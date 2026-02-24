<?php
/**
 * Email Service Class
 *
 * Handles all email notifications for the Visitor Management System.
 * Uses Microsoft Graph API for sending emails.
 */

require_once __DIR__ . '/microsoft-graph.php';

class EmailService
{
    private MicrosoftGraphClient $graphClient;
    private PDO $db;
    private string $baseUrl;
    private array $settings;

    // SAP Brand Colors
    private const SAP_BLUE = '#008FD3';
    private const SAP_DARK_BLUE = '#0070A0';
    private const SAP_GRAY = '#5C5C5C';
    private const SAP_LIGHT_GRAY = '#F5F5F5';
    private const SAP_WHITE = '#FFFFFF';
    private const SAP_RED = '#E74C3C';
    private const SAP_GREEN = '#27AE60';

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param array $config Microsoft Graph API configuration
     * @param string $baseUrl Base URL for action links
     */
    public function __construct(PDO $db, array $config, string $baseUrl)
    {
        $this->db = $db;
        $this->baseUrl = rtrim($baseUrl, '/');

        // Initialize Microsoft Graph client
        $this->graphClient = new MicrosoftGraphClient(
            $config['tenant_id'],
            $config['client_id'],
            $config['client_secret'],
            $config['from_email'],
            $config['from_name'] ?? 'SAP Visitor System'
        );

        // Load settings
        $this->settings = $this->loadSettings();
    }

    /**
     * Load system settings from database
     *
     * @return array
     */
    private function loadSettings(): array
    {
        $defaultSettings = [
            'site_name' => 'SAP Office',
            'end_of_day_time' => '18:00',
            'reminder_intervals' => [120, 240, 360, 480]
        ];

        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            if (isset($settings['reminder_intervals'])) {
                $settings['reminder_intervals'] = json_decode($settings['reminder_intervals'], true);
            }

            return array_merge($defaultSettings, $settings);
        } catch (Exception $e) {
            $this->logError('Failed to load settings: ' . $e->getMessage());
            return $defaultSettings;
        }
    }

    /**
     * Send arrival notification to host
     *
     * @param array $visitor Visitor data from database
     * @return bool Success status
     */
    public function sendArrivalNotification(array $visitor): bool
    {
        try {
            $subject = sprintf(
                '[VISITEUR ARRIVÉ] %s %s - %s',
                htmlspecialchars($visitor['first_name']),
                htmlspecialchars($visitor['last_name']),
                htmlspecialchars($visitor['company'])
            );

            $actionToken = $this->generateActionToken($visitor['id']);
            $htmlBody = $this->getArrivalEmailTemplate($visitor, $actionToken);

            $result = $this->graphClient->sendEmail(
                $visitor['host_email'],
                $visitor['host_name'] ?? '',
                $subject,
                $htmlBody
            );

            // Log notification
            $this->logNotification($visitor['id'], 'arrival', $visitor['host_email'], 'sent');

            return true;
        } catch (Exception $e) {
            $this->logError('Failed to send arrival notification: ' . $e->getMessage());
            $this->logNotification($visitor['id'], 'arrival', $visitor['host_email'], 'failed');
            return false;
        }
    }

    /**
     * Send reminder email to host
     *
     * @param array $visitor Visitor data
     * @param int $durationMinutes Duration on site in minutes
     * @return bool Success status
     */
    public function sendReminder(array $visitor, int $durationMinutes): bool
    {
        try {
            $duration = $this->formatDuration($durationMinutes);

            $subject = sprintf('[RAPPEL] Visiteur sur site depuis %s', $duration);

            $actionToken = $this->generateActionToken($visitor['id']);
            $htmlBody = $this->getReminderEmailTemplate($visitor, $duration, $actionToken);

            $result = $this->graphClient->sendEmail(
                $visitor['host_email'],
                $visitor['host_name'] ?? '',
                $subject,
                $htmlBody
            );

            // Log notification
            $this->logNotification($visitor['id'], 'reminder', $visitor['host_email'], 'sent');

            return true;
        } catch (Exception $e) {
            $this->logError('Failed to send reminder: ' . $e->getMessage());
            $this->logNotification($visitor['id'], 'reminder', $visitor['host_email'], 'failed');
            return false;
        }
    }

    /**
     * Send end-of-day escalation to supervisors
     *
     * @param array $visitors Array of unconfirmed visitors
     * @param array $supervisors Array of supervisor emails
     * @return bool Success status
     */
    public function sendEscalation(array $visitors, array $supervisors): bool
    {
        try {
            $subject = '[FIN DE JOURNÉE] Visites non confirmées';
            $htmlBody = $this->getEscalationEmailTemplate($visitors);

            $recipients = [];
            foreach ($supervisors as $supervisor) {
                $recipients[] = [
                    'email' => is_array($supervisor) ? $supervisor['email'] : $supervisor,
                    'name' => is_array($supervisor) ? ($supervisor['name'] ?? '') : ''
                ];
            }

            $results = $this->graphClient->sendEmailToMultiple(
                $recipients,
                $subject,
                $htmlBody
            );

            // Log notifications
            foreach ($visitors as $visitor) {
                foreach ($recipients as $recipient) {
                    $this->logNotification(
                        $visitor['id'],
                        'escalation',
                        $recipient['email'],
                        $results[$recipient['email']]['success'] ? 'sent' : 'failed'
                    );
                }
            }

            // Return true if at least one email was sent successfully
            foreach ($results as $result) {
                if ($result['success']) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            $this->logError('Failed to send escalation: ' . $e->getMessage());
            foreach ($visitors as $visitor) {
                foreach ($supervisors as $supervisor) {
                    $email = is_array($supervisor) ? $supervisor['email'] : $supervisor;
                    $this->logNotification($visitor['id'], 'escalation', $email, 'failed');
                }
            }
            return false;
        }
    }

    /**
     * Send checkout confirmation to visitor
     *
     * @param array $visitor Visitor data
     * @param bool $includeEvacuation Include evacuation confirmation
     * @return bool Success status
     */
    public function sendCheckoutConfirmation(array $visitor, bool $includeEvacuation = false): bool
    {
        if (empty($visitor['visitor_email'])) {
            return false;
        }

        try {
            $subject = 'Confirmation de départ - SAP Visitor System';
            $htmlBody = $this->getCheckoutEmailTemplate($visitor, $includeEvacuation);

            $result = $this->graphClient->sendEmail(
                $visitor['visitor_email'],
                $visitor['first_name'] . ' ' . $visitor['last_name'],
                $subject,
                $htmlBody
            );

            // Log notification
            $this->logNotification($visitor['id'], 'checkout', $visitor['visitor_email'], 'sent');

            return true;
        } catch (Exception $e) {
            $this->logError('Failed to send checkout confirmation: ' . $e->getMessage());
            $this->logNotification($visitor['id'], 'checkout', $visitor['visitor_email'], 'failed');
            return false;
        }
    }

    /**
     * Generate secure action token for host actions
     *
     * @param int $visitorId
     * @return string
     */
    private function generateActionToken(int $visitorId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        // Store token in database
        $stmt = $this->db->prepare("
            INSERT INTO action_tokens (visitor_id, token, action_type, expires_at, created_at)
            VALUES (?, ?, 'host_action', ?, NOW())
        ");
        $stmt->execute([$visitorId, $token, $expiresAt]);

        return $token;
    }

    /**
     * Format duration in minutes to human-readable string
     *
     * @param int $minutes
     * @return string
     */
    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return sprintf('%dh %dmin', $hours, $mins);
        } elseif ($hours > 0) {
            return sprintf('%dh', $hours);
        } else {
            return sprintf('%dmin', $mins);
        }
    }

    /**
     * Log notification to database
     *
     * @param int $visitorId
     * @param string $type
     * @param string $recipientEmail
     * @param string $status
     */
    private function logNotification(int $visitorId, string $type, string $recipientEmail, string $status): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (visitor_id, type, recipient_email, status, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$visitorId, $type, $recipientEmail, $status]);
        } catch (Exception $e) {
            error_log('Failed to log notification: ' . $e->getMessage());
        }
    }

    /**
     * Log error
     *
     * @param string $message
     */
    private function logError(string $message): void
    {
        error_log('[EmailService] ' . $message);

        // Also log to audit_log if database is available
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (action, details, ip_address, created_at)
                VALUES ('email_error', ?, ?, NOW())
            ");
            $stmt->execute([$message, $_SERVER['REMOTE_ADDR'] ?? 'cli']);
        } catch (Exception $e) {
            // Silent fail - we don't want to crash on logging
        }
    }

    /**
     * Get email header HTML (common to all emails)
     *
     * @param string $title
     * @return string
     */
    private function getEmailHeader(string $title): string
    {
        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F5F5F5;
            color: #333333;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
        }
        .header {
            background: linear-gradient(135deg, #008FD3 0%, #0070A0 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #FFFFFF;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .logo {
            color: #FFFFFF;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background-color: #F5F5F5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #5C5C5C;
            border-top: 1px solid #E0E0E0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .button-primary {
            background-color: #008FD3;
            color: #FFFFFF;
        }
        .button-success {
            background-color: #27AE60;
            color: #FFFFFF;
        }
        .button-danger {
            background-color: #E74C3C;
            color: #FFFFFF;
        }
        .info-box {
            background-color: #F0F8FF;
            border-left: 4px solid #008FD3;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background-color: #FFF8E1;
            border-left: 4px solid #FFA726;
            padding: 15px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #EEEEEE;
        }
        .detail-label {
            font-weight: 600;
            color: #5C5C5C;
            width: 140px;
            flex-shrink: 0;
        }
        .detail-value {
            color: #333333;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }
            .button {
                display: block;
                margin: 10px 0;
                text-align: center;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SAP</div>
            <h1>' . htmlspecialchars($title) . '</h1>
        </div>
        <div class="content">';
    }

    /**
     * Get email footer HTML (common to all emails)
     *
     * @return string
     */
    private function getEmailFooter(): string
    {
        return '</div>
        <div class="footer">
            <p><strong>Système de Gestion des Visiteurs SAP</strong></p>
            <p>Ce message a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; ' . date('Y') . ' SAP. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Get arrival email template
     *
     * @param array $visitor
     * @param string $actionToken
     * @return string
     */
    private function getArrivalEmailTemplate(array $visitor, string $actionToken): string
    {
        $arrivalTime = date('H:i', strtotime($visitor['arrival_time']));
        $expectedHours = ceil($visitor['expected_duration'] / 60);
        $endOfDay = $this->settings['end_of_day_time'];

        $stillHereUrl = $this->baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=still_here';
        $leftUrl = $this->baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=left';

        $body = '
            <p>Bonjour <strong>' . htmlspecialchars($visitor['host_name'] ?? 'Madame, Monsieur') . '</strong>,</p>

            <p>Un visiteur est arrivé pour vous :</p>

            <div class="info-box">
                <div class="detail-row">
                    <span class="detail-label">Nom :</span>
                    <span class="detail-value">' . htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Société :</span>
                    <span class="detail-value">' . htmlspecialchars($visitor['company']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Motif :</span>
                    <span class="detail-value">' . htmlspecialchars($visitor['reason']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Heure d\'arrivée :</span>
                    <span class="detail-value">' . $arrivalTime . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durée prévue :</span>
                    <span class="detail-value">' . $expectedHours . ' heure' . ($expectedHours > 1 ? 's' : '') . '</span>
                </div>
            </div>

            <p style="font-size: 16px; margin-top: 25px;"><strong>Ce visiteur est-il toujours sur site ?</strong></p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $stillHereUrl . '" class="button button-success">TOUJOURS LÀ</a>
                <a href="' . $leftUrl . '" class="button button-danger">PARTI</a>
            </div>

            <div class="warning-box">
                <p style="margin: 0;">
                    <strong>Important :</strong> Si vous ne confirmez pas le départ avant ' . $endOfDay . ',
                    les chefs de car seront automatiquement notifiés pour vérification.
                </p>
            </div>

            <p style="font-size: 13px; color: #666; margin-top: 20px;">
                Ces liens sont valables pendant 7 jours. En cas de problème, veuillez contacter l\'accueil.
            </p>';

        return $this->getEmailHeader('Visiteur Arrivé') . $body . $this->getEmailFooter();
    }

    /**
     * Get reminder email template
     *
     * @param array $visitor
     * @param string $duration
     * @param string $actionToken
     * @return string
     */
    private function getReminderEmailTemplate(array $visitor, string $duration, string $actionToken): string
    {
        $stillHereUrl = $this->baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=still_here';
        $leftUrl = $this->baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=left';

        $body = '
            <p>Bonjour <strong>' . htmlspecialchars($visitor['host_name'] ?? 'Madame, Monsieur') . '</strong>,</p>

            <div class="warning-box">
                <p style="margin: 0; font-size: 16px;">
                    <strong>Rappel :</strong> ' . htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) .
                    ' (' . htmlspecialchars($visitor['company']) . ') est sur site depuis <strong>' . $duration . '</strong>.
                </p>
            </div>

            <p>Veuillez confirmer le statut de ce visiteur :</p>

            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $stillHereUrl . '" class="button button-success">TOUJOURS LÀ</a>
                <a href="' . $leftUrl . '" class="button button-danger">PARTI</a>
            </div>

            <div class="info-box">
                <p style="margin: 0;">
                    <strong>Détails du visiteur :</strong><br>
                    Motif : ' . htmlspecialchars($visitor['reason']) . '<br>
                    Heure d\'arrivée : ' . date('H:i', strtotime($visitor['arrival_time'])) . '
                </p>
            </div>';

        return $this->getEmailHeader('Rappel Visiteur') . $body . $this->getEmailFooter();
    }

    /**
     * Get escalation email template
     *
     * @param array $visitors
     * @return string
     */
    private function getEscalationEmailTemplate(array $visitors): string
    {
        $visitorRows = '';
        foreach ($visitors as $visitor) {
            $arrivalTime = date('H:i', strtotime($visitor['arrival_time']));
            $duration = $this->formatDuration(
                (time() - strtotime($visitor['arrival_time'])) / 60
            );

            $visitorRows .= '
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #EEEEEE;">' .
                        htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) . '</td>
                    <td style="padding: 12px; border-bottom: 1px solid #EEEEEE;">' .
                        htmlspecialchars($visitor['company']) . '</td>
                    <td style="padding: 12px; border-bottom: 1px solid #EEEEEE;">' .
                        htmlspecialchars($visitor['host_name'] ?? 'N/A') . '</td>
                    <td style="padding: 12px; border-bottom: 1px solid #EEEEEE;">' .
                        $arrivalTime . '</td>
                    <td style="padding: 12px; border-bottom: 1px solid #EEEEEE;">' .
                        $duration . '</td>
                </tr>';
        }

        $body = '
            <p>Bonjour,</p>

            <p>Les visites suivantes n\'ont pas été confirmées comme terminées :</p>

            <table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;">
                <thead>
                    <tr style="background-color: #008FD3; color: #FFFFFF;">
                        <th style="padding: 12px; text-align: left;">Visiteur</th>
                        <th style="padding: 12px; text-align: left;">Société</th>
                        <th style="padding: 12px; text-align: left;">Hôte</th>
                        <th style="padding: 12px; text-align: left;">Arrivée</th>
                        <th style="padding: 12px; text-align: left;">Durée</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $visitorRows . '
                </tbody>
            </table>

            <div class="warning-box">
                <p style="margin: 0;">
                    <strong>Action requise :</strong> Merci de vérifier si ces personnes sont encore sur site
                    et de mettre à jour leur statut dans le système de gestion des visiteurs.
                </p>
            </div>

            <p style="text-align: center; margin: 30px 0;">
                <a href="' . $this->baseUrl . '/admin/dashboard.php" class="button button-primary">
                    Accéder au tableau de bord
                </a>
            </p>';

        return $this->getEmailHeader('Fin de Journée - Visites Non Confirmées') . $body . $this->getEmailFooter();
    }

    /**
     * Get checkout email template
     *
     * @param array $visitor
     * @param bool $includeEvacuation
     * @return string
     */
    private function getCheckoutEmailTemplate(array $visitor, bool $includeEvacuation): string
    {
        $arrivalTime = date('H:i', strtotime($visitor['arrival_time']));
        $departureTime = date('H:i', strtotime($visitor['departure_time'] ?? 'now'));

        // Calculate duration
        $arrival = strtotime($visitor['arrival_time']);
        $departure = strtotime($visitor['departure_time'] ?? 'now');
        $durationMinutes = ($departure - $arrival) / 60;
        $duration = $this->formatDuration($durationMinutes);

        $evacuationSection = '';
        if ($includeEvacuation) {
            $evacuationSection = '
                <div class="info-box" style="background-color: #E8F5E9; border-left-color: #27AE60;">
                    <p style="margin: 0;">
                        <strong>Confirmation d\'évacuation :</strong><br>
                        En cas d\'urgence ou d\'évacuation, votre présence a été enregistrée comme
                        <strong>quitte les locaux</strong> à ' . $departureTime . '.
                    </p>
                </div>';
        }

        $body = '
            <p>Bonjour <strong>' . htmlspecialchars($visitor['first_name']) . '</strong>,</p>

            <p>Merci de votre visite ! Nous vous confirmons votre départ des locaux SAP.</p>

            <div class="info-box">
                <h3 style="margin-top: 0; color: #008FD3;">Résumé de votre visite</h3>
                <div class="detail-row">
                    <span class="detail-label">Date :</span>
                    <span class="detail-value">' . date('d/m/Y', strtotime($visitor['arrival_time'])) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Heure d\'arrivée :</span>
                    <span class="detail-value">' . $arrivalTime . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Heure de départ :</span>
                    <span class="detail-value">' . $departureTime . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durée totale :</span>
                    <span class="detail-value">' . $duration . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Hôte :</span>
                    <span class="detail-value">' . htmlspecialchars($visitor['host_name'] ?? 'N/A') . '</span>
                </div>
            </div>

            ' . $evacuationSection . '

            <p style="margin-top: 25px;">
                Nous espérons que votre visite s\'est bien déroulée. N\'hésitez pas à nous contacter
                pour toute question ou pour planifier votre prochaine visite.
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="https://www.sap.com" class="button button-primary">Visiter sap.com</a>
            </p>';

        return $this->getEmailHeader('Confirmation de Départ') . $body . $this->getEmailFooter();
    }

    /**
     * Verify Microsoft Graph API connection
     *
     * @return array Status information
     */
    public function verifyConnection(): array
    {
        try {
            $this->graphClient->verifyConnection();
            return [
                'success' => true,
                'message' => 'Connexion à Microsoft Graph API établie avec succès',
                'sender' => $this->graphClient->getFromEmail()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur de connexion : ' . $e->getMessage(),
                'sender' => $this->graphClient->getFromEmail()
            ];
        }
    }
}
