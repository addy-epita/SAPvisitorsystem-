<?php
/**
 * Reminder and Escalation Cron Job
 *
 * Run every 15 minutes: */15 * * * * /usr/bin/php /var/www/visitors/cron/reminders.php
 * Run escalation at 18:00: 0 18 * * * /usr/bin/php /var/www/visitors/cron/reminders.php --escalation
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line');
}

$db = new Database();

// Initialize email service with proper configuration
$emailConfig = [
    'tenant_id' => MS_GRAPH_TENANT_ID,
    'client_id' => MS_GRAPH_CLIENT_ID,
    'client_secret' => MS_GRAPH_CLIENT_SECRET,
    'from_email' => MS_GRAPH_FROM_EMAIL,
    'from_name' => MS_GRAPH_FROM_NAME
];

$emailService = new EmailService($db->getPdo(), $emailConfig, BASE_URL);

$isEscalation = in_array('--escalation', $argv);

echo "Starting reminders job at " . date('Y-m-d H:i:s') . "\n";

if ($isEscalation) {
    // End of day escalation
    echo "Running end-of-day escalation...\n";

    $unconfirmed = $db->fetchAll(
        "SELECT v.*, h.name as host_name
         FROM visitors v
         LEFT JOIN hosts h ON v.host_email = h.email
         WHERE v.status = 'checked_in'
         AND DATE(v.arrival_time) = CURDATE()"
    );

    if (empty($unconfirmed)) {
        echo "No unconfirmed visitors found.\n";
    } else {
        // Get supervisor emails from settings
        $supervisorEmails = explode(',', get_setting('escalation_emails') ?: ESCALATION_EMAILS);

        if (empty($supervisorEmails[0])) {
            echo "No supervisor emails configured!\n";
        } else {
            $sent = $emailService->sendEscalation($unconfirmed, $supervisorEmails);
            echo "Escalation email sent to " . count($supervisorEmails) . " supervisors\n";

            // Mark visitors as unconfirmed
            foreach ($unconfirmed as $visitor) {
                $db->execute(
                    "UPDATE visitors SET status = 'unconfirmed' WHERE id = ?",
                    [$visitor['id']]
                );
            }
        }
    }
} else {
    // Regular reminders at 2h, 4h, 6h, 8h
    $intervals = [120, 240, 360, 480]; // minutes

    foreach ($intervals as $intervalMinutes) {
        $visitors = $db->fetchAll(
            "SELECT v.*, h.name as host_name
             FROM visitors v
             LEFT JOIN hosts h ON v.host_email = h.email
             WHERE v.status = 'checked_in'
             AND TIMESTAMPDIFF(MINUTE, v.arrival_time, NOW()) >= ?
             AND TIMESTAMPDIFF(MINUTE, v.arrival_time, NOW()) < ? + 15
             AND NOT EXISTS (
                 SELECT 1 FROM notifications n
                 WHERE n.visitor_id = v.id
                 AND n.type = 'reminder'
                 AND TIMESTAMPDIFF(MINUTE, n.sent_at, NOW()) < 15
             )",
            [$intervalMinutes, $intervalMinutes]
        );

        foreach ($visitors as $visitor) {
            echo "Sending reminder for visitor {$visitor['id']} ({$intervalMinutes}min)\n";

            $emailService->sendReminder($visitor, $intervalMinutes);

            // Log notification
            $db->execute(
                "INSERT INTO notifications (visitor_id, type, recipient_email, status)
                 VALUES (?, 'reminder', ?, 'sent')",
                [$visitor['id'], $visitor['host_email']]
            );
        }
    }
}

echo "Reminders job completed at " . date('Y-m-d H:i:s') . "\n";
