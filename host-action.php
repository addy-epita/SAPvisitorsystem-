<?php
/**
 * Host Action Handler
 *
 * This file handles the action links from host emails (still_here/left).
 * It validates the token, updates the visitor status, and shows a
 * confirmation page to the host.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Get parameters
$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

// Validate parameters
$validActions = ['still_here', 'left'];
if (empty($token) || !in_array($action, $validActions)) {
    showError('Lien invalide ou expiré.');
    exit;
}

try {
    // Verify token and get visitor info
    $stmt = $db->prepare("
        SELECT at.*, v.id as visitor_id, v.first_name, v.last_name, v.company,
               v.status as visitor_status, v.host_name, v.host_email
        FROM action_tokens at
        JOIN visitors v ON at.visitor_id = v.id
        WHERE at.token = ?
        AND at.action_type = 'host_action'
        AND at.expires_at > NOW()
        AND at.used_at IS NULL
    ");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        showError('Ce lien est invalide, a déjà été utilisé ou a expiré.');
        exit;
    }

    // Check if visitor is still checked in
    if ($tokenData['visitor_status'] !== 'checked_in') {
        showInfo('Cette visite a déjà été clôturée. Le statut est : ' . getStatusLabel($tokenData['visitor_status']));
        markTokenUsed($db, $token);
        exit;
    }

    // Process the action
    $newStatus = ($action === 'left') ? 'checked_out' : 'checked_in';
    $checkoutMethod = ($action === 'left') ? 'host_confirmed' : null;

    if ($action === 'left') {
        // Update visitor as checked out
        $stmt = $db->prepare("
            UPDATE visitors
            SET status = 'checked_out',
                departure_time = NOW(),
                checkout_method = 'host_confirmed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$tokenData['visitor_id']]);

        // Log the action
        logAction($db, 'host_checkout', $tokenData['visitor_id'], [
            'host_email' => $tokenData['host_email'],
            'method' => 'email_link'
        ]);

        // Mark token as used
        markTokenUsed($db, $token);

        // Show success page
        showSuccess('left', $tokenData);

    } else {
        // Action: still_here - extend duration or just confirm
        $stmt = $db->prepare("
            UPDATE visitors
            SET expected_duration = expected_duration + 60,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$tokenData['visitor_id']]);

        // Log the action
        logAction($db, 'host_still_here', $tokenData['visitor_id'], [
            'host_email' => $tokenData['host_email']
        ]);

        // Mark token as used
        markTokenUsed($db, $token);

        // Show success page
        showSuccess('still_here', $tokenData);
    }

} catch (Exception $e) {
    error_log('Host action error: ' . $e->getMessage());
    showError('Une erreur est survenue. Veuillez réessayer ou contacter l\'accueil.');
}

/**
 * Mark token as used
 */
function markTokenUsed(PDO $db, string $token): void {
    $stmt = $db->prepare("
        UPDATE action_tokens
        SET used_at = NOW()
        WHERE token = ?
    ");
    $stmt->execute([$token]);
}

/**
 * Log action to audit log
 */
function logAction(PDO $db, string $action, int $visitorId, array $details): void {
    $stmt = $db->prepare("
        INSERT INTO audit_log (action, user_email, visitor_id, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $action,
        $details['host_email'] ?? null,
        $visitorId,
        json_encode($details),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

/**
 * Get French label for status
 */
function getStatusLabel(string $status): string {
    $labels = [
        'checked_in' => 'En cours',
        'checked_out' => 'Terminée',
        'unconfirmed' => 'Non confirmée',
        'manual_close' => 'Clôturée manuellement'
    ];
    return $labels[$status] ?? $status;
}

/**
 * Show error page
 */
function showError(string $message): void {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erreur - SAP Visitor System</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #F5F5F5 0%, #E0E0E0 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .card {
                background: #FFFFFF;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                max-width: 500px;
                width: 100%;
                padding: 40px;
                text-align: center;
            }
            .icon {
                width: 80px;
                height: 80px;
                background: #FFEBEE;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 40px;
                color: #E74C3C;
            }
            h1 {
                color: #333;
                font-size: 24px;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 25px;
            }
            .logo {
                color: #008FD3;
                font-size: 32px;
                font-weight: bold;
                margin-bottom: 30px;
            }
            .contact {
                background: #F5F5F5;
                padding: 15px;
                border-radius: 8px;
                font-size: 14px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="logo">SAP</div>
            <div class="icon">✕</div>
            <h1>Erreur</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <div class="contact">
                En cas de problème, veuillez contacter l'accueil ou l'administrateur système.
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Show info page
 */
function showInfo(string $message): void {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Information - SAP Visitor System</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #F5F5F5 0%, #E0E0E0 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .card {
                background: #FFFFFF;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                max-width: 500px;
                width: 100%;
                padding: 40px;
                text-align: center;
            }
            .icon {
                width: 80px;
                height: 80px;
                background: #E3F2FD;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 40px;
                color: #008FD3;
            }
            h1 {
                color: #333;
                font-size: 24px;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 25px;
            }
            .logo {
                color: #008FD3;
                font-size: 32px;
                font-weight: bold;
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="logo">SAP</div>
            <div class="icon">ℹ</div>
            <h1>Information</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Show success page
 */
function showSuccess(string $action, array $visitorData): void {
    $isLeft = ($action === 'left');
    $title = $isLeft ? 'Départ Confirmé' : 'Présence Confirmée';
    $message = $isLeft
        ? sprintf(
            'Le départ de %s (%s) a été confirmé.',
            htmlspecialchars($visitorData['first_name'] . ' ' . $visitorData['last_name']),
            htmlspecialchars($visitorData['company'])
        )
        : sprintf(
            'La présence de %s (%s) a été confirmée. La durée de visite a été prolongée.',
            htmlspecialchars($visitorData['first_name'] . ' ' . $visitorData['last_name']),
            htmlspecialchars($visitorData['company'])
        );
    $icon = $isLeft ? '✓' : '✓';
    $iconColor = $isLeft ? '#27AE60' : '#008FD3';
    $iconBg = $isLeft ? '#E8F5E9' : '#E3F2FD';
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - SAP Visitor System</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #F5F5F5 0%, #E0E0E0 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .card {
                background: #FFFFFF;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                max-width: 500px;
                width: 100%;
                padding: 40px;
                text-align: center;
            }
            .icon {
                width: 100px;
                height: 100px;
                background: <?php echo $iconBg; ?>;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 25px;
                font-size: 50px;
                color: <?php echo $iconColor; ?>;
                animation: scaleIn 0.3s ease-out;
            }
            @keyframes scaleIn {
                0% { transform: scale(0); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            h1 {
                color: #333;
                font-size: 28px;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .logo {
                color: #008FD3;
                font-size: 32px;
                font-weight: bold;
                margin-bottom: 30px;
            }
            .details {
                background: #F5F5F5;
                padding: 20px;
                border-radius: 8px;
                text-align: left;
                margin-bottom: 25px;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #E0E0E0;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .detail-label {
                color: #666;
                font-weight: 500;
            }
            .detail-value {
                color: #333;
                font-weight: 600;
            }
            .timestamp {
                font-size: 12px;
                color: #999;
                margin-top: 20px;
            }
            .close-note {
                font-size: 13px;
                color: #888;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #E0E0E0;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="logo">SAP</div>
            <div class="icon"><?php echo $icon; ?></div>
            <h1><?php echo $title; ?></h1>
            <p><?php echo $message; ?></p>

            <div class="details">
                <div class="detail-row">
                    <span class="detail-label">Visiteur :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitorData['first_name'] . ' ' . $visitorData['last_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Société :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitorData['company']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Hôte :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitorData['host_name'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="timestamp">
                Confirmation enregistrée le <?php echo date('d/m/Y à H:i'); ?>
            </div>

            <div class="close-note">
                Vous pouvez fermer cette page.
            </div>
        </div>
    </body>
    </html>
    <?php
}
