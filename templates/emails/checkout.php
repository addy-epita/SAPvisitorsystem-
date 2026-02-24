<?php
/**
 * Checkout Confirmation Email Template
 *
 * This template is used for the confirmation email sent to visitors
 * after they check out.
 *
 * Variables expected:
 * - $visitor: Array with visitor data
 * - $includeEvacuation: Boolean to include evacuation confirmation
 * - $baseUrl: Base URL for links
 */

if (!isset($visitor)) {
    die('This template requires visitor variable');
}

$arrivalTime = date('H:i', strtotime($visitor['arrival_time']));
$departureTime = date('H:i', strtotime($visitor['departure_time'] ?? 'now'));
$visitDate = date('d/m/Y', strtotime($visitor['arrival_time']));

// Calculate duration
$arrival = strtotime($visitor['arrival_time']);
$departure = strtotime($visitor['departure_time'] ?? 'now');
$durationMinutes = ($departure - $arrival) / 60;

function formatDuration(int $minutes): string {
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

$duration = formatDuration($durationMinutes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Départ - SAP Visitor System</title>
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
            background: linear-gradient(135deg, #27AE60 0%, #219A52 100%);
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
        .button-primary {
            background-color: #008FD3;
            color: #FFFFFF;
        }
        .button-primary:hover {
            background-color: #0070A0;
        }
        .success-box {
            background-color: #E8F5E9;
            border-left: 4px solid #27AE60;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
            text-align: center;
        }
        .success-icon {
            font-size: 48px;
            color: #27AE60;
            margin-bottom: 10px;
        }
        .info-box {
            background-color: #F0F8FF;
            border-left: 4px solid #008FD3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .evacuation-box {
            background-color: #E3F2FD;
            border-left: 4px solid #1976D2;
            padding: 20px;
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
            width: 160px;
            flex-shrink: 0;
        }
        .detail-value {
            color: #333333;
            font-weight: 500;
        }
        .thank-you {
            font-size: 20px;
            color: #27AE60;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }
        .divider {
            height: 1px;
            background-color: #E0E0E0;
            margin: 25px 0;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px;
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
            <h1>Confirmation de Départ</h1>
        </div>

        <div class="content">
            <div class="success-box">
                <div class="success-icon">✓</div>
                <div class="thank-you">Merci de votre visite !</div>
                <p style="margin: 0;">
                    Nous vous confirmons votre départ des locaux SAP.
                </p>
            </div>

            <div class="info-box">
                <h3 style="margin-top: 0; color: #008FD3;">Résumé de votre visite</h3>

                <div class="detail-row">
                    <span class="detail-label">Date :</span>
                    <span class="detail-value"><?php echo $visitDate; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Heure d'arrivée :</span>
                    <span class="detail-value"><?php echo $arrivalTime; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Heure de départ :</span>
                    <span class="detail-value"><?php echo $departureTime; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Durée totale :</span>
                    <span class="detail-value"><?php echo $duration; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Hôte :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitor['host_name'] ?? 'N/A'); ?></span>
                </div>
                <?php if (!empty($visitor['reason'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Motif :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($visitor['reason']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($includeEvacuation): ?>
            <div class="evacuation-box">
                <h4 style="margin-top: 0; color: #1976D2;">Confirmation d'évacuation</h4>
                <p style="margin: 0;">
                    En cas d'urgence ou d'évacuation, votre présence a été enregistrée comme
                    <strong>quitte les locaux</strong> à <?php echo $departureTime; ?>.
                </p>
            </div>
            <?php endif; ?>

            <div class="divider"></div>

            <p>
                Nous espérons que votre visite s'est bien déroulée. N'hésitez pas à nous contacter
                pour toute question ou pour planifier votre prochaine visite.
            </p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="https://www.sap.com" class="button button-primary">Visiter sap.com</a>
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
