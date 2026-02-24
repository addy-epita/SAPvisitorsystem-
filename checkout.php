<?php
/**
 * Checkout page with QR scanner and manual entry
 * Supports French and English languages
 */

require_once __DIR__ . '/includes/db.php';

// Get language preference (default to French)
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'fr';
if (!in_array($lang, ['fr', 'en'])) {
    $lang = 'fr';
}
setcookie('lang', $lang, time() + 86400 * 30, '/');

// Get token from URL if present (for direct QR scan)
$token = $_GET['token'] ?? '';

// Translations
$translations = [
    'fr' => [
        'title' => 'Départ Visiteur',
        'checkout' => 'Sortie',
        'scan_qr' => 'Scannez votre QR code',
        'or_manual' => 'Ou saisie manuelle',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'company' => 'Société',
        'find_visit' => 'Rechercher ma visite',
        'processing' => 'Traitement en cours...',
        'camera_error' => 'Erreur caméra. Veuillez utiliser la saisie manuelle.',
        'back' => 'Retour',
        'switch_lang' => 'English',
        'help_text' => 'Présentez votre QR code devant la caméra ou saisissez vos informations',
        'start_scanner' => 'Démarrer le scan',
        'stop_scanner' => 'Arrêter le scan',
    ],
    'en' => [
        'title' => 'Visitor Checkout',
        'checkout' => 'Checkout',
        'scan_qr' => 'Scan your QR code',
        'or_manual' => 'Or manual entry',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'company' => 'Company',
        'find_visit' => 'Find my visit',
        'processing' => 'Processing...',
        'camera_error' => 'Camera error. Please use manual entry.',
        'back' => 'Back',
        'switch_lang' => 'Français',
        'help_text' => 'Present your QR code to the camera or enter your details',
        'start_scanner' => 'Start Scanner',
        'stop_scanner' => 'Stop Scanner',
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%);
            min-height: 100vh;
        }
        .kiosk-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .scanner-container {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            background: #000;
        }
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #reader video {
            border-radius: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #008FD3 0%, #0066a6 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 143, 211, 0.3);
        }
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(0, 143, 211, 0.3);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes pulse-border {
            0%, 100% { border-color: rgba(0, 143, 211, 0.5); }
            50% { border-color: rgba(0, 143, 211, 1); }
        }
        .scanning-active {
            animation: pulse-border 2s infinite;
        }
    </style>
</head>
<body class="text-white">
    <div class="kiosk-container">
        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold"><?php echo $t['checkout']; ?></h1>
                    <p class="text-gray-400 text-sm"><?php echo SITE_NAME; ?></p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="?lang=<?php echo $lang === 'fr' ? 'en' : 'fr'; ?>"
                   class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition text-sm">
                    <?php echo $t['switch_lang']; ?>
                </a>
                <a href="index.php" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition text-sm">
                    <?php echo $t['back']; ?>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <!-- QR Scanner Section -->
            <div class="glass-panel rounded-2xl p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                    <?php echo $t['scan_qr']; ?>
                </h2>

                <p class="text-gray-300 mb-4"><?php echo $t['help_text']; ?></p>

                <!-- Scanner Controls -->
                <div class="flex justify-center gap-4 mb-4">
                    <button id="startScanner" class="btn-primary px-6 py-3 rounded-xl font-semibold flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <?php echo $t['start_scanner']; ?>
                    </button>
                    <button id="stopScanner" class="hidden px-6 py-3 rounded-xl font-semibold bg-red-500 hover:bg-red-600 transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <?php echo $t['stop_scanner']; ?>
                    </button>
                </div>

                <!-- QR Scanner Container -->
                <div id="scannerContainer" class="scanner-container hidden">
                    <div id="reader"></div>
                </div>

                <!-- Status Message -->
                <div id="scannerStatus" class="text-center mt-4 hidden">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-500/20 text-blue-300">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span><?php echo $t['processing']; ?></span>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="scannerError" class="hidden mt-4 p-4 rounded-lg bg-red-500/20 text-red-300 text-center">
                    <?php echo $t['camera_error']; ?>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-4 mb-6">
                <div class="flex-1 h-px bg-white/20"></div>
                <span class="text-gray-400"><?php echo $t['or_manual']; ?></span>
                <div class="flex-1 h-px bg-white/20"></div>
            </div>

            <!-- Manual Entry Form -->
            <div class="glass-panel rounded-2xl p-6">
                <form id="manualCheckoutForm" class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <?php echo $t['first_name']; ?>
                            </label>
                            <input type="text" name="first_name" required
                                   class="input-field w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-500 focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <?php echo $t['last_name']; ?>
                            </label>
                            <input type="text" name="last_name" required
                                   class="input-field w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-500 focus:outline-none focus:border-blue-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <?php echo $t['company']; ?>
                        </label>
                        <input type="text" name="company" required
                               class="input-field w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-500 focus:outline-none focus:border-blue-400">
                    </div>
                    <button type="submit" class="btn-primary w-full py-4 rounded-xl font-semibold text-lg">
                        <?php echo $t['find_visit']; ?>
                    </button>
                </form>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-8 text-center text-gray-500 text-sm">
            <p>Système de gestion des visiteurs SAP &copy; <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script src="assets/js/qr-scanner.js"></script>
    <script>
        // Initialize QR Scanner
        document.addEventListener('DOMContentLoaded', function() {
            const qrScanner = new CheckoutQRScanner({
                readerId: 'reader',
                onScanSuccess: function(decodedText) {
                    handleToken(decodedText);
                },
                onScanError: function(error) {
                    console.warn('QR scan error:', error);
                }
            });

            // Start scanner button
            document.getElementById('startScanner').addEventListener('click', function() {
                document.getElementById('scannerContainer').classList.remove('hidden');
                document.getElementById('startScanner').classList.add('hidden');
                document.getElementById('stopScanner').classList.remove('hidden');
                qrScanner.start();
            });

            // Stop scanner button
            document.getElementById('stopScanner').addEventListener('click', function() {
                qrScanner.stop();
                document.getElementById('scannerContainer').classList.add('hidden');
                document.getElementById('startScanner').classList.remove('hidden');
                document.getElementById('stopScanner').classList.add('hidden');
            });

            // Manual form submission
            document.getElementById('manualCheckoutForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                // Show processing
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="inline-flex items-center gap-2"><?php echo $t['processing']; ?></span>';

                // Find visitor by name and company
                fetch('api/checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        first_name: formData.get('first_name'),
                        last_name: formData.get('last_name'),
                        company: formData.get('company'),
                        method: 'manual'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'confirmation.php?type=checkout&success=1&lang=<?php echo $lang; ?>';
                    } else {
                        alert(data.message || 'Error processing checkout');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
            });

            // Handle token from QR code
            function handleToken(token) {
                document.getElementById('scannerStatus').classList.remove('hidden');

                // Extract token from URL if full URL was scanned
                let cleanToken = token;
                if (token.includes('?token=')) {
                    const url = new URL(token);
                    const params = new URLSearchParams(url.search);
                    cleanToken = params.get('token');
                }

                fetch('api/checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        qr_token: cleanToken,
                        method: 'qr_rescan'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'confirmation.php?type=checkout&success=1&lang=<?php echo $lang; ?>';
                    } else {
                        alert(data.message || 'Invalid or expired QR code');
                        document.getElementById('scannerStatus').classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    document.getElementById('scannerStatus').classList.add('hidden');
                });
            }

            // If token is in URL, process it immediately
            <?php if ($token): ?>
            handleToken('<?php echo htmlspecialchars($token); ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>
