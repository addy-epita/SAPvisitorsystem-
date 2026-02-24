<?php
/**
 * SAP Visitor Management System - Helper Functions
 *
 * Utility functions used throughout the application.
 *
 * @package SAP_Visitor_System
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';

// --------------------------------------------------------
// Input Sanitization and Validation
// --------------------------------------------------------

/**
 * Sanitize user input to prevent XSS and injection attacks
 *
 * @param mixed $input Input to sanitize
 * @param string $type Type of sanitization: 'string', 'email', 'int', 'float', 'html', 'url'
 * @return mixed Sanitized input
 */
function sanitize_input(mixed $input, string $type = 'string'): mixed {
    if ($input === null) {
        return null;
    }

    if (is_array($input)) {
        return array_map(fn($item) => sanitize_input($item, $type), $input);
    }

    return match ($type) {
        'string' => htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8'),
        'email' => filter_var(trim((string)$input), FILTER_SANITIZE_EMAIL),
        'int' => filter_var($input, FILTER_VALIDATE_INT),
        'float' => filter_var($input, FILTER_VALIDATE_FLOAT),
        'bool' => filter_var($input, FILTER_VALIDATE_BOOLEAN),
        'html' => strip_tags((string)$input, '<p><br><strong><em><ul><ol><li>'),
        'url' => filter_var(trim((string)$input), FILTER_SANITIZE_URL),
        'alpha' => preg_replace('/[^a-zA-Z\s]/', '', (string)$input),
        'alphanumeric' => preg_replace('/[^a-zA-Z0-9\s]/', '', (string)$input),
        'numeric' => preg_replace('/[^0-9]/', '', (string)$input),
        default => htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8')
    };
}

/**
 * Validate email address format
 *
 * @param string $email
 * @return bool
 */
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate that a string is not empty after trimming
 *
 * @param string $string
 * @return bool
 */
function is_not_empty(string $string): bool {
    return strlen(trim($string)) > 0;
}

/**
 * Validate date string format
 *
 * @param string $date
 * @param string $format
 * @return bool
 */
function is_valid_date(string $date, string $format = 'Y-m-d H:i:s'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// --------------------------------------------------------
// QR Code and Token Generation
// --------------------------------------------------------

/**
 * Generate a unique QR token for visitor checkout
 *
 * @param int $length Token length (default 32)
 * @return string
 * @throws Exception
 */
function generate_qr_token(int $length = 32): string {
    // Use cryptographically secure random bytes
    $bytes = random_bytes($length);
    // Convert to URL-safe base64
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/**
 * Generate a random secure token
 *
 * @param int $length
 * @return string
 * @throws Exception
 */
function generate_secure_token(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate a CSRF token
 *
 * @return string
 * @throws Exception
 */
function generate_csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = generate_secure_token();
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 *
 * @param string $token
 * @return bool
 */
function validate_csrf_token(string $token): bool {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }

    // Check token expiry
    if (!empty($_SESSION[CSRF_TOKEN_NAME . '_time'])) {
        if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_TIME) {
            return false;
        }
    }

    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Generate QR code URL
 *
 * @param string $token
 * @param string $type Type of QR code: 'checkout', 'checkin', 'info'
 * @return string
 */
function generate_qr_url(string $token, string $type = 'checkout'): string {
    return SITE_URL . '/qr.php?type=' . $type . '&token=' . urlencode($token);
}

// --------------------------------------------------------
// Date and Time Formatting
// --------------------------------------------------------

/**
 * Format a duration in minutes to human-readable string
 *
 * @param int $minutes Duration in minutes
 * @param string $language Language: 'fr' or 'en'
 * @return string
 */
function format_duration(int $minutes, string $language = 'fr'): string {
    if ($minutes < 0) {
        $minutes = 0;
    }

    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($language === 'fr') {
        if ($hours === 0) {
            return $mins . ' minute' . ($mins > 1 ? 's' : '');
        }
        if ($mins === 0) {
            return $hours . ' heure' . ($hours > 1 ? 's' : '');
        }
        return $hours . 'h ' . $mins . 'min';
    } else {
        if ($hours === 0) {
            return $mins . ' minute' . ($mins > 1 ? 's' : '');
        }
        if ($mins === 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        return $hours . 'h ' . $mins . 'm';
    }
}

/**
 * Format a datetime for display
 *
 * @param string|DateTime $datetime
 * @param string $format Custom format (optional)
 * @param string $language Language: 'fr' or 'en'
 * @return string
 */
function format_datetime(string|DateTime $datetime, ?string $format = null, string $language = 'fr'): string {
    if (is_string($datetime)) {
        $datetime = new DateTime($datetime);
    }

    $datetime->setTimezone(new DateTimeZone(TIMEZONE));

    if ($format === null) {
        $format = $language === 'fr' ? 'd/m/Y H:i' : 'Y-m-d H:i';
    }

    return $datetime->format($format);
}

/**
 * Format a date for display
 *
 * @param string|DateTime $date
 * @param string $language Language: 'fr' or 'en'
 * @return string
 */
function format_date(string|DateTime $date, string $language = 'fr'): string {
    return format_datetime($date, $language === 'fr' ? 'd/m/Y' : 'Y-m-d', $language);
}

/**
 * Format a time for display
 *
 * @param string|DateTime $time
 * @return string
 */
function format_time(string|DateTime $time): string {
    return format_datetime($time, 'H:i');
}

/**
 * Get relative time description (e.g., "2 hours ago")
 *
 * @param string|DateTime $datetime
 * @param string $language Language: 'fr' or 'en'
 * @return string
 */
function time_ago(string|DateTime $datetime, string $language = 'fr'): string {
    if (is_string($datetime)) {
        $datetime = new DateTime($datetime);
    }

    $now = new DateTime();
    $diff = $now->diff($datetime);

    $isFuture = $datetime > $now;

    if ($language === 'fr') {
        $suffix = $isFuture ? 'dans ' : '';
        $prefix = $isFuture ? '' : ' il y a';

        if ($diff->y > 0) return $suffix . $diff->y . ' an' . ($diff->y > 1 ? 's' : '') . $prefix;
        if ($diff->m > 0) return $suffix . $diff->m . ' mois' . $prefix;
        if ($diff->d > 0) return $suffix . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '') . $prefix;
        if ($diff->h > 0) return $suffix . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '') . $prefix;
        if ($diff->i > 0) return $suffix . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . $prefix;
        return $isFuture ? 'dans quelques secondes' : 'à l\'instant';
    } else {
        $suffix = $isFuture ? 'in ' : ' ago';
        $prefix = $isFuture ? '' : '';

        if ($diff->y > 0) return $prefix . $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . $suffix;
        if ($diff->m > 0) return $prefix . $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . $suffix;
        if ($diff->d > 0) return $prefix . $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . $suffix;
        if ($diff->h > 0) return $prefix . $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . $suffix;
        if ($diff->i > 0) return $prefix . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . $suffix;
        return $isFuture ? 'in a few seconds' : 'just now';
    }
}

// --------------------------------------------------------
// Settings Management
// --------------------------------------------------------

/**
 * Get a setting value from the database
 *
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed
 */
function get_setting(string $key, mixed $default = null): mixed {
    static $settings = null;

    // Cache settings to avoid repeated queries
    if ($settings === null) {
        try {
            $db = db();
            $stmt = $db->query("SELECT setting_key, setting_value, setting_type FROM settings");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = self::cast_setting_value($row['setting_value'], $row['setting_type']);
            }
        } catch (PDOException $e) {
            error_log("Failed to load settings: " . $e->getMessage());
            return $default;
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * Cast setting value based on type
 *
 * @param string $value
 * @param string $type
 * @return mixed
 */
function cast_setting_value(string $value, string $type): mixed {
    return match ($type) {
        'integer' => (int) $value,
        'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        'json', 'array' => json_decode($value, true) ?? [],
        default => $value
    };
}

/**
 * Update a setting value
 *
 * @param string $key Setting key
 * @param mixed $value New value
 * @param string $type Value type
 * @return bool
 */
function set_setting(string $key, mixed $value, string $type = 'string'): bool {
    try {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $type = 'json';
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
            $type = 'boolean';
        } elseif (is_int($value)) {
            $type = 'integer';
        }

        $db = db();
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_type, updated_at, updated_by)
            VALUES (:key, :value, :type, NOW(), :user)
            ON DUPLICATE KEY UPDATE
                setting_value = :value,
                setting_type = :type,
                updated_at = NOW(),
                updated_by = :user
        ");

        $user = $_SESSION['user_email'] ?? 'system';

        return $stmt->execute([
            ':key' => $key,
            ':value' => $value,
            ':type' => $type,
            ':user' => $user
        ]);
    } catch (PDOException $e) {
        error_log("Failed to update setting '$key': " . $e->getMessage());
        return false;
    }
}

/**
 * Clear settings cache
 */
function clear_settings_cache(): void {
    // Static cache will be cleared on next request
}

// --------------------------------------------------------
// String Utilities
// --------------------------------------------------------

/**
 * Truncate a string to a maximum length
 *
 * @param string $string
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate(string $string, int $length = 100, string $suffix = '...'): string {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Convert string to slug (URL-friendly)
 *
 * @param string $string
 * @return string
 */
function slugify(string $string): string {
    $string = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string);
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    return trim($string, '-');
}

/**
 * Generate initials from a name
 *
 * @param string $name
 * @return string
 */
function get_initials(string $name): string {
    $parts = explode(' ', trim($name));
    $initials = '';

    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper($part[0]);
        }
    }

    return substr($initials, 0, 2);
}

// --------------------------------------------------------
// Array Utilities
// --------------------------------------------------------

/**
 * Get a value from an array using dot notation
 *
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function array_get(array $array, string $key, mixed $default = null): mixed {
    $keys = explode('.', $key);

    foreach ($keys as $k) {
        if (!is_array($array) || !array_key_exists($k, $array)) {
            return $default;
        }
        $array = $array[$k];
    }

    return $array;
}

/**
 * Set a value in an array using dot notation
 *
 * @param array $array
 * @param string $key
 * @param mixed $value
 * @return array
 */
function array_set(array &$array, string $key, mixed $value): array {
    $keys = explode('.', $key);
    $current = &$array;

    foreach ($keys as $k) {
        if (!isset($current[$k]) || !is_array($current[$k])) {
            $current[$k] = [];
        }
        $current = &$current[$k];
    }

    $current = $value;
    return $array;
}

// --------------------------------------------------------
// HTTP and Response Utilities
// --------------------------------------------------------

/**
 * Send JSON response
 *
 * @param mixed $data
 * @param int $statusCode
 */
function json_response(mixed $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send error response
 *
 * @param string $message
 * @param int $statusCode
 * @param array $errors
 */
function error_response(string $message, int $statusCode = 400, array $errors = []): void {
    $response = ['success' => false, 'message' => $message];
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    json_response($response, $statusCode);
}

/**
 * Send success response
 *
 * @param array $data
 * @param string $message
 */
function success_response(array $data = [], string $message = 'Success'): void {
    $response = array_merge(['success' => true, 'message' => $message], $data);
    json_response($response);
}

/**
 * Get client IP address
 *
 * @return string
 */
function get_client_ip(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}

/**
 * Redirect to a URL
 *
 * @param string $url
 * @param int $statusCode
 */
function redirect(string $url, int $statusCode = 302): void {
    header("Location: $url", true, $statusCode);
    exit;
}

// --------------------------------------------------------
// File and Path Utilities
// --------------------------------------------------------

/**
 * Ensure directory exists, create if not
 *
 * @param string $path
 * @param int $permissions
 * @return bool
 */
function ensure_directory(string $path, int $permissions = 0755): bool {
    if (!is_dir($path)) {
        return mkdir($path, $permissions, true);
    }
    return true;
}

/**
 * Generate a safe filename
 *
 * @param string $originalName
 * @return string
 */
function safe_filename(string $originalName): string {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    $basename = slugify($basename);
    $basename = substr($basename, 0, 50);
    $timestamp = date('Ymd-His');
    $random = substr(generate_secure_token(8), 0, 8);

    return sprintf('%s-%s-%s.%s', $basename, $timestamp, $random, $extension);
}

// --------------------------------------------------------
// Logging Utilities
// --------------------------------------------------------

/**
 * Write to application log
 *
 * @param string $message
 * @param string $level
 * @param array $context
 */
function log_message(string $message, string $level = 'info', array $context = []): void {
    $logFile = LOG_PATH . '/' . date('Y-m-d') . '.log';

    ensure_directory(LOG_PATH);

    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context,
        'ip' => get_client_ip()
    ];

    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Log an audit event
 *
 * @param string $action
 * @param array $details
 * @param int|null $visitorId
 */
function log_audit(string $action, array $details = [], ?int $visitorId = null): void {
    try {
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO audit_log (action, user_email, visitor_id, details, ip_address, user_agent)
            VALUES (:action, :user, :visitor_id, :details, :ip, :user_agent)
        ");

        $stmt->execute([
            ':action' => $action,
            ':user' => $_SESSION['user_email'] ?? null,
            ':visitor_id' => $visitorId,
            ':details' => json_encode($details),
            ':ip' => get_client_ip(),
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Failed to write audit log: " . $e->getMessage());
    }
}
