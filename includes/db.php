<?php
/**
 * SAP Visitor Management System - Database Connection Class
 *
 * Provides PDO database connection with error handling,
 * query helpers, and prepared statement support.
 *
 * @package SAPVisitorSystem
 * @author SAP Visitor System
 * @version 1.0.0
 */

require_once __DIR__ . '/config.php';

class Database
{
    /** @var PDO|null The PDO connection instance */
    private static ?PDO $instance = null;

    /** @var PDO|null The current PDO connection */
    private ?PDO $connection = null;

    /** @var array Query log for debugging */
    private array $queryLog = [];

    /** @var bool Enable query logging */
    private bool $logging = false;

    /** @var int Total query count */
    private int $queryCount = 0;

    /** @var float Total query execution time */
    private float $totalQueryTime = 0.0;

    /**
     * Constructor - Initialize database connection
     *
     * @param bool $logging Enable query logging
     * @throws PDOException If connection fails
     */
    public function __construct(bool $logging = false)
    {
        $this->logging = $logging;
        $this->connect();
    }

    /**
     * Get singleton instance
     *
     * @param bool $logging Enable query logging
     * @return Database
     */
    public static function getInstance(bool $logging = false): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self($logging);
        }
        return $instance;
    }

    /**
     * Establish database connection
     *
     * @return void
     * @throws PDOException
     */
    private function connect(): void
    {
        if ($this->connection !== null) {
            return;
        }

        // Check if we have a static instance
        if (self::$instance !== null) {
            $this->connection = self::$instance;
            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s;collation=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET,
            DB_COLLATION
        );

        $options = [
            PDO::ATTR_ERRMODE => APP_DEBUG ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION,
            PDO::ATTR_TIMEOUT => DB_TIMEOUT,
        ];

        if (DB_PERSISTENT) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            self::$instance = $this->connection;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException(
                "Unable to connect to database. Please try again later.",
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the raw PDO connection
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Execute a SELECT query and return all results
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array Query results
     * @throws PDOException
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a SELECT query and return single row
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array|null Single row or null if not found
     * @throws PDOException
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a SELECT query and return single column value
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @param int $column Column index (0-based)
     * @return mixed|null Column value or null
     * @throws PDOException
     */
    public function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetchColumn($column);
        return $result !== false ? $result : null;
    }

    /**
     * Execute a query (INSERT, UPDATE, DELETE)
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     * @throws PDOException
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Insert a record and return the last insert ID
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false Last insert ID or false on failure
     * @throws PDOException
     */
    public function insert(string $table, array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Insert data cannot be empty");
        }

        // Sanitize table name (allow only alphanumeric and underscore)
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $this->query($sql, $data);
        return (int) $this->connection->lastInsertId();
    }

    /**
     * Update records in a table
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param string $where WHERE clause (with placeholders)
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     * @throws PDOException
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Update data cannot be empty");
        }

        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $paramName = "set_$column";
            // Handle duplicate column names in SET and WHERE
            while (isset($whereParams[$paramName]) || isset($params[$paramName])) {
                $paramName .= '_';
            }
            $setParts[] = "`$column` = :$paramName";
            $params[$paramName] = $value;
        }

        // Merge WHERE params (renaming if necessary)
        foreach ($whereParams as $key => $value) {
            if (isset($params[$key])) {
                $newKey = $key . '_where';
                $where = str_replace(":$key", ":$newKey", $where);
                $params[$newKey] = $value;
            } else {
                $params[$key] = $value;
            }
        }

        $sql = sprintf("UPDATE `%s` SET %s WHERE %s", $table, implode(', ', $setParts), $where);

        return $this->execute($sql, $params);
    }

    /**
     * Delete records from a table
     *
     * @param string $table Table name
     * @param string $where WHERE clause (with placeholders)
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     * @throws PDOException
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        $sql = sprintf("DELETE FROM `%s` WHERE %s", $table, $where);
        return $this->execute($sql, $params);
    }

    /**
     * Execute a prepared statement query
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     * @throws PDOException
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $startTime = microtime(true);

        try {
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                throw new PDOException("Failed to prepare statement");
            }

            // Bind parameters with proper types
            foreach ($params as $key => $value) {
                $type = $this->getPdoType($value);
                $stmt->bindValue(is_int($key) ? $key + 1 : ":$key", $value, $type);
            }

            $stmt->execute();

            $executionTime = microtime(true) - $startTime;
            $this->totalQueryTime += $executionTime;
            $this->queryCount++;

            if ($this->logging) {
                $this->queryLog[] = [
                    'sql' => $sql,
                    'params' => $params,
                    'time' => $executionTime,
                    'rows' => $stmt->rowCount(),
                ];
            }

            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Check if currently in a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Execute a callback within a transaction
     *
     * @param callable $callback Function to execute
     * @return mixed Result from callback
     * @throws Exception
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get the last insert ID
     *
     * @param string|null $name Sequence name (for PostgreSQL)
     * @return string
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * Get query statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'query_count' => $this->queryCount,
            'total_time' => round($this->totalQueryTime, 4),
            'avg_time' => $this->queryCount > 0 ? round($this->totalQueryTime / $this->queryCount, 4) : 0,
        ];
    }

    /**
     * Get query log
     *
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear query log
     *
     * @return void
     */
    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Enable or disable query logging
     *
     * @param bool $enabled
     * @return void
     */
    public function setLogging(bool $enabled): void
    {
        $this->logging = $enabled;
    }

    /**
     * Get PDO type constant for a value
     *
     * @param mixed $value
     * @return int
     */
    private function getPdoType($value): int
    {
        return match (true) {
            is_null($value) => PDO::PARAM_NULL,
            is_bool($value) => PDO::PARAM_BOOL,
            is_int($value) => PDO::PARAM_INT,
            is_resource($value) => PDO::PARAM_LOB,
            default => PDO::PARAM_STR,
        };
    }

    /**
     * Escape a string for use in SQL (use prepared statements instead!)
     *
     * @param string $string
     * @return string
     * @deprecated Use prepared statements instead
     */
    public function escape(string $string): string
    {
        return $this->connection->quote($string);
    }

    /**
     * Close the database connection
     *
     * @return void
     */
    public function close(): void
    {
        $this->connection = null;
        self::$instance = null;
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        // Don't close persistent connections
        if (!DB_PERSISTENT) {
            $this->connection = null;
        }
    }
}

/**
 * Global database helper function
 *
 * @return Database
 */
function db(): Database
{
    return Database::getInstance();
}
