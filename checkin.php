<?php
/**
 * SAP Visitor Management System - Check-in Form
 * Touch-friendly form for visitor registration
 */

// Start session for language persistence
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr';
}

// Sample hosts data (in production, this would come from database)
// This can be replaced with: $hosts = require 'includes/db.php';
$hosts = [
    ['id' => 1, 'name' => 'Jean Dupont', 'email' => 'jean.dupont@sap.com', 'department' => 'IT'],
    ['id' => 2, 'name' => 'Marie Martin', 'email' => 'marie.martin@sap.com', 'department' => 'RH'],
    ['id' => 3, 'name' => 'Pierre Bernard', 'email' => 'pierre.bernard@sap.com', 'department' => 'Finance'],
    ['id' => 4, 'name' => 'Sophie Petit', 'email' => 'sophie.petit@sap.com', 'department' => 'Marketing'],
    ['id' => 5, 'name' => 'Lucas Moreau', 'email' => 'lucas.moreau@sap.com', 'department' => 'Sales'],
];

// Duration options
$durations = [
    120 => ['label_fr' => '2 heures', 'label_en' => '2 hours'],
    180 => ['label_fr' => '3 heures', 'label_en' => '3 hours'],
    240 => ['label_fr' => '4 heures', 'label_en' => '4 hours'],
    360 => ['label_fr' => '6 heures', 'label_en' => '6 hours'],
    480 => ['label_fr' => '8 heures', 'label_en' => '8 hours'],
];

// Page metadata
$pageTitle = $_SESSION['lang'] === 'fr' ? 'Enregistrement Visiteur' : 'Visitor Check-in';

// Start output buffering for layout
ob_start();
?>

<!-- Check-in Form Container -->
<div class="flex-1 flex flex-col px-8 py-8 pb-24 overflow-y-auto hide-scrollbar">

    <!-- Header -->
    <div class="text-center mb-8 animate-fade-in">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-sap-blue rounded-2xl shadow-lg mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </div>
        <h1 class="text-kiosk-title text-sap-gray-dark">
            <?php echo $_SESSION['lang'] === 'fr' ? 'Enregistrement Visiteur' : 'Visitor Check-in'; ?>
        </h1>
        <p class="text-kiosk-body text-gray-500 mt-2">
            <?php echo $_SESSION['lang'] === 'fr' ? 'Veuillez remplir le formulaire ci-dessous' : 'Please fill in the form below'; ?>
        </p>
    </div>

    <!-- Form -->
    <form action="api/checkin.php" method="POST" data-validate class="max-w-4xl mx-auto w-full space-y-6">

        <!-- Name Row -->
        <div class="form-row">
            <!-- First Name -->
            <div class="form-group">
                <label for="firstName" data-i18n="firstName">
                    Prénom
                </label>
                <input type="text"
                       id="firstName"
                       name="first_name"
                       required
                       minlength="2"
                       data-capitalize
                       data-i18n-placeholder="firstName"
                       placeholder="Prénom"
                       class="w-full"
                       autocomplete="given-name">
                <span class="error-message" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="error-text"></span>
                </span>
            </div>

            <!-- Last Name -->
            <div class="form-group">
                <label for="lastName" data-i18n="lastName">
                    Nom
                </label>
                <input type="text"
                       id="lastName"
                       name="last_name"
                       required
                       minlength="2"
                       data-capitalize
                       data-i18n-placeholder="lastName"
                       placeholder="Nom"
                       class="w-full"
                       autocomplete="family-name">
                <span class="error-message" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="error-text"></span>
                </span>
            </div>
        </div>

        <!-- Company -->
        <div class="form-group">
            <label for="company" data-i18n="company">
                Société
            </label>
            <input type="text"
                   id="company"
                   name="company"
                   required
                   minlength="2"
                   data-i18n-placeholder="company"
                   placeholder="Nom de la société"
                   class="w-full"
                   autocomplete="organization">
            <span class="error-message" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span class="error-text"></span>
            </span>
        </div>

        <!-- Reason for Visit -->
        <div class="form-group">
            <label for="reason" data-i18n="reason">
                Motif de visite
            </label>
            <input type="text"
                   id="reason"
                   name="reason"
                   required
                   minlength="3"
                   data-i18n-placeholder="reason"
                   placeholder="Ex: Réunion, Maintenance, Livraison..."
                   class="w-full">
            <span class="error-message" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span class="error-text"></span>
            </span>
        </div>

        <!-- Host Selection -->
        <div class="form-group">
            <label for="hostSelect" data-i18n="host">
                Hôte
            </label>
            <select id="hostSelect" name="host_select" class="w-full">
                <option value="" data-i18n="selectHost">
                    Sélectionnez un hôte...
                </option>
                <?php foreach ($hosts as $host): ?>
                    <option value="<?php echo htmlspecialchars($host['id']); ?>"
                            data-email="<?php echo htmlspecialchars($host['email']); ?>">
                        <?php echo htmlspecialchars($host['name']); ?>
                        (<?php echo htmlspecialchars($host['department']); ?>)
                    </option>
                <?php endforeach; ?>
                <option value="other" data-i18n="otherHost">
                    Autre (saisir email)
                </option>
            </select>
        </div>

        <!-- Host Email -->
        <div class="form-group">
            <label for="hostEmail" data-i18n="hostEmail">
                Email de l'hôte
            </label>
            <input type="email"
                   id="hostEmail"
                   name="host_email"
                   required
                   data-i18n-placeholder="hostEmail"
                   placeholder="email@sap.com"
                   class="w-full"
                   autocomplete="email">
            <span class="error-message" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span class="error-text"></span>
            </span>
        </div>

        <!-- Visitor Email (Optional) -->
        <div class="form-group">
            <label for="visitorEmail" data-i18n="visitorEmail">
                Email visiteur (optionnel)
            </label>
            <input type="email"
                   id="visitorEmail"
                   name="visitor_email"
                   data-i18n-placeholder="visitorEmail"
                   placeholder="votre@email.com"
                   class="w-full"
                   autocomplete="email">
            <span class="error-message" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span class="error-text"></span>
            </span>
        </div>

        <!-- Expected Duration -->
        <div class="form-group">
            <label for="duration" data-i18n="duration">
                Durée prévue
            </label>
            <select id="duration" name="expected_duration" class="w-full">
                <?php foreach ($durations as $minutes => $labels): ?>
                    <option value="<?php echo $minutes; ?>" <?php echo $minutes === 180 ? 'selected' : ''; ?>>
                        <?php echo $_SESSION['lang'] === 'fr' ? $labels['label_fr'] : $labels['label_en']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6">
            <a href="index.php"
               class="btn-kiosk btn-kiosk-secondary flex-1 order-2 sm:order-1"
               data-i18n="cancel">
                Annuler
            </a>
            <button type="submit"
                    class="btn-kiosk btn-kiosk-primary flex-1 order-1 sm:order-2"
                    data-i18n="submit">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Valider
            </button>
        </div>

    </form>

</div>

<?php
// Get content from buffer
$content = ob_get_clean();

// Include layout
require_once 'templates/layout/kiosk.php';
