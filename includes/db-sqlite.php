<?php
/**
 * SQLite Database Connection for Bolt Platform
 * Lightweight alternative to MySQL for easy deployment
 */

require_once __DIR__ . '/config.php';

/**
 * Get SQLite database connection
 *
 * @return PDO
 */
function getDB(): PDO {
    static $db = null;

    if ($db === null) {
        try {
            $dbPath = __DIR__ . '/../data/visitor_system.db';

            // Create data directory if it doesn't exist
            if (!is_dir(dirname($dbPath))) {
                mkdir(dirname($dbPath), 0755, true);
            }

            $db = new PDO("sqlite:" . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign keys
            $db->exec("PRAGMA foreign_keys = ON");

            // Initialize schema if needed
            initializeSQLiteSchema($db);

        } catch (PDOException $e) {
            error_log('SQLite connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please try again later.');
        }
    }

    return $db;
}

/**
 * Initialize SQLite schema
 */
function initializeSQLiteSchema(PDO $db): void {
    // Check if visitors table exists
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='visitors'");
    if ($stmt->fetch()) {
        return; // Schema already initialized
    }

    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS visitors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        company TEXT,
        reason TEXT,
        host_email TEXT NOT NULL,
        host_name TEXT,
        visitor_email TEXT,
        arrival_time DATETIME NOT NULL,
        expected_duration INTEGER DEFAULT 180,
        departure_time DATETIME,
        status TEXT DEFAULT 'checked_in',
        checkin_method TEXT DEFAULT 'kiosk',
        checkout_method TEXT,
        qr_token TEXT UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_visitors_status ON visitors(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_visitors_qr_token ON visitors(qr_token)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_visitors_arrival ON visitors(arrival_time)");

    $db->exec("CREATE TABLE IF NOT EXISTS hosts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        department TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        visitor_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        recipient_email TEXT NOT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'pending'
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        action TEXT NOT NULL,
        user_email TEXT,
        visitor_id INTEGER,
        details TEXT,
        ip_address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS action_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        visitor_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        action_type TEXT DEFAULT 'confirm_present',
        used_at DATETIME,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        role TEXT DEFAULT 'admin',
        password_hash TEXT NOT NULL,
        sso_enabled INTEGER DEFAULT 0,
        login_count INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert default settings
    $defaultSettings = [
        ['reminder_intervals', '[120, 240, 360, 480]'],
        ['default_duration', '180'],
        ['end_of_day_time', '18:00'],
        ['data_retention_days', '365'],
        ['site_name', 'SAP Office'],
        ['timezone', 'Europe/Paris'],
        ['language_default', 'fr'],
        ['enable_email_notifications', 'false']
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }

    // Insert default admin user (password: changeme)
    $stmt = $db->prepare("INSERT OR IGNORE INTO admin_users (email, name, role, password_hash, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'admin@example.com',
        'System Administrator',
        'admin',
        password_hash('changeme', PASSWORD_DEFAULT),
        1
    ]);

    // Insert sample hosts
    $sampleHosts = [
        ['jean.dupont@sap.com', 'Jean Dupont', 'IT'],
        ['marie.martin@sap.com', 'Marie Martin', 'RH'],
        ['pierre.bernard@sap.com', 'Pierre Bernard', 'Finance'],
        ['sophie.petit@sap.com', 'Sophie Petit', 'Marketing'],
        ['lucas.moreau@sap.com', 'Lucas Moreau', 'Sales']
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO hosts (email, name, department) VALUES (?, ?, ?)");
    foreach ($sampleHosts as $host) {
        $stmt->execute($host);
    }
}

/**
 * Database Class for Bolt/SQLite
 */
class Database {
    private ?PDO $pdo = null;

    public function __construct() {
        $this->pdo = getDB();
    }

    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($column);
    }

    public function execute(string $sql, array $params = []): int {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollback(): bool {
        return $this->pdo->rollBack();
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }
}

// Backward compatibility
try {
    $db = getDB();
} catch (Exception $e) {
    error_log('Failed to initialize database: ' . $e->getMessage());
    $db = null;
}
