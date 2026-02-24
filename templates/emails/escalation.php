<?php
/**
 * Escalation Email Template
 *
 * This template is used for end-of-day escalation emails sent to supervisors
 * when visitors have not been confirmed as departed.
 * Subject: [FIN DE JOURNÉE] Visites non confirmées
 *
 * Variables expected:
 * - $visitors: Array of visitor records with unconfirmed status
 * - $baseUrl: Base URL for admin dashboard link
 * - $siteName: Name of the site/location
 */

if (!isset($visitors, $baseUrl)) {
    die('This template requires visitors and baseUrl variables');
}

$siteName = $siteName ?? 'SAP Office';

/**
 * Format duration in minutes to human-readable string
 */
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fin de Journée - Visites Non Confirmées</title>
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
            max-width: 700px;
            margin: 0 auto;
            background-color: #FFFFFF;
        }
        .header {
            background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #FFFFFF;
            margin: 0;
            font-size: 22px;
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
        .alert-box {
            background-color: #FFEBEE;
            border-left: 4px solid #E74C3C;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        .info-box {
            background-color: #E3F2FD;
            border-left: 4px solid #008FD3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th {
            background-color: #008FD3;
            color: #FFFFFF;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #EEEEEE;
        }
        tr:nth-child(even) {
            background-color: #FAFAFA;
        }
        tr:hover {
            background-color: #F0F8FF;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-warning {
            background-color: #FFF3E0;
            color: #E65100;
        }
        .badge-danger {
            background-color: #FFEBEE;
            color: #C62828;
        }
        .summary {
            background-color: #F5F5F5;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: center;
        }
        .summary-number {
            font-size: 36px;
            font-weight: bold;
            color: #E74C3C;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SAP</div>
            <h1>Fin de Journée - Visites Non Confirmées</h1>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <div class="alert-box">
                <p style="margin: 0;">
                    <strong>Alerte de fin de journée :</strong> Les visites suivantes au site
                    <strong><?php echo htmlspecialchars($siteName); ?></strong> n'ont pas été confirmées comme terminées.
                </p>
            </div>

            <div class="summary">
                <div class="summary-number"><?php echo count($visitors); ?></div>
                <div>visiteur(s) non confirmé(s)</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Visiteur</th>
                        <th>Société</th>
                        <th>Hôte</th>
                        <th>Arrivée</th>
                        <th>Durée</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visitors as $visitor):
                        $arrivalTime = strtotime($visitor['arrival_time']);
                        $durationMinutes = (time() - $arrivalTime) / 60;
                        $duration = formatDuration($durationMinutes);
                        $isLongStay = $durationMinutes > 480; // More than 8 hours
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($visitor['company']); ?></td>
                        <td><?php echo htmlspecialchars($visitor['host_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('H:i', $arrivalTime); ?></td>
                        <td>
                            <span class="badge <?php echo $isLongStay ? 'badge-danger' : 'badge-warning'; ?>">
                                <?php echo $duration; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="info-box">
                <p style="margin: 0;">
                    <strong>Action requise :</strong> Merci de vérifier si ces personnes sont encore sur site
                    et de mettre à jour leur statut dans le système de gestion des visiteurs.
                </p>
            </div>

            <p style="text-align: center; margin: 30px 0;">
                <a href="<?php echo $baseUrl; ?>/admin/dashboard.php" class="button button-primary">
                    Accéder au tableau de bord
                </a>
            </p>
        </div>

        <div class="footer">
            <p><strong>Système de Gestion des Visiteurs SAP</strong></p>
            <p>Ce message a été envoyé automatiquement à la fin de la journée.</p>
            <p>&copy; <?php echo date('Y'); ?> SAP. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
