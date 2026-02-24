<?php
/**
 * Reminder Email Template
 *
 * This template is used for reminder emails sent to hosts when a visitor
 * has been on site for an extended period.
 * Subject: [RAPPEL] Visiteur sur site depuis {duration}
 *
 * Variables expected:
 * - $visitor: Array with visitor data
 * - $host: Array with host data
 * - $duration: Formatted duration string (e.g., "4h 30min")
 * - $actionToken: Secure token for action buttons
 * - $baseUrl: Base URL for action links
 */

if (!isset($visitor, $host, $duration, $actionToken, $baseUrl)) {
    die('This template requires visitor, host, duration, actionToken, and baseUrl variables');
}

$stillHereUrl = $baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=still_here';
$leftUrl = $baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=left';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel Visiteur - SAP Visitor System</title>
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
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
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
            transition: background-color 0.3s ease;
        }
        .button-success {
            background-color: #27AE60;
            color: #FFFFFF;
        }
        .button-success:hover {
            background-color: #219A52;
        }
        .button-danger {
            background-color: #E74C3C;
            color: #FFFFFF;
        }
        .button-danger:hover {
            background-color: #C0392B;
        }
        .urgent-box {
            background-color: #FFF3E0;
            border-left: 4px solid #FF9800;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .info-box {
            background-color: #F0F8FF;
            border-left: 4px solid #008FD3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .detail-row {
            display: flex;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #EEEEEE;
        }
        .detail-row:last-child {
            border-bottom: none;
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
        .duration-highlight {
            font-size: 24px;
            font-weight: bold;
            color: #FF9800;
        }
        .action-section {
            background-color: #FAFAFA;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
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
            <h1>Rappel Visiteur</h1>
        </div>

        <div class="content">
            <p>Bonjour <strong><?php echo htmlspecialchars($host['name'] ?? 'Madame, Monsieur'); ?></strong>,</p>

            <div class="urgent-box">
                <p style="margin: 0; font-size: 16px;">
                    <strong>Rappel :</strong>
                    <?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?>
                    (<?php echo htmlspecialchars($visitor['company']); ?>) est sur site depuis
                    <span class="duration-highlight"><?php echo htmlspecialchars($duration); ?></span>.
                </p>
            </div>

            <p>Veuillez confirmer le statut de ce visiteur :</p>

            <div class="action-section">
                <a href="<?php echo $stillHereUrl; ?>" class="button button-success">TOUJOURS LÀ</a>
                <a href="<?php echo $leftUrl; ?>" class="button button-danger">PARTI</a>
            </div>

            <div class="info-box">
                <p style="margin: 0;">
                    <strong>Détails du visiteur :</strong><br>
                    Nom : <?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?><br>
                    Société : <?php echo htmlspecialchars($visitor['company']); ?><br>
                    Motif : <?php echo htmlspecialchars($visitor['reason']); ?><br>
                    Heure d'arrivée : <?php echo date('H:i', strtotime($visitor['arrival_time'])); ?>
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>Système de Gestion des Visiteurs SAP</strong></p>
            <p>Ce message a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; <?php echo date('Y'); ?> SAP. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
