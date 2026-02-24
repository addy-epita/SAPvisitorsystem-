<?php
/**
 * Configuration File
 *
 * Central configuration for the Visitor Management System.
 * Copy this file to config.php and update with your actual values.
 */

// Prevent direct access
if (!defined('VISITOR_SYSTEM')) {
    define('VISITOR_SYSTEM', true);
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'visitor_system');
define('DB_USER', $_ENV['DB_USER'] ?? 'visitor_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'your_secure_password');
define('DB_CHARSET', 'utf8mb4');

// Microsoft Graph API Configuration
// These should be set via environment variables for security
define('MS_GRAPH_TENANT_ID', $_ENV['MS_GRAPH_TENANT_ID'] ?? '');
define('MS_GRAPH_CLIENT_ID', $_ENV['MS_GRAPH_CLIENT_ID'] ?? '');
define('MS_GRAPH_CLIENT_SECRET', $_ENV['MS_GRAPH_CLIENT_SECRET'] ?? '');
define('MS_GRAPH_FROM_EMAIL', $_ENV['MS_GRAPH_FROM_EMAIL'] ?? 'visitors@yourdomain.com');
define('MS_GRAPH_FROM_NAME', $_ENV['MS_GRAPH_FROM_NAME'] ?? 'SAP Visitor System');

// Application Settings
define('BASE_URL', $_ENV['BASE_URL'] ?? 'https://visitors.yourdomain.com');
define('SITE_URL', $_ENV['BASE_URL'] ?? 'https://visitors.yourdomain.com'); // Alias for compatibility
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'SAP Office');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'Europe/Paris');

// Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email Settings
define('EMAIL_ENABLED', filter_var($_ENV['EMAIL_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('REMINDER_INTERVALS', [120, 240, 360, 480]); // Minutes: 2h, 4h, 6h, 8h
define('END_OF_DAY_TIME', '18:00');
define('ESCALATION_EMAILS', $_ENV['ESCALATION_EMAILS'] ?? ''); // Comma-separated list

// QR Code Settings
define('QR_CODE_SIZE', 300);
define('QR_CODE_MARGIN', 10);

// GDPR Settings
define('DATA_RETENTION_DAYS', 365);
define('ANONYMIZE_AFTER_DAYS', 365);

// Paths
define('LOG_PATH', __DIR__ . '/../logs');
define('UPLOAD_PATH', __DIR__ . '/../uploads');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (disable in production)
if ($_ENV['ENVIRONMENT'] ?? 'production' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
