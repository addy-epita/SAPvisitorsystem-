<?php
/**
 * SAP Visitor Management System - Kiosk Main Page
 * Touch-friendly interface for check-in/check-out
 */

// Start session for language persistence
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr';
}

// Page metadata
$pageTitle = 'SAP Visitor Management';

// Start output buffering for layout
ob_start();
?>

<!-- Main Kiosk Interface -->
<div class="flex-1 flex flex-col items-center justify-center px-8 py-16 pb-24">

    <!-- Welcome Header -->
    <div class="text-center mb-16 animate-fade-in">
        <div class="inline-flex items-center justify-center w-32 h-32 bg-sap-blue rounded-3xl shadow-xl mb-8">
            <span class="text-white font-bold text-5xl">SAP</span>
        </div>
        <h1 class="text-kiosk-display text-sap-gray-dark mb-4" data-i18n="welcome">
            Bienvenue chez SAP
        </h1>
        <p class="text-kiosk-heading text-gray-500" data-i18n="instruction">
            Veuillez sélectionner votre action
        </p>
    </div>

    <!-- Action Tiles -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-5xl">

        <!-- Check-in Tile -->
        <a href="checkin.php"
           class="kiosk-tile kiosk-tile-arrival group"
           aria-label="Check-in - Register arrival">

            <!-- Icon -->
            <svg class="kiosk-tile-icon transition-transform group-hover:scale-110"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>

            <!-- Title -->
            <span class="kiosk-tile-title" data-i18n="arrival">Arrivée</span>

            <!-- Subtitle -->
            <span class="kiosk-tile-subtitle">Check-in</span>
        </a>

        <!-- Check-out Tile -->
        <a href="checkout.php"
           class="kiosk-tile kiosk-tile-departure group"
           aria-label="Check-out - Register departure">

            <!-- Icon -->
            <svg class="kiosk-tile-icon transition-transform group-hover:scale-110"
                 xmlns="http://www.w3.org/2000/svg"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>

            <!-- Title -->
            <span class="kiosk-tile-title" data-i18n="departure">Sortie</span>

            <!-- Subtitle -->
            <span class="kiosk-tile-subtitle">Check-out</span>
        </a>

    </div>

    <!-- Help Text -->
    <div class="mt-16 text-center">
        <p class="text-kiosk-body text-gray-400">
            <span data-i18n="needHelp">Besoin d'aide ?</span>
            <br>
            <span class="text-kiosk-small">Contactez la réception</span>
        </p>
    </div>

</div>

<?php
// Get content from buffer
$content = ob_get_clean();

// Include layout
require_once 'templates/layout/kiosk.php';
