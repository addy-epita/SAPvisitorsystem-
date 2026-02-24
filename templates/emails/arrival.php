<?php
/**
 * Host Arrival Notification Email Template
 *
 * This template is used for the initial email sent to hosts when a visitor checks in.
 * Subject: [VISITEUR ARRIVÉ] {Name} - {Company}
 *
 * Variables expected:
 * - $visitor: Array with visitor data (first_name, last_name, company, reason, arrival_time, expected_duration)
 * - $host: Array with host data (name, email)
 * - $actionToken: Secure token for action buttons
 * - $baseUrl: Base URL for action links
 * - $settings: System settings array
 */

if (!isset($visitor, $host, $actionToken, $baseUrl)) {
    die('This template requires visitor, host, actionToken, and baseUrl variables');
}

$arrivalTime = date('H:i', strtotime($visitor['arrival_time']));
$expectedHours = ceil(($visitor['expected_duration'] ?? 180) / 60);
$endOfDay = $settings['end_of_day_time'] ?? '18:00';

$stillHereUrl = $baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=still_here';
$leftUrl = $baseUrl . '/host-action.php?token=' . urlencode($actionToken) . '&action=left';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visiteur Arrivé - SAP Visitor System</title>
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
        .info-box {
            background-color: #F0F8FF;
            border-left: 4px solid #008FD3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .warning-box {
            background-color: #FFF8E1;
            border-left: 4px solid #FFA726;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .detail-row {
            display: flex;
            margin: 12px 0;
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
            font-weight: 500;
        }
        .action-section {
            background-color: #FAFAFA;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .action-title {
            font-size: 18px;
            font-weight: 600;
            color: #333333;
            margin-bottom: 20px;
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
            <h1>Visiteur Arrivé</h1>
        </div>

        <div class="content">
            <p>Bonjour <strong><?php echo htmlspecialchars($host['name'] ?? 'Madame, Monsieur'); ?></strong>,</p>

            <p>Un visiteur est arrivé pour vous :</p>

            <div class="info-box">
                <div class="detail-row">
                    <span class="detail-label">Nom :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Société :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitor['company']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Motif :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitor['reason']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Heure d'arrivée :</span>
                    <span class="detail-value"><?php echo $arrivalTime; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durée prévue :</span>
                    <span class="detail-value"><?php echo $expectedHours; ?> heure<?php echo $expectedHours > 1 ? 's' : ''; ?></span>
                </div>
            </div>

            <div class="action-section">
                <div class="action-title">Ce visiteur est-il toujours sur site ?</div>
                <a href="<?php echo $stillHereUrl; ?>" class="button button-success">TOUJOURS LÀ</a>
                <a href="<?php echo $leftUrl; ?>" class="button button-danger">PARTI</a>
            </div>

            <div class="warning-box">
                <p style="margin: 0;">
                    <strong>Important :</strong> Si vous ne confirmez pas le départ avant <?php echo $endOfDay; ?>,
                    les chefs de car seront automatiquement notifiés pour vérification.
                </p>
            </div>

            <p style="font-size: 13px; color: #666; margin-top: 20px;">
                Ces liens sont valables pendant 7 jours. En cas de problème, veuillez contacter l'accueil.
            </p>
        </div>

        <div class="footer">
            <p><strong>Système de Gestion des Visiteurs SAP</strong></p>
            <p>Ce message a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; <?php echo date('Y'); ?> SAP. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
