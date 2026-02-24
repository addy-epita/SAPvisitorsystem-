<?php
/**
 * CSV Export - Admin
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$db = new Database();

// Export action
if (isset($_GET['export'])) {
    $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $date_to = $_GET['date_to'] ?? date('Y-m-d');

    $visitors = $db->fetchAll(
        "SELECT v.*, h.name as host_name,
                TIMESTAMPDIFF(MINUTE, v.arrival_time, v.departure_time) as duration_minutes
         FROM visitors v
         LEFT JOIN hosts h ON v.host_email = h.email
         WHERE DATE(v.arrival_time) BETWEEN ? AND ?
         ORDER BY v.arrival_time DESC",
        [$date_from, $date_to]
    );

    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=visiteurs_' . $date_from . '_to_' . $date_to . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    // Headers
    fputcsv($output, ['Date', 'Nom', 'Prénom', 'Société', 'Hôte', 'Email Hôte', 'Motif', 'Arrivée', 'Départ', 'Durée (min)', 'Statut', 'Méthode']);

    foreach ($visitors as $v) {
        fputcsv($output, [
            date('d/m/Y', strtotime($v['arrival_time'])),
            $v['last_name'],
            $v['first_name'],
            $v['company'],
            $v['host_name'] ?: '',
            $v['host_email'],
            $v['reason'],
            date('H:i', strtotime($v['arrival_time'])),
            $v['departure_time'] ? date('H:i', strtotime($v['departure_time'])) : '',
            $v['duration_minutes'] ?: '',
            $v['status'],
            $v['checkin_method']
        ]);
    }

    fclose($output);
    log_audit('export_csv', "Exported {$date_from} to {$date_to}", null, $_SERVER['REMOTE_ADDR']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export - Gestion des Visiteurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-slate-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">Gestion des Visiteurs SAP</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm"><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm">Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="flex">
        <aside class="w-64 bg-white shadow-md min-h-screen">
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Dashboard</a>
                <a href="visitors.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Visiteurs</a>
                <a href="hosts.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Hôtes</a>
                <a href="export.php" class="block px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium">Export CSV</a>
                <a href="settings.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Paramètres</a>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md p-8">
                <h2 class="text-2xl font-bold mb-6">Export CSV</h2>

                <form method="GET" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                            <input type="date" name="date_from" value="<?= date('Y-m-d', strtotime('-30 days')) ?>"
                                class="w-full border rounded-lg px-4 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                            <input type="date" name="date_to" value="<?= date('Y-m-d') ?>"
                                class="w-full border rounded-lg px-4 py-2">
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-blue-800">
                            💡 L'export comprend: Date, Nom, Société, Hôte, Motif, Heures d'arrivée/départ, Durée, Statut
                        </p>
                    </div>

                    <button type="submit" name="export" value="1"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg">
                        📥 Télécharger CSV
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
