<?php
/**
 * SAP Visitor Management System - Configuration File
 *
 * This file contains all system configuration settings.
 * Sensitive values should be loaded from environment variables.
 *
 * @package SAPVisitorSystem
 * @author SAP Visitor System
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// =============================================================================
// ENVIRONMENT CONFIGURATION
// =============================================================================

// Load .env file if it exists
$envFile = APP_ROOT . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            // Only set if not already defined in environment
            if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// =============================================================================
// ENVIRONMENT SETTINGS
// =============================================================================

// Application environment: development, staging, production
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Debug mode - NEVER enable in production!
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));

// Application version
define('APP_VERSION', '1.0.0');
define('APP_NAME', 'SAP Visitor Management System');
define('APP_URL', getenv('APP_URL') ?: 'https://visitors.sap.example.com');

// =============================================================================
// DATABASE CONFIGURATION
// =============================================================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', (int)(getenv('DB_PORT') ?: '3306'));
define('DB_NAME', getenv('DB_NAME') ?: 'visitor_management');
define('DB_USER', getenv('DB_USER') ?: 'visitor_app');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Connection pool settings
define('DB_PERSISTENT', filter_var(getenv('DB_PERSISTENT') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('DB_TIMEOUT', (int)(getenv('DB_TIMEOUT') ?: '30'));

// =============================================================================
// MICROSOFT GRAPH API CONFIGURATION
// =============================================================================

define('GRAPH_API_ENABLED', filter_var(getenv('GRAPH_API_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('GRAPH_TENANT_ID', getenv('GRAPH_TENANT_ID') ?: '');
define('GRAPH_CLIENT_ID', getenv('GRAPH_CLIENT_ID') ?: '');
define('GRAPH_CLIENT_SECRET', getenv('GRAPH_CLIENT_SECRET') ?: '');

// Graph API scopes needed
define('GRAPH_SCOPES', 'https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/User.Read');

// Token endpoint
define('GRAPH_TOKEN_URL', 'https://login.microsoftonline.com/' . GRAPH_TENANT_ID . '/oauth2/v2.0/token');
define('GRAPH_API_URL', 'https://graph.microsoft.com/v1.0');

// =============================================================================
// EMAIL CONFIGURATION (SMTP Fallback)
// =============================================================================

define('SMTP_ENABLED', filter_var(getenv('SMTP_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.office365.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: '587'));
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls'); // tls, ssl, or none
define('SMTP_AUTH', filter_var(getenv('SMTP_AUTH') ?: 'true', FILTER_VALIDATE_BOOLEAN));

// Default email settings
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: 'SAP Visitor System');
define('EMAIL_FROM_ADDRESS', getenv('EMAIL_FROM_ADDRESS') ?: 'visitors@sap.example.com');
define('EMAIL_REPLY_TO', getenv('EMAIL_REPLY_TO') ?: '');

// =============================================================================
// SECURITY SETTINGS
// =============================================================================

// Session configuration
define('SESSION_NAME', 'SAPVisitorSession');
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: '28800')); // 8 hours in seconds
define('SESSION_SECURE', filter_var(getenv('SESSION_SECURE') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('SESSION_HTTP_ONLY', true);
define('SESSION_SAMESITE', 'Strict');

// CSRF protection
define('CSRF_TOKEN_NAME', '_csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// Password hashing
define('PASSWORD_COST', (int)(getenv('PASSWORD_COST') ?: '12'));

// Rate limiting
define('RATE_LIMIT_ENABLED', filter_var(getenv('RATE_LIMIT_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('RATE_LIMIT_REQUESTS', (int)(getenv('RATE_LIMIT_REQUESTS') ?: '100'));
define('RATE_LIMIT_WINDOW', (int)(getenv('RATE_LIMIT_WINDOW') ?: '60')); // seconds

// Encryption key for sensitive data (generate with: openssl rand -base64 32)
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: '');

// =============================================================================
// SITE SETTINGS
// =============================================================================

// Default timezone
define('DEFAULT_TIMEZONE', getenv('DEFAULT_TIMEZONE') ?: 'Europe/Paris');

// Default locale
define('DEFAULT_LOCALE', getenv('DEFAULT_LOCALE') ?: 'fr_FR');

// Available languages
define('AVAILABLE_LOCALES', ['fr_FR' => 'Français', 'en_US' => 'English']);

// Date/time formats
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// =============================================================================
// VISITOR MANAGEMENT SETTINGS
// =============================================================================

// Default visit duration in minutes (3 hours)
define('DEFAULT_VISIT_DURATION', 180);

// Reminder intervals in minutes (2h, 4h, 6h, 8h)
define('REMINDER_INTERVALS', [120, 240, 360, 480]);

// End of business day (24h format)
define('END_OF_DAY_TIME', '18:00');

// Data retention period in days (GDPR)
define('DATA_RETENTION_DAYS', 365);

// QR code settings
define('QR_CODE_SIZE', 300);
define('QR_CODE_VALIDITY_HOURS', 24);
define('QR_CODE_ECC_LEVEL', 'M'); // L, M, Q, H

// =============================================================================
// KIOSK SETTINGS
// =============================================================================

define('KIOSK_AUTO_REDIRECT_SECONDS', 30);
define('KIOSK_IDLE_TIMEOUT_MINUTES', 5);
define('KIOSK_SCREEN_WIDTH', 1920);
define('KIOSK_SCREEN_HEIGHT', 1080);

// =============================================================================
// PATH CONFIGURATION
// =============================================================================

// Base paths
define('INCLUDES_PATH', APP_ROOT . '/includes');
define('TEMPLATES_PATH', APP_ROOT . '/templates');
define('ASSETS_PATH', APP_ROOT . '/assets');
define('CACHE_PATH', APP_ROOT . '/cache');
define('LOGS_PATH', APP_ROOT . '/logs');

// URL paths
define('ASSETS_URL', '/assets');
define('API_URL', '/api');
define('ADMIN_URL', '/admin');

// =============================================================================
// ERROR HANDLING
// =============================================================================

// Error reporting based on environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

// Log errors to file
ini_set('log_errors', '1');
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// =============================================================================
// TIMEZONE AND LOCALE SETUP
// =============================================================================

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Set locale for string functions
setlocale(LC_ALL, DEFAULT_LOCALE . '.UTF-8', DEFAULT_LOCALE, 'fr_FR', 'fr', 'en_US', 'en');

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Check if running in CLI mode
 *
 * @return bool
 */
function isCli(): bool
{
    return php_sapi_name() === 'cli' || defined('STDIN');
}

/**
 * Get configuration value with fallback
 *
 * @param string $key Configuration key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function config(string $key, $default = null)
{
    if (defined($key)) {
        return constant($key);
    }
    return $default;
}

/**
 * Check if HTTPS is required and enforce it
 *
 * @return void
 */
function enforceHttps(): void
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $_SERVER['HTTPS'] = 'on';
    }

    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        if (!APP_DEBUG && filter_var(getenv('REQUIRE_HTTPS') ?: 'true', FILTER_VALIDATE_BOOLEAN)) {
            $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}

// Enforce HTTPS for web requests
if (!isCli()) {
    enforceHttps();
}
