<?php
/**
 * Kiosk Layout Template
 * Layout wrapper for kiosk interface pages
 */
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#008FD3">
    <meta name="description" content="SAP Visitor Management System - Kiosk Interface">

    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'SAP Visitor Management'; ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config for SAP Colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        sap: {
                            blue: '#008FD3',
                            'blue-dark': '#0070A8',
                            'blue-light': '#4DB3E3',
                            gray: '#F5F5F5',
                            'gray-dark': '#333333',
                            white: '#FFFFFF',
                            black: '#000000',
                            success: '#107E3E',
                            warning: '#E9730C',
                            error: '#BB0000'
                        }
                    },
                    fontSize: {
                        'kiosk-xl': ['4rem', { lineHeight: '1.1' }],
                        'kiosk-lg': ['2.5rem', { lineHeight: '1.2' }],
                        'kiosk-md': ['1.75rem', { lineHeight: '1.3' }],
                        'kiosk-sm': ['1.25rem', { lineHeight: '1.4' }]
                    },
                    spacing: {
                        'kiosk': '3rem',
                        'touch': '48px'
                    },
                    minHeight: {
                        'touch': '48px'
                    }
                }
            }
        }
    </script>

    <!-- Custom Kiosk Styles -->
    <link rel="stylesheet" href="/assets/css/kiosk.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/logo.png">
</head>
<body class="bg-gray-100 font-sans antialiased overflow-hidden">

    <!-- Language Toggle -->
    <div class="fixed top-4 right-4 z-50">
        <button id="langToggle"
                class="bg-white/90 backdrop-blur-sm text-sap-blue-dark font-semibold px-6 py-3 rounded-full shadow-lg border-2 border-sap-blue hover:bg-sap-blue hover:text-white transition-all duration-200 text-kiosk-sm min-h-touch flex items-center gap-2"
                aria-label="Toggle language">
            <span id="langIcon">🇫🇷</span>
            <span id="langText">FR</span>
        </button>
    </div>

    <!-- Fullscreen Toggle -->
    <div class="fixed top-4 left-4 z-50">
        <button id="fullscreenToggle"
                class="bg-white/90 backdrop-blur-sm text-sap-gray-dark font-semibold px-6 py-3 rounded-full shadow-lg border-2 border-gray-300 hover:bg-sap-blue hover:text-white hover:border-sap-blue transition-all duration-200 text-kiosk-sm min-h-touch flex items-center gap-2"
                aria-label="Toggle fullscreen">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <span data-i18n="fullscreen">Plein écran</span>
        </button>
    </div>

    <!-- Main Content -->
    <main id="mainContent" class="min-h-screen flex flex-col">
        <?php echo $content; ?>
    </main>

    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-sm border-t border-gray-200 py-4 px-6">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sap-blue rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-xl">SAP</span>
                </div>
                <span class="text-sap-gray-dark font-medium text-kiosk-sm" data-i18n="siteName">Système de Gestion des Visiteurs</span>
            </div>
            <div class="text-gray-500 text-kiosk-sm" id="clock">
                --:--
            </div>
        </div>
    </footer>

    <!-- Kiosk JavaScript -->
    <script src="/assets/js/kiosk.js"></script>

</body>
</html>
