<?php
/**
 * Database class with improved PDO named parameter handling
 */
class Database {
    private $connection;
    private $errorLog;

    public function __construct() {
        $this->errorLog = defined('ERROR_LOG_FILE') ? ERROR_LOG_FILE : __DIR__ . '/../logs/error.log';
        $this->connect();
    }

    private function connect() {
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci"
        ];
        $lastError = null;

        // Shared hosting: MySQL often reachable via Unix socket (php.ini) when TCP is refused
        $iniSocket = ini_get('pdo_mysql.default_socket') ?: ini_get('mysqli.default_socket');
        if ($iniSocket !== '' && $iniSocket !== false) {
            try {
                $dsn = 'mysql:unix_socket=' . $iniSocket . ';dbname=' . DB_NAME . ';charset=' . $charset;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                return;
            } catch (PDOException $e) {
                $lastError = $e;
                $this->logError('Connection failed (unix_socket from php.ini): ' . $e->getMessage());
            }
        }

        $hosts = [DB_HOST];
        if (DB_HOST === 'localhost') {
            $hosts[] = '127.0.0.1';
        } elseif (DB_HOST === '127.0.0.1') {
            $hosts[] = 'localhost';
            $hosts[] = 'mysql';
        }
        $hosts = array_unique($hosts);
        foreach ($hosts as $host) {
            try {
                $dsn = 'mysql:host=' . $host . ';dbname=' . DB_NAME . ';charset=' . $charset;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                return;
            } catch (PDOException $e) {
                $lastError = $e;
                $this->logError("Connection failed ($host): " . $e->getMessage());
                continue;
            }
        }

        // Last resort: explicit port (some stacks need it)
        foreach (['127.0.0.1', 'localhost'] as $host) {
            try {
                $dsn = 'mysql:host=' . $host . ';port=3306;dbname=' . DB_NAME . ';charset=' . $charset;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                return;
            } catch (PDOException $e) {
                $lastError = $e;
                $this->logError("Connection failed ($host:3306): " . $e->getMessage());
                continue;
            }
        }
        if ($lastError) {
            if (defined('API_JSON_RESPONSE')) {
                throw new PDOException("Database connection failed." . (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS ? " {$lastError->getMessage()}" : ""));
            }
            if (defined('ADMIN_URL')) {
                die("Database connection failed. Please check your configuration. " .
                    (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS ? "[{$lastError->getMessage()}]" : ""));
            } else {
                die("Database connection failed." .
                    (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS ? " {$lastError->getMessage()}" : ""));
            }
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    /**
     * Fix PDO named parameter reuse bug: PDO does not allow the same named
     * parameter to appear more than once in a query when EMULATE_PREPARES=false.
     * This normalizes duplicate named params to indexed ones.
     */
    private function normalizeParams($sql, $params) {
        if (empty($params)) return [$sql, $params];

        $newParams = [];
        $counter   = [];

        $newSql = preg_replace_callback('/:([a-zA-Z_][a-zA-Z0-9_]*)/', function ($match) use ($params, &$newParams, &$counter) {
            $name = $match[1];
            if (!isset($counter[$name])) {
                $counter[$name] = 0;
            }
            $idx     = $counter[$name]++;
            $newKey  = ($idx === 0) ? $name : $name . '_dup' . $idx;
            // Map the value — handle both :name and name keys
            $val = $params[$name] ?? $params[':' . $name] ?? null;
            $newParams[$newKey] = $val;
            return ':' . $newKey;
        }, $sql);

        return [$newSql, $newParams];
    }

    /**
     * Execute a query
     */
    public function query($sql, $params = []) {
        try {
            [$sql, $params] = $this->normalizeParams($sql, $params);
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return false;
        }
    }

    /**
     * Fetch one row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt === false) return false;
        return $stmt->fetch() ?: false;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt === false) return [];
        return $stmt->fetchAll();
    }

    /**
     * Insert a row and return last insert ID
     */
    public function insert($table, $data) {
        $table   = '`' . str_replace('`', '', $table) . '`';
        $columns = implode(', ', array_map(fn($k) => '`' . $k . '`', array_keys($data)));
        $placeholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($data)));

        $sql  = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->query($sql, $data);
        if ($stmt === false) return false;
        return $this->connection->lastInsertId();
    }

    /**
     * Update rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        $table = '`' . str_replace('`', '', $table) . '`';
        $setClauses = implode(', ', array_map(fn($k) => '`' . $k . '` = :set_' . $k, array_keys($data)));

        $params = $whereParams;
        foreach ($data as $k => $v) {
            $params['set_' . $k] = $v;
        }

        $sql  = "UPDATE {$table} SET {$setClauses} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        if ($stmt === false) return false;
        return $stmt->rowCount();
    }

    /**
     * Delete rows
     */
    public function delete($table, $where, $params = []) {
        $table = '`' . str_replace('`', '', $table) . '`';
        $sql   = "DELETE FROM {$table} WHERE {$where}";
        $stmt  = $this->query($sql, $params);
        if ($stmt === false) return false;
        return $stmt->rowCount();
    }

    /**
     * Count rows
     */
    public function count($table, $where = null, $params = []) {
        $table = '`' . str_replace('`', '', $table) . '`';
        $sql   = "SELECT COUNT(*) as count FROM {$table}";
        if ($where) $sql .= " WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return $result ? (int) $result['count'] : 0;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->inTransaction()) {
            return $this->connection->rollBack();
        }
        return false;
    }

    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }

    /**
     * Log error to file
     */
    private function logError($message, $sql = null, $params = null) {
        if (!defined('LOG_ERRORS') || !LOG_ERRORS) return;

        $logDir = dirname($this->errorLog);
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);

        $entry = "[" . date('Y-m-d H:i:s') . "] " . $message;
        if ($sql) {
            $entry .= " | SQL: " . preg_replace('/\s+/', ' ', trim($sql));
        }
        if ($params) {
            $entry .= " | Params: " . print_r($params, true);
        }
        $entry .= "\n";

        file_put_contents($this->errorLog, $entry, FILE_APPEND | LOCK_EX);

        // Debug output (only on dev-only debug files, never on real pages)
        if (PHP_SAPI === 'cli' || (defined('SHOW_DB_ERRORS') && SHOW_DB_ERRORS)) {
            echo "DEBUG: PDO Error - Message: " . $message . "\n";
            if ($sql) echo "DEBUG: Failed SQL: " . substr($sql, 0, 200) . "\n";
        }
    }
}
