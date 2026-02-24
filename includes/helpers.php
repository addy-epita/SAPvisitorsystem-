<?php
/**
 * SAP Visitor Management System - Utility Functions
 *
 * Collection of helper functions for sanitization, formatting,
 * token generation, and common operations.
 *
 * @package SAPVisitorSystem
 * @author SAP Visitor System
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';

// =============================================================================
// INPUT SANITIZATION AND VALIDATION
// =============================================================================

/**
 * Sanitize input data to prevent XSS and injection attacks
 *
 * @param mixed $data Input data to sanitize
 * @param string $type Type of sanitization: 'string', 'email', 'int', 'float', 'url', 'html'
 * @return mixed Sanitized data
 */
function sanitize_input(mixed $data, string $type = 'string'): mixed
{
    if ($data === null) {
        return null;
    }

    return match ($type) {
        'string' => sanitize_string($data),
        'email' => sanitize_email($data),
        'int' => filter_var($data, FILTER_VALIDATE_INT),
        'float' => filter_var($data, FILTER_VALIDATE_FLOAT),
        'bool' => filter_var($data, FILTER_VALIDATE_BOOLEAN),
        'url' => filter_var($data, FILTER_SANITIZE_URL),
        'html' => sanitize_html($data),
        'alpha' => preg_replace('/[^a-zA-Z]/', '', $data),
        'alphanumeric' => preg_replace('/[^a-zA-Z0-9]/', '', $data),
        'numeric' => preg_replace('/[^0-9]/', '', $data),
        default => sanitize_string($data),
    };
}

/**
 * Sanitize a string value
 *
 * @param string $string
 * @return string
 */
function sanitize_string(string $string): string
{
    // Remove null bytes
    $string = str_replace(chr(0), '', $string);
    // Trim whitespace
    $string = trim($string);
    // Convert special characters to HTML entities
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize email address
 *
 * @param string $email
 * @return string|null Sanitized email or null if invalid
 */
function sanitize_email(string $email): ?string
{
    $email = trim(strtolower($email));
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ?: null;
}

/**
 * Sanitize HTML content (allows safe HTML)
 *
 * @param string $html
 * @return string
 */
function sanitize_html(string $html): string
{
    // Strip all tags by default (be strict)
    // For allowed tags, use a library like HTMLPurifier
    return strip_tags($html);
}

/**
 * Validate and sanitize a name (first name, last name, company)
 *
 * @param string $name
 * @param int $maxLength Maximum length
 * @return string|null
 */
function sanitize_name(string $name, int $maxLength = 150): ?string
{
    $name = trim($name);
    // Remove control characters but allow letters, numbers, spaces, and common punctuation
    $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);
    // Limit length
    if (strlen($name) > $maxLength) {
        $name = substr($name, 0, $maxLength);
    }
    // Must contain at least one letter
    if (!preg_match('/[\p{L}]/u', $name)) {
        return null;
    }
    return sanitize_string($name);
}

/**
 * Validate a French phone number
 *
 * @param string $phone
 * @return string|null Normalized phone number or null if invalid
 */
function validate_phone(string $phone): ?string
{
    // Remove all non-numeric characters
    $digits = preg_replace('/[^0-9]/', '', $phone);

    // French phone numbers: 10 digits starting with 0
    if (preg_match('/^0[1-9][0-9]{8}$/', $digits)) {
        return $digits;
    }

    // International format with country code
    if (preg_match('/^33[1-9][0-9]{8}$/', $digits)) {
        return '0' . substr($digits, 2);
    }

    return null;
}

// =============================================================================
// TOKEN AND QR CODE GENERATION
// =============================================================================

/**
 * Generate a cryptographically secure random token
 *
 * @param int $length Token length in bytes (results in 2x hex chars)
 * @return string Hex-encoded token
 */
function generate_token(int $length = 32): string
{
    try {
        return bin2hex(random_bytes($length));
    } catch (Exception $e) {
        // Fallback (less secure, should not happen on modern PHP)
        $token = '';
        for ($i = 0; $i < $length * 2; $i++) {
            $token .= dechex(random_int(0, 15));
        }
        return $token;
    }
}

/**
 * Generate a QR token for visitor checkout
 *
 * @param int $visitorId Visitor ID to encode
 * @return string Unique QR token
 */
function generate_qr_token(int $visitorId): string
{
    // Create a token with visitor ID and random component
    $randomPart = generate_token(24); // 48 hex chars
    $timestamp = dechex(time()); // Timestamp for expiration validation
    $visitorPart = dechex($visitorId);

    // Format: random(48) + timestamp(8) + visitorId(variable)
    $token = $randomPart . $timestamp . $visitorPart;

    // Add checksum for integrity (first 8 chars of SHA256)
    $checksum = substr(hash('sha256', $token . ENCRYPTION_KEY), 0, 8);

    return $token . $checksum;
}

/**
 * Validate a QR token structure
 *
 * @param string $token
 * @return array|null ['visitor_id' => int, 'created_at' => int] or null if invalid
 */
function validate_qr_token(string $token): ?array
{
    if (strlen($token) < 64) {
        return null;
    }

    // Extract parts
    $checksum = substr($token, -8);
    $payload = substr($token, 0, -8);

    // Verify checksum
    $expectedChecksum = substr(hash('sha256', $payload . ENCRYPTION_KEY), 0, 8);
    if (!hash_equals($expectedChecksum, $checksum)) {
        return null;
    }

    // Extract timestamp and visitor ID
    $timestampHex = substr($payload, 48, 8);
    $visitorIdHex = substr($payload, 56);

    $createdAt = hexdec($timestampHex);
    $visitorId = hexdec($visitorIdHex);

    // Check expiration
    $maxAge = QR_CODE_VALIDITY_HOURS * 3600;
    if (time() - $createdAt > $maxAge) {
        return null;
    }

    return [
        'visitor_id' => $visitorId,
        'created_at' => $createdAt,
    ];
}

/**
 * Generate a CSRF token
 *
 * @return string
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = generate_token(CSRF_TOKEN_LENGTH);
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 *
 * @param string $token Token to validate
 * @return bool
 */
function validate_csrf_token(string $token): bool
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// =============================================================================
// DATE AND TIME FORMATTING
// =============================================================================

/**
 * Format a duration in minutes to human-readable string
 *
 * @param int $minutes Duration in minutes
 * @param string $locale Locale for formatting (fr/en)
 * @return string Formatted duration
 */
function format_duration(int $minutes, string $locale = 'fr'): string
{
    if ($minutes < 0) {
        $minutes = 0;
    }

    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($locale === 'fr') {
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'heure' : 'heures');
        }
        if ($mins > 0 || $hours === 0) {
            $parts[] = $mins . ' ' . ($mins === 1 ? 'minute' : 'minutes');
        }
        return implode(' ', $parts);
    } else {
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }
        if ($mins > 0 || $hours === 0) {
            $parts[] = $mins . ' ' . ($mins === 1 ? 'minute' : 'minutes');
        }
        return implode(' ', $parts);
    }
}

/**
 * Format a datetime for display
 *
 * @param string|DateTime $datetime
 * @param string $format Custom format or 'date', 'time', 'datetime', 'relative'
 * @param string $locale
 * @return string
 */
function format_datetime(string|DateTime $datetime, string $format = 'datetime', string $locale = 'fr'): string
{
    if (is_string($datetime)) {
        $datetime = new DateTime($datetime);
    }

    // Set locale for IntlDateFormatter if available
    $localeString = $locale === 'fr' ? 'fr_FR' : 'en_US';

    if ($format === 'relative') {
        return format_relative_time($datetime, $locale);
    }

    $formatString = match ($format) {
        'date' => DATE_FORMAT,
        'time' => TIME_FORMAT,
        'datetime' => DATETIME_FORMAT,
        'iso' => 'c',
        'full' => 'l d F Y à H:i',
        default => $format,
    };

    // Use IntlDateFormatter for localized dates if available
    if (class_exists('IntlDateFormatter') && $format !== 'iso') {
        $formatter = new IntlDateFormatter(
            $localeString,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::SHORT,
            $datetime->getTimezone()->getName()
        );

        if ($format === 'date') {
            $formatter->setPattern('dd/MM/yyyy');
        } elseif ($format === 'time') {
            $formatter->setPattern('HH:mm');
        } elseif ($format === 'full') {
            $formatter->setPattern("EEEE d MMMM yyyy 'à' HH:mm");
        }

        return $formatter->format($datetime);
    }

    return $datetime->format($formatString);
}

/**
 * Format relative time (e.g., "2 hours ago")
 *
 * @param DateTime $datetime
 * @param string $locale
 * @return string
 */
function format_relative_time(DateTime $datetime, string $locale = 'fr'): string
{
    $now = new DateTime();
    $diff = $now->diff($datetime);

    $isPast = $datetime < $now;

    if ($diff->y > 0) {
        $unit = $locale === 'fr' ? 'an' : 'year';
        $plural = $diff->y > 1 ? ($locale === 'fr' ? 's' : 's') : '';
    } elseif ($diff->m > 0) {
        $unit = $locale === 'fr' ? 'mois' : 'month';
        $plural = $diff->m > 1 && $locale !== 'fr' ? 's' : '';
    } elseif ($diff->d > 0) {
        $unit = $locale === 'fr' ? 'jour' : 'day';
        $plural = $diff->d > 1 ? ($locale === 'fr' ? 's' : 's') : '';
    } elseif ($diff->h > 0) {
        $unit = $locale === 'fr' ? 'heure' : 'hour';
        $plural = $diff->h > 1 ? ($locale === 'fr' ? 's' : 's') : '';
        $value = $diff->h;
    } elseif ($diff->i > 0) {
        $unit = $locale === 'fr' ? 'minute' : 'minute';
        $plural = $diff->i > 1 ? ($locale === 'fr' ? 's' : 's') : '';
        $value = $diff->i;
    } else {
        return $locale === 'fr' ? 'à l\'instant' : 'just now';
    }

    if (!isset($value)) {
        $value = $diff->y ?: $diff->m ?: $diff->d;
    }

    if ($isPast) {
        return $locale === 'fr'
            ? "il y a {$value} {$unit}{$plural}"
            : "{$value} {$unit}{$plural} ago";
    } else {
        return $locale === 'fr'
            ? "dans {$value} {$unit}{$plural}"
            : "in {$value} {$unit}{$plural}";
    }
}

// =============================================================================
// SETTINGS MANAGEMENT
// =============================================================================

/**
 * Get a setting value from the database
 *
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value
 */
function get_setting(string $key, mixed $default = null): mixed
{
    static $settings = null;

    // Load all settings once per request
    if ($settings === null) {
        $settings = [];
        try {
            require_once __DIR__ . '/db.php';
            $db = Database::getInstance();
            $rows = $db->fetchAll("SELECT setting_key, setting_value, is_json FROM settings");
            foreach ($rows as $row) {
                $value = $row['setting_value'];
                if ($row['is_json']) {
                    $decoded = json_decode($value, true);
                    $value = $decoded !== null ? $decoded : $value;
                }
                $settings[$row['setting_key']] = $value;
            }
        } catch (Exception $e) {
            error_log("Failed to load settings: " . $e->getMessage());
            // Fall back to defaults
        }
    }

    // Return cached value or default
    if (array_key_exists($key, $settings)) {
        return $settings[$key];
    }

    // Check for constant fallback
    $constantKey = strtoupper($key);
    if (defined($constantKey)) {
        return constant($constantKey);
    }

    return $default;
}

/**
 * Update a setting value
 *
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param bool $isJson Whether value should be stored as JSON
 * @return bool Success
 */
function set_setting(string $key, mixed $value, bool $isJson = false): bool
{
    try {
        require_once __DIR__ . '/db.php';
        $db = Database::getInstance();

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $isJson = true;
        }

        $sql = "INSERT INTO settings (setting_key, setting_value, is_json)
                VALUES (:key, :value, :is_json)
                ON DUPLICATE KEY UPDATE
                setting_value = :value,
                is_json = :is_json,
                updated_at = CURRENT_TIMESTAMP";

        $db->execute($sql, [
            'key' => $key,
            'value' => $value,
            'is_json' => $isJson ? 1 : 0,
        ]);

        // Clear cache
        global $settings;
        $settings = null;

        return true;
    } catch (Exception $e) {
        error_log("Failed to update setting {$key}: " . $e->getMessage());
        return false;
    }
}

// =============================================================================
// SECURITY UTILITIES
// =============================================================================

/**
 * Generate a secure password hash
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
}

/**
 * Verify a password against a hash
 *
 * @param string $password Plain text password
 * @param string $hash Stored hash
 * @return bool
 */
function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Encrypt sensitive data
 *
 * @param string $data Data to encrypt
 * @return string|false Encrypted data or false on failure
 */
function encrypt_data(string $data): string|false
{
    if (empty(ENCRYPTION_KEY)) {
        error_log("Encryption key not configured");
        return false;
    }

    $key = base64_decode(ENCRYPTION_KEY);
    if (strlen($key) !== 32) {
        error_log("Invalid encryption key length");
        return false;
    }

    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-GCM', $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($encrypted === false) {
        return false;
    }

    return base64_encode($iv . $tag . $encrypted);
}

/**
 * Decrypt sensitive data
 *
 * @param string $data Data to decrypt
 * @return string|false Decrypted data or false on failure
 */
function decrypt_data(string $data): string|false
{
    if (empty(ENCRYPTION_KEY)) {
        error_log("Encryption key not configured");
        return false;
    }

    $key = base64_decode(ENCRYPTION_KEY);
    $data = base64_decode($data);

    if (strlen($data) < 32) {
        return false;
    }

    $iv = substr($data, 0, 16);
    $tag = substr($data, 16, 16);
    $ciphertext = substr($data, 32);

    return openssl_decrypt($ciphertext, 'AES-256-GCM', $key, OPENSSL_RAW_DATA, $iv, $tag);
}

/**
 * Get client IP address
 *
 * @return string
 */
function get_client_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Check if request is from a valid IP range
 *
 * @param array $allowedRanges Array of allowed IP ranges (CIDR notation)
 * @return bool
 */
function is_ip_allowed(array $allowedRanges): bool
{
    if (empty($allowedRanges)) {
        return true;
    }

    $clientIp = get_client_ip();

    foreach ($allowedRanges as $range) {
        if (ip_in_range($clientIp, $range)) {
            return true;
        }
    }

    return false;
}

/**
 * Check if IP is in CIDR range
 *
 * @param string $ip
 * @param string $range CIDR range (e.g., "192.168.1.0/24")
 * @return bool
 */
function ip_in_range(string $ip, string $range): bool
{
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }

    list($range, $netmask) = explode('/', $range, 2);
    $rangeDecimal = ip2long($range);
    $ipDecimal = ip2long($ip);
    $wildcard = pow(2, (32 - $netmask)) - 1;
    $netmaskDecimal = ~$wildcard;

    return (($ipDecimal & $netmaskDecimal) === ($rangeDecimal & $netmaskDecimal));
}

// =============================================================================
// ARRAY AND STRING UTILITIES
// =============================================================================

/**
 * Get array value with dot notation
 *
 * @param array $array
 * @param string $key Dot-separated key (e.g., "user.name")
 * @param mixed $default
 * @return mixed
 */
function array_get(array $array, string $key, mixed $default = null): mixed
{
    if (isset($array[$key])) {
        return $array[$key];
    }

    $keys = explode('.', $key);
    foreach ($keys as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }
        $array = $array[$segment];
    }

    return $array;
}

/**
 * Pluralize a word (basic English/French support)
 *
 * @param int $count
 * @param string $singular
 * @param string|null $plural
 * @param string $locale
 * @return string
 */
function pluralize(int $count, string $singular, ?string $plural = null, string $locale = 'fr'): string
{
    if ($count === 1) {
        return "1 {$singular}";
    }

    if ($plural === null) {
        $plural = $locale === 'fr' ? $singular . 's' : $singular . 's';
    }

    return "{$count} {$plural}";
}

/**
 * Truncate text to specified length
 *
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Generate a slug from a string
 *
 * @param string $string
 * @return string
 */
function slugify(string $string): string
{
    // Transliterate to ASCII
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    // Convert to lowercase
    $string = strtolower($string);
    // Replace non-alphanumeric with hyphens
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    // Remove leading/trailing hyphens
    $string = trim($string, '-');
    // Collapse multiple hyphens
    $string = preg_replace('/-+/', '-', $string);

    return $string;
}

// =============================================================================
// HTTP AND REDIRECT UTILITIES
// =============================================================================

/**
 * Send JSON response and exit
 *
 * @param mixed $data Response data
 * @param int $status HTTP status code
 * @return never
 */
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect to a URL
 *
 * @param string $url
 * @param int $status HTTP status code
 * @return never
 */
function redirect(string $url, int $status = 302): never
{
    // Prevent header injection
    $url = str_replace(["\r", "\n"], '', $url);

    header("Location: {$url}", true, $status);
    exit;
}

/**
 * Get current URL
 *
 * @param bool $withQueryString
 * @return string
 */
function current_url(bool $withQueryString = true): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    if (!$withQueryString && strpos($uri, '?') !== false) {
        $uri = substr($uri, 0, strpos($uri, '?'));
    }

    return "{$protocol}://{$host}{$uri}";
}

/**
 * Flash message helper
 *
 * @param string|null $type Message type (success, error, warning, info)
 * @param string|null $message Message text
 * @return array|null Current messages or null
 */
function flash(?string $type = null, ?string $message = null): ?array
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }

    // Set message
    if ($type !== null && $message !== null) {
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message,
        ];
        return null;
    }

    // Get and clear messages
    $messages = $_SESSION['flash_messages'];
    $_SESSION['flash_messages'] = [];

    return $messages;
}
