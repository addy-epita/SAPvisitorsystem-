<?php
/**
 * Admin Dashboard
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

// Get today's stats
$today = date('Y-m-d');
$today_start = $today . ' 00:00:00';
$today_end = $today . ' 23:59:59';

// Visitors today
$visitors_today = $db->fetchColumn(
    "SELECT COUNT(*) FROM visitors WHERE arrival_time BETWEEN ? AND ?",
    [$today_start, $today_end]
);

// Currently on site
$currently_on_site = $db->fetchColumn(
    "SELECT COUNT(*) FROM visitors WHERE status = 'checked_in'"
);

// Average dwell time today
$avg_dwell = $db->fetchOne(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, arrival_time, departure_time)) as avg_minutes
     FROM visitors
     WHERE DATE(arrival_time) = ? AND departure_time IS NOT NULL",
    [$today]
);
$avg_dwell_minutes = round($avg_dwell['avg_minutes'] ?? 0);

// This week's stats
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_visitors = $db->fetchColumn(
    "SELECT COUNT(*) FROM visitors WHERE arrival_time >= ?",
    [$week_start . ' 00:00:00']
);

// Recent visitors
$recent_visitors = $db->fetchAll(
    "SELECT v.*, h.name as host_name
     FROM visitors v
     LEFT JOIN hosts h ON v.host_email = h.email
     WHERE DATE(v.arrival_time) = ?
     ORDER BY v.arrival_time DESC
     LIMIT 10",
    [$today]
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestion des Visiteurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
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
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md min-h-screen">
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="block px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium">Dashboard</a>
                <a href="visitors.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Visiteurs</a>
                <a href="hosts.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Hôtes</a>
                <a href="export.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Export CSV</a>
                <a href="settings.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Paramètres</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Aujourd'hui</p>
                            <p class="text-3xl font-bold text-blue-600"><?= $visitors_today ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">👥</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Sur site maintenant</p>
                            <p class="text-3xl font-bold text-green-600"><?= $currently_on_site ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">🟢</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Temps moyen</p>
                            <p class="text-3xl font-bold text-purple-600"><?= $avg_dwell_minutes ?>min</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">⏱️</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Cette semaine</p>
                            <p class="text-3xl font-bold text-orange-600"><?= $week_visitors ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">📅</div>
                    </div>
                </div>
            </div>

            <!-- Recent Visitors -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold">Visites récentes (aujourd'hui)</h2>
                    <a href="visitors.php" class="text-blue-600 hover:text-blue-800 text-sm">Voir tout →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Société</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hôte</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Arrivée</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($recent_visitors as $visitor): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($visitor['company']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($visitor['host_name'] ?: $visitor['host_email']) ?></td>
                                <td class="px-6 py-4"><?= date('H:i', strtotime($visitor['arrival_time'])) ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($visitor['status'] === 'checked_in'): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Sur site</span>
                                    <?php elseif ($visitor['status'] === 'checked_out'): ?>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Parti</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs"><?= $visitor['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_visitors)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucune visite aujourd'hui</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
