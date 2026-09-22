<?php
namespace GestaoTI\Core\Infrastructure;

class Database
{
    private static $instance = null;
    private $connection;
    private $config;

    private function __construct()
    {
        $this->config = require dirname(dirname(dirname(__DIR__))) . '/config/config.php';
        
        $dsn = "mysql:host={$this->config['db']['host']};dbname={$this->config['db']['database']};charset=utf8mb4";
        
        try {
            $this->connection = new \PDO($dsn, $this->config['db']['username'], $this->config['db']['password']);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::getInstance()->error("Erro de conexão com banco de dados: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return (int) $this->connection->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }
}
