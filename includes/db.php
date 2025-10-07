<?php
/**
 * Database Connection and Query Management
 */

// Include database configuration constants
require_once __DIR__ . '/../config/database.php';

// The classes PDO, PDOException, and PDOStatement are in the global namespace,
// so you don't need 'use' statements.

class Database {
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed. Error: " . $e->getMessage());
            die("🔴 Database connection failed. Please try again later.");
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new PDOException("Failed to prepare statement: " . $sql);
            }
            $result = $stmt->execute($params);
            if (!$result) {
                throw new PDOException("Failed to execute statement: " . $sql);
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed. SQL: " . $sql . ". Error: " . $e->getMessage());
            throw $e; // Re-throw to ensure errors are not silently ignored
        }
    }

    public function fetchRow(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []): mixed {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Fetches a single, scalar value from the database query.
     * @param string $sql The SQL query.
     * @param array $params The parameters for the prepared statement.
     * @return mixed The value of the cell, or null if no result is found.
     */
    public function fetchCell(string $sql, array $params = []): mixed {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result ? $result[0] : null;
    }

    public function lastInsertId(): string|false {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool {
        return $this->connection->commit();
    }

    public function rollBack(): bool {
        return $this->connection->rollBack();
    }
}

function db(): Database {
    return Database::getInstance();
}