<?php
/**
 * Database Connection Manager & Query Wrapper
 * Supports both SQLite (local development fallback) and MySQL/MariaDB (production).
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $driver;

    private function __construct() {
        // Check if database constants are defined in config.php
        $useMysql = defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS') 
                    && !empty(DB_HOST) && !empty(DB_NAME);

        if ($useMysql) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                $this->driver = 'mysql';
            } catch (PDOException $e) {
                // Fall back to SQLite if MySQL connection fails in development environment
                $this->connectSQLite();
            }
        } else {
            $this->connectSQLite();
        }
    }

    private function connectSQLite() {
        $dbPath = __DIR__ . '/../storage/database.sqlite';
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new PDO("sqlite:" . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->driver = 'sqlite';
    }

    /**
     * Get instance of Database (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get underlying PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Get driver name ('mysql' or 'sqlite')
     */
    public function getDriverName() {
        return $this->driver;
    }

    /**
     * Get database-specific random ordering keyword
     */
    public function getRandomKeyword() {
        return $this->driver === 'sqlite' ? 'RANDOM()' : 'RAND()';
    }

    /**
     * Execute query with parameters and return PDOStatement
     */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Select all rows
     */
    public function select($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Select single row
     */
    public function selectOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Execute statement (INSERT/UPDATE/DELETE) and return row count
     */
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Return last inserted ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
