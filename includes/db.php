<?php
/**
 * Database Connection
 *
 * Establishes PDO connection to MySQL or SQLite database with error handling.
 * Automatically falls back to SQLite for Bolt/Platform.sh or when MySQL is unavailable.
 */

require_once __DIR__ . '/config.php';

// Check if we should use SQLite (Bolt, Platform.sh, or local testing without MySQL)
$useSQLite = false;

// Detect Bolt environment
if (getenv('BOLT') || getenv('PLATFORM_PROJECT') || !extension_loaded('pdo_mysql')) {
    $useSQLite = true;
}

// Try MySQL first if available, fall back to SQLite
if (!$useSQLite) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $testDb = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $testDb = null; // Close test connection
    } catch (PDOException $e) {
        error_log('MySQL not available, falling back to SQLite: ' . $e->getMessage());
        $useSQLite = true;
    }
}

// Use SQLite for Bolt and fallback scenarios
if ($useSQLite) {
    require_once __DIR__ . '/db-sqlite.php';
    return;
}

/**
 * Get MySQL database connection
 *
 * @return PDO
 * @throws Exception If connection fails
 */
if (!function_exists('getDB')) {
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
}

// Create global $db variable for backward compatibility
try {
    $db = getDB();
} catch (Exception $e) {
    // Log error but don't crash - let the calling code handle it
    error_log('Failed to initialize database: ' . $e->getMessage());
    $db = null;
}

/**
 * Database Class Wrapper
 *
 * Provides an object-oriented interface for database operations
 * Compatible with admin panel and other components
 */
if (!class_exists('Database')) {
    class Database {
        private ?PDO $pdo = null;

        public function __construct() {
            $this->pdo = getDB();
        }

        /**
         * Execute a query and return all results
         */
        public function fetchAll(string $sql, array $params = []): array {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        /**
         * Execute a query and return a single row
         */
        public function fetchOne(string $sql, array $params = []): ?array {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        }

        /**
         * Execute a query and return a single column value
         */
        public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn($column);
        }

        /**
         * Execute a statement (INSERT, UPDATE, DELETE)
         */
        public function execute(string $sql, array $params = []): int {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        }

        /**
         * Get the last inserted ID
         */
        public function lastInsertId(): string {
            return $this->pdo->lastInsertId();
        }

        /**
         * Begin a transaction
         */
        public function beginTransaction(): bool {
            return $this->pdo->beginTransaction();
        }

        /**
         * Commit a transaction
         */
        public function commit(): bool {
            return $this->pdo->commit();
        }

        /**
         * Rollback a transaction
         */
        public function rollback(): bool {
            return $this->pdo->rollBack();
        }

        /**
         * Get the underlying PDO connection
         */
        public function getPdo(): PDO {
            return $this->pdo;
        }
    }
}
