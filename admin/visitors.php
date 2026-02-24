<?php
/**
 * Visitors List - Admin
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

// Get filter parameters
$view = $_GET['view'] ?? 'today';
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

// Build query
$params = [];
$where = [];

if ($view === 'now') {
    $where[] = "v.status = 'checked_in'";
} elseif ($view === 'today') {
    $where[] = "DATE(v.arrival_time) = ?";
    $params[] = date('Y-m-d');
} else {
    if ($date_from) {
        $where[] = "DATE(v.arrival_time) >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $where[] = "DATE(v.arrival_time) <= ?";
        $params[] = $date_to;
    }
}

if ($status) {
    $where[] = "v.status = ?";
    $params[] = $status;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get visitors
$visitors = $db->fetchAll(
    "SELECT v.*, h.name as host_name
     FROM visitors v
     LEFT JOIN hosts h ON v.host_email = h.email
     {$where_clause}
     ORDER BY v.arrival_time DESC",
    $params
);

// Manual checkout action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_visitor'])) {
    $visitor_id = (int)$_POST['visitor_id'];

    $db->execute(
        "UPDATE visitors SET status = 'checked_out', departure_time = NOW(), checkout_method = 'manual_admin'
         WHERE id = ? AND status = 'checked_in'",
        [$visitor_id]
    );

    log_audit('manual_checkout', ['message' => "Manual checkout for visitor ID {$visitor_id}", 'ip' => $_SERVER['REMOTE_ADDR']], $visitor_id);

    header('Location: visitors.php?view=' . $view);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visiteurs - Gestion des Visiteurs</title>
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
                <a href="visitors.php" class="block px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium">Visiteurs</a>
                <a href="hosts.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Hôtes</a>
                <a href="export.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Export CSV</a>
                <a href="settings.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Paramètres</a>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <div class="bg-white rounded-xl shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-bold">Liste des Visiteurs</h2>

                    <!-- Filters -->
                    <div class="mt-4 flex flex-wrap gap-4">
                        <a href="?view=now" class="px-4 py-2 <?= $view === 'now' ? 'bg-green-600 text-white' : 'bg-gray-200' ?> rounded-lg">
                            🟢 Sur site maintenant
                        </a>
                        <a href="?view=today" class="px-4 py-2 <?= $view === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200' ?> rounded-lg">
                            Aujourd'hui
                        </a>
                        <a href="?view=all" class="px-4 py-2 <?= $view === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200' ?> rounded-lg">
                            Tout
                        </a>
                    </div>

                    <?php if ($view === 'all'): ?>
                    <form method="GET" class="mt-4 flex gap-4">
                        <input type="hidden" name="view" value="all">
                        <input type="date" name="date_from" value="<?= $date_from ?>" class="border rounded-lg px-3 py-2">
                        <input type="date" name="date_to" value="<?= $date_to ?>" class="border rounded-lg px-3 py-2">
                        <select name="status" class="border rounded-lg px-3 py-2">
                            <option value="">Tous statuts</option>
                            <option value="checked_in" <?= $status === 'checked_in' ? 'selected' : '' ?>>Sur site</option>
                            <option value="checked_out" <?= $status === 'checked_out' ? 'selected' : '' ?>>Parti</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Filtrer</button>
                    </form>
                    <?php endif; ?>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Société</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hôte</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motif</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Arrivée</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Départ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($visitors as $v): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    <?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']) ?>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars($v['company']) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($v['host_name'] ?: $v['host_email']) ?></td>
                                <td class="px-4 py-3 text-sm"><?= htmlspecialchars($v['reason']) ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?= date('d/m/Y H:i', strtotime($v['arrival_time'])) ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?= $v['departure_time'] ? date('d/m/Y H:i', strtotime($v['departure_time'])) : '-' ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($v['status'] === 'checked_in'): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Sur site</span>
                                    <?php elseif ($v['status'] === 'checked_out'): ?>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Parti</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs"><?= $v['status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($v['status'] === 'checked_in'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Confirmer le départ ?')">
                                        <input type="hidden" name="visitor_id" value="<?= $v['id'] ?>">
                                        <button type="submit" name="checkout_visitor" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">
                                            Checkout
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($visitors)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">Aucun visiteur trouvé</td>
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
