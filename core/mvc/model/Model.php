<?php
namespace Core\MVC\Model;

use Core\Database\Database;

/**
 * Model Base - Classe abstrata para todos os models
 * Responsabilidade única: Gerenciar acesso a dados
 */
abstract class Model
{
    protected \PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Busca registro por ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Busca todos os registros
     */
    public function findAll(array $where = [], string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = ?";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($where));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Insere novo registro
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Atualiza registro
     */
    public function update(int $id, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        
        $values = array_values($data);
        $values[] = $id;
        
        return $stmt->execute($values);
    }
    
    /**
     * Deleta registro
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Conta registros
     */
    public function count(array $where = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = ?";
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($where));
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int) $result['total'];
    }
    
    /**
     * Executa query customizada
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
