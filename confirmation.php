<?php
/**
 * Success/thank you pages for check-in and checkout
 * Supports French and English languages
 */

require_once __DIR__ . '/includes/db.php';

// Get parameters
$type = $_GET['type'] ?? 'checkin'; // 'checkin' or 'checkout'
$success = isset($_GET['success']) && $_GET['success'] === '1';
$visitorId = intval($_GET['visitor_id'] ?? 0);
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'fr';

if (!in_array($lang, ['fr', 'en'])) {
    $lang = 'fr';
}
setcookie('lang', $lang, time() + 86400 * 30, '/');

// Get visitor details if ID provided
$visitor = null;
if ($visitorId > 0 && $type === 'checkin') {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT first_name, last_name, company, qr_token, arrival_time, expected_duration
            FROM visitors
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$visitorId]);
        $visitor = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error fetching visitor: " . $e->getMessage());
    }
}

// Build QR URL
$qrUrl = '';
if ($visitor) {
    $qrUrl = SITE_URL . '/checkout.php?token=' . urlencode($visitor['qr_token']);
}

// Translations
$translations = [
    'fr' => [
        // Check-in success
        'checkin_title' => 'Enregistrement Réussi',
        'checkin_subtitle' => 'Bienvenue chez SAP',
        'save_qr_message' => 'Veuillez sauvegarder ce QR code pour votre départ',
        'qr_scan_instructions' => 'Scannez ce code au départ ou gardez-le sur votre téléphone',
        'visitor_info' => 'Informations visiteur',
        'name' => 'Nom',
        'company' => 'Société',
        'arrival' => 'Arrivée',
        'expected_duration' => 'Durée prévue',
        'hours' => 'heures',
        'print_qr' => 'Imprimer le QR code',
        'download_qr' => 'Télécharger le QR code',

        // Checkout success
        'checkout_title' => 'Au Revoir !',
        'checkout_subtitle' => 'Merci de votre visite',
        'checkout_message' => 'Vous avez été enregistré comme parti.',
        'safe_travels' => 'Bonne journée et bon retour !',

        // Common
        'back_home' => 'Retour à l\'accueil',
        'switch_lang' => 'English',
        'error_title' => 'Erreur',
        'error_message' => 'Une erreur s\'est produite. Veuillez réessayer.',
    ],
    'en' => [
        // Check-in success
        'checkin_title' => 'Check-in Successful',
        'checkin_subtitle' => 'Welcome to SAP',
        'save_qr_message' => 'Please save this QR code for your departure',
        'qr_scan_instructions' => 'Scan this code when leaving or keep it on your phone',
        'visitor_info' => 'Visitor Information',
        'name' => 'Name',
        'company' => 'Company',
        'arrival' => 'Arrival',
        'expected_duration' => 'Expected Duration',
        'hours' => 'hours',
        'print_qr' => 'Print QR Code',
        'download_qr' => 'Download QR Code',

        // Checkout success
        'checkout_title' => 'Goodbye!',
        'checkout_subtitle' => 'Thank you for visiting',
        'checkout_message' => 'You have been checked out successfully.',
        'safe_travels' => 'Have a great day and safe travels!',

        // Common
        'back_home' => 'Back to Home',
        'switch_lang' => 'Français',
        'error_title' => 'Error',
        'error_message' => 'An error occurred. Please try again.',
    ]
];

$t = $translations[$lang];

// Format duration
function formatDuration($minutes, $lang) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($lang === 'fr') {
        if ($hours > 0 && $mins > 0) {
            return "$hours h $mins min";
        } elseif ($hours > 0) {
            return "$hours h";
        } else {
            return "$mins min";
        }
    } else {
        if ($hours > 0 && $mins > 0) {
            return "$hours hr $mins min";
        } elseif ($hours > 0) {
            return "$hours hr";
        } else {
            return "$mins min";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $type === 'checkin' ? $t['checkin_title'] : $t['checkout_title']; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%);
            min-height: 100vh;
        }
        .success-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .qr-container {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            display: inline-block;
        }
        .success-icon {
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .btn-primary {
            background: linear-gradient(135deg, #008FD3 0%, #0066a6 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 143, 211, 0.3);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .info-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="text-white">
    <div class="success-container">
        <!-- Language Switcher -->
        <div class="flex justify-end mb-6">
            <a href="?type=<?php echo $type; ?>&success=<?php echo $success ? '1' : '0'; ?>&visitor_id=<?php echo $visitorId; ?>&lang=<?php echo $lang === 'fr' ? 'en' : 'fr'; ?>"
               class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition text-sm">
                <?php echo $t['switch_lang']; ?>
            </a>
        </div>

        <?php if (!$success): ?>
            <!-- Error State -->
            <div class="glass-panel rounded-2xl p-8 text-center">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-red-500/20 flex items-center justify-center">
                    <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold mb-4"><?php echo $t['error_title']; ?></h1>
                <p class="text-gray-300 mb-8"><?php echo $t['error_message']; ?></p>
                <a href="index.php" class="btn-primary inline-block px-8 py-4 rounded-xl font-semibold">
                    <?php echo $t['back_home']; ?>
                </a>
            </div>
        <?php elseif ($type === 'checkin'): ?>
            <!-- Check-in Success -->
            <div class="glass-panel rounded-2xl p-8">
                <!-- Success Header -->
                <div class="text-center mb-8">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-green-500/20 flex items-center justify-center success-icon">
                        <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold mb-2"><?php echo $t['checkin_title']; ?></h1>
                    <p class="text-xl text-blue-300"><?php echo $t['checkin_subtitle']; ?></p>
                </div>

                <?php if ($visitor): ?>
                    <!-- QR Code Section -->
                    <div class="text-center mb-8">
                        <p class="text-gray-300 mb-4"><?php echo $t['save_qr_message']; ?></p>

                        <div class="qr-container mb-4">
                            <div id="qrcode"></div>
                        </div>

                        <p class="text-sm text-gray-400"><?php echo $t['qr_scan_instructions']; ?></p>

                        <!-- QR Actions -->
                        <div class="flex justify-center gap-4 mt-6">
                            <button onclick="printQR()" class="btn-secondary px-4 py-2 rounded-lg flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                <?php echo $t['print_qr']; ?>
                            </button>
                            <button onclick="downloadQR()" class="btn-secondary px-4 py-2 rounded-lg flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <?php echo $t['download_qr']; ?>
                            </button>
                        </div>
                    </div>

                    <!-- Visitor Info -->
                    <div class="bg-white/5 rounded-xl p-6 mb-6">
                        <h3 class="text-lg font-semibold mb-4"><?php echo $t['visitor_info']; ?></h3>
                        <div class="info-row">
                            <span class="text-gray-400"><?php echo $t['name']; ?></span>
                            <span class="font-medium"><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="text-gray-400"><?php echo $t['company']; ?></span>
                            <span class="font-medium"><?php echo htmlspecialchars($visitor['company']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="text-gray-400"><?php echo $t['arrival']; ?></span>
                            <span class="font-medium"><?php echo date('H:i', strtotime($visitor['arrival_time'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="text-gray-400"><?php echo $t['expected_duration']; ?></span>
                            <span class="font-medium"><?php echo formatDuration($visitor['expected_duration'], $lang); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Back Button -->
                <a href="index.php" class="btn-primary block w-full text-center py-4 rounded-xl font-semibold">
                    <?php echo $t['back_home']; ?>
                </a>
            </div>
        <?php else: ?>
            <!-- Checkout Success -->
            <div class="glass-panel rounded-2xl p-8 text-center">
                <!-- Success Header -->
                <div class="mb-8">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-blue-500/20 flex items-center justify-center success-icon">
                        <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold mb-2"><?php echo $t['checkout_title']; ?></h1>
                    <p class="text-xl text-blue-300"><?php echo $t['checkout_subtitle']; ?></p>
                </div>

                <!-- Message -->
                <div class="bg-white/5 rounded-xl p-6 mb-8">
                    <p class="text-lg mb-2"><?php echo $t['checkout_message']; ?></p>
                    <p class="text-gray-400"><?php echo $t['safe_travels']; ?></p>
                </div>

                <!-- Back Button -->
                <a href="index.php" class="btn-primary block w-full text-center py-4 rounded-xl font-semibold">
                    <?php echo $t['back_home']; ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <footer class="mt-8 text-center text-gray-500 text-sm">
            <p>Système de gestion des visiteurs SAP &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <?php if ($type === 'checkin' && $visitor && $qrUrl): ?>
    <script>
        // Generate QR Code
        document.addEventListener('DOMContentLoaded', function() {
            const qrcode = new QRCode(document.getElementById('qrcode'), {
                text: '<?php echo $qrUrl; ?>',
                width: 256,
                height: 256,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        });

        // Print QR Code
        function printQR() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>QR Code - Visitor Pass</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            text-align: center;
                            padding: 40px;
                        }
                        .pass {
                            border: 2px solid #008FD3;
                            border-radius: 20px;
                            padding: 30px;
                            max-width: 400px;
                            margin: 0 auto;
                        }
                        .logo {
                            font-size: 24px;
                            font-weight: bold;
                            color: #008FD3;
                            margin-bottom: 20px;
                        }
                        .qr-container {
                            margin: 20px 0;
                        }
                        .info {
                            margin-top: 20px;
                            text-align: left;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            padding: 8px 0;
                            border-bottom: 1px solid #eee;
                        }
                        .label {
                            color: #666;
                        }
                        .value {
                            font-weight: bold;
                        }
                        .footer {
                            margin-top: 20px;
                            font-size: 12px;
                            color: #666;
                        }
                    </style>
                </head>
                <body>
                    <div class="pass">
                        <div class="logo"><?php echo SITE_NAME; ?></div>
                        <div class="qr-container">
                            <img src="${document.querySelector('#qrcode img').src}" alt="QR Code" style="width: 200px; height: 200px;">
                        </div>
                        <div class="info">
                            <div class="info-row">
                                <span class="label">Name:</span>
                                <span class="value"><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Company:</span>
                                <span class="value"><?php echo htmlspecialchars($visitor['company']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo date('d/m/Y'); ?></span>
                            </div>
                        </div>
                        <div class="footer">
                            Scan this QR code at checkout<br>
                            or visit: <?php echo SITE_URL; ?>/checkout.php
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Download QR Code
        function downloadQR() {
            const qrImage = document.querySelector('#qrcode img');
            if (qrImage) {
                const link = document.createElement('a');
                link.download = 'visitor-qr-<?php echo $visitorId; ?>.png';
                link.href = qrImage.src;
                link.click();
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>
