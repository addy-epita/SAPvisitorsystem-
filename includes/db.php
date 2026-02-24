<?php
/**
 * Database Connection
 *
 * Establishes PDO connection to MySQL database with error handling.
 */

require_once __DIR__ . '/config.php';

/**
 * Get database connection
 *
 * @return PDO
 * @throws Exception If connection fails
 */
function getDB(): PDO {
    static $db = null;

    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
            ];

            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please try again later.');
        }
    }

    return $db;
}

// Create global $db variable for backward compatibility
try {
    $db = getDB();
} catch (Exception $e) {
    // Log error but don't crash - let the calling code handle it
    error_log('Failed to initialize database: ' . $e->getMessage());
    $db = null;
}
