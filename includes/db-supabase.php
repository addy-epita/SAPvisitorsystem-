<?php
/**
 * Supabase Database Wrapper
 * Provides compatibility with existing PDO-based code
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase-client.php';

if (!function_exists('getDB')) {
    function getDB(): Database {
        return new Database();
    }
}

/**
 * Database Class for Supabase
 * Provides PDO-like interface for Supabase REST API
 */
if (!class_exists('Database')) {
    class Database {
        private SupabaseClient $client;

        public function __construct() {
            $this->client = getSupabaseClient();
        }

        /**
         * Execute a query and return all results
         */
        public function fetchAll(string $sql, array $params = []): array {
            $parsed = $this->parseSQL($sql, $params);
            if (!$parsed) {
                return [];
            }

            $result = $this->client->select($parsed['table'], $parsed['options']);
            return $result ?? [];
        }

        /**
         * Execute a query and return a single row
         */
        public function fetchOne(string $sql, array $params = []): ?array {
            $parsed = $this->parseSQL($sql, $params);
            if (!$parsed) {
                return null;
            }

            $parsed['options']['limit'] = 1;
            $result = $this->client->select($parsed['table'], $parsed['options']);
            return !empty($result) ? $result[0] : null;
        }

        /**
         * Execute a query and return a single column value
         */
        public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed {
            $row = $this->fetchOne($sql, $params);
            if ($row) {
                $values = array_values($row);
                return $values[$column] ?? null;
            }
            return null;
        }

        /**
         * Execute a statement (INSERT, UPDATE, DELETE)
         */
        public function execute(string $sql, array $params = []): int {
            $parsed = $this->parseSQL($sql, $params);
            if (!$parsed) {
                return 0;
            }

            switch ($parsed['type']) {
                case 'INSERT':
                    $result = $this->client->insert($parsed['table'], $parsed['data']);
                    return $result ? 1 : 0;

                case 'UPDATE':
                    $this->client->update($parsed['table'], $parsed['data'], $parsed['where'] ?? []);
                    return 1;

                case 'DELETE':
                    $this->client->delete($parsed['table'], $parsed['where'] ?? []);
                    return 1;

                default:
                    return 0;
            }
        }

        /**
         * Get the last inserted ID
         */
        public function lastInsertId(): string {
            return '0';
        }

        /**
         * Parse SQL query (basic parser for common queries)
         */
        private function parseSQL(string $sql, array $params): ?array {
            $sql = trim($sql);
            $sql = preg_replace('/\s+/', ' ', $sql);

            if (preg_match('/^SELECT\s+(.+?)\s+FROM\s+`?(\w+)`?/i', $sql, $matches)) {
                $select = $matches[1];
                $table = $matches[2];
                $options = [];

                if ($select !== '*') {
                    $options['select'] = $select;
                }

                if (preg_match('/WHERE\s+(.+?)(\s+ORDER|\s+LIMIT|$)/i', $sql, $whereMatches)) {
                    $options['where'] = $this->parseWhere($whereMatches[1], $params);
                }

                if (preg_match('/ORDER BY\s+`?(\w+)`?\s*(ASC|DESC)?/i', $sql, $orderMatches)) {
                    $column = $orderMatches[1];
                    $direction = isset($orderMatches[2]) && strtoupper($orderMatches[2]) === 'DESC' ? '.desc' : '.asc';
                    $options['order'] = $column . $direction;
                }

                if (preg_match('/LIMIT\s+(\d+)/i', $sql, $limitMatches)) {
                    $options['limit'] = (int)$limitMatches[1];
                }

                return [
                    'type' => 'SELECT',
                    'table' => $table,
                    'options' => $options
                ];
            }

            if (preg_match('/^INSERT INTO\s+`?(\w+)`?\s*\((.*?)\)\s*VALUES\s*\((.*?)\)/i', $sql, $matches)) {
                $table = $matches[1];
                $columns = array_map('trim', explode(',', $matches[2]));
                $columns = array_map(function($col) {
                    return trim($col, '`');
                }, $columns);

                $data = [];
                foreach ($columns as $i => $column) {
                    if (isset($params[$i])) {
                        $data[$column] = $params[$i];
                    }
                }

                return [
                    'type' => 'INSERT',
                    'table' => $table,
                    'data' => $data
                ];
            }

            if (preg_match('/^UPDATE\s+`?(\w+)`?\s+SET\s+(.+?)\s+WHERE\s+(.+)/i', $sql, $matches)) {
                $table = $matches[1];
                $data = $this->parseSet($matches[2], $params);
                $where = $this->parseWhere($matches[3], $params);

                return [
                    'type' => 'UPDATE',
                    'table' => $table,
                    'data' => $data,
                    'where' => $where
                ];
            }

            if (preg_match('/^DELETE FROM\s+`?(\w+)`?\s+WHERE\s+(.+)/i', $sql, $matches)) {
                $table = $matches[1];
                $where = $this->parseWhere($matches[2], $params);

                return [
                    'type' => 'DELETE',
                    'table' => $table,
                    'where' => $where
                ];
            }

            return null;
        }

        /**
         * Parse WHERE clause
         */
        private function parseWhere(string $where, array $params): array {
            $conditions = [];
            $where = trim($where);

            if (preg_match('/^(\w+)\s*=\s*\?$/i', $where)) {
                $column = trim(preg_replace('/\s*=\s*\?$/', '', $where));
                if (!empty($params)) {
                    $conditions[$column] = $params[0];
                }
            } elseif (preg_match_all('/(\w+)\s*=\s*[:\?](\w*)/', $where, $matches)) {
                foreach ($matches[1] as $i => $column) {
                    $paramName = $matches[2][$i];
                    if (isset($params[$paramName])) {
                        $conditions[$column] = $params[$paramName];
                    } elseif (isset($params[$i])) {
                        $conditions[$column] = $params[$i];
                    }
                }
            }

            return $conditions;
        }

        /**
         * Parse SET clause
         */
        private function parseSet(string $set, array $params): array {
            $data = [];
            $parts = explode(',', $set);
            $paramIndex = 0;

            foreach ($parts as $part) {
                if (preg_match('/(\w+)\s*=\s*[:\?](\w*)/', $part, $matches)) {
                    $column = $matches[1];
                    $paramName = $matches[2];

                    if (isset($params[$paramName])) {
                        $data[$column] = $params[$paramName];
                    } elseif (isset($params[$paramIndex])) {
                        $data[$column] = $params[$paramIndex];
                        $paramIndex++;
                    }
                }
            }

            return $data;
        }

        /**
         * Transaction methods (no-op for Supabase REST API)
         */
        public function beginTransaction(): bool {
            return true;
        }

        public function commit(): bool {
            return true;
        }

        public function rollback(): bool {
            return true;
        }

        /**
         * Get the Supabase client
         */
        public function getClient(): SupabaseClient {
            return $this->client;
        }
    }
}

try {
    $db = getDB();
} catch (Exception $e) {
    error_log('Failed to initialize Supabase database: ' . $e->getMessage());
    $db = null;
}
