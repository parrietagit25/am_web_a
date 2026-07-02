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
        // DB_REQUIRE_MYSQL: cuando está activo, la app NUNCA debe caer a SQLite
        // en silencio — cualquier falla de configuración/conexión debe fallar
        // de forma explícita (RuntimeException) en vez de servir datos obsoletos.
        $requireMysql = defined('DB_REQUIRE_MYSQL') && DB_REQUIRE_MYSQL === true;

        // Check if database constants are defined in config.php
        $hasMysqlConfig = defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')
                    && !empty(DB_HOST) && !empty(DB_NAME);

        if ($requireMysql && !$hasMysqlConfig) {
            throw new RuntimeException('DB_REQUIRE_MYSQL está activo pero faltan DB_HOST/DB_NAME/DB_USER/DB_PASS en config.php.');
        }

        if ($requireMysql && !extension_loaded('pdo_mysql')) {
            throw new RuntimeException('DB_REQUIRE_MYSQL está activo pero la extensión pdo_mysql no está disponible en PHP.');
        }

        if ($hasMysqlConfig) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                $this->driver = 'mysql';
            } catch (PDOException $e) {
                if ($requireMysql) {
                    throw new RuntimeException('MySQL requerido pero no se pudo conectar. Revisar configuración DB y pdo_mysql.', 0, $e);
                }
                // Fall back to SQLite if MySQL connection fails in development environment
                $this->logMysqlFallback($e);
                $this->connectSQLite();
            }
        } else {
            $this->connectSQLite();
        }
    }

    /**
     * Log de la caída MySQL -> SQLite. Nunca incluye DB_PASS; PDOException::getMessage()
     * de un fallo de conexión no expone el password en texto plano (solo "(using password: YES)").
     */
    private function logMysqlFallback(PDOException $e): void {
        $message = 'MySQL connection failed, falling back to SQLite: ' . $e->getMessage();
        if (function_exists('am_log')) {
            am_log($message, 'ERROR');
        } else {
            error_log('[Database] ' . $message);
        }
    }

    private function connectSQLite() {
        $dbPath = __DIR__ . '/../storage/database.sqlite';
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0775, true);
        }
        if (!is_writable($dbDir)) {
            am_log('SQLite storage directory is not writable: ' . $dbDir, 'ERROR');
        }
        if (!is_file($dbPath)) {
            touch($dbPath);
            @chmod($dbPath, 0664);
        }

        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // SQLite no permite reutilizar el mismo placeholder nombrado en una consulta.
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
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
