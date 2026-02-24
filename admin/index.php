<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '', 'string');
    $password = $_POST['password'] ?? '';

    // Simple admin authentication (in production, use proper password hashing)
    $admin_username = getenv('ADMIN_USERNAME') ?: 'admin';
    $admin_password = getenv('ADMIN_PASSWORD') ?: 'changeme';

    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_role'] = 'admin';

        // Log login
        log_audit('admin_login', ['message' => "User {$username} logged in", 'ip' => $_SERVER['REMOTE_ADDR']]);

        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Identifiants invalides';
        log_audit('admin_login_failed', ['message' => "Failed login attempt for {$username}", 'ip' => $_SERVER['REMOTE_ADDR']]);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des Visiteurs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #1e3a5f 0%, #0d1f33 100%); min-height: 100vh; }
    </style>
</head>
<body class="flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Administration</h1>
            <p class="text-gray-600">Gestion des Visiteurs SAP</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Nom d'utilisateur</label>
                <input type="text" name="username" required
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Mot de passe</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                Connexion
            </button>
        </form>
    </div>
</body>
</html>
