<?php

namespace GestaoTI\Core\Database;

use PDO;
use PDOException;
use Exception;

/**
 * Classe Database - Gerencia conexão e operações com banco de dados MySQL
 * Implementa padrão Singleton para garantir única instância de conexão
 */
class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;
    private bool $installed = false;

    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        $this->config = getConfig()['database'];
        $this->connect();
        $this->checkInstallation();
    }

    /**
     * Obtém instância única da classe
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Estabelece conexão com o banco de dados
     */
    private function connect(): void {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};charset={$this->config['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Se não conseguir conectar, verifica se é erro de banco inexistente
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $this->createDatabase();
                $this->connect(); // Tenta conectar novamente
            } else {
                throw new Exception("Erro de conexão com banco de dados: " . $e->getMessage());
            }
        }
    }

    /**
     * Cria o banco de dados se não existir
     */
    private function createDatabase(): void {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};charset={$this->config['charset']}";
            
            $tempConnection = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['password']
            );
            
            $tempConnection->exec("CREATE DATABASE IF NOT EXISTS `{$this->config['name']}` CHARACTER SET {$this->config['charset']} COLLATE utf8mb4_unicode_ci");
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao criar banco de dados: " . $e->getMessage());
        }
    }

    /**
     * Verifica se o sistema está instalado
     */
    private function checkInstallation(): void {
        try {
            $this->connection->query("SELECT 1 FROM system_version LIMIT 1");
            $this->installed = true;
        } catch (PDOException $e) {
            $this->installed = false;
        }
    }

    /**
     * Retorna se o sistema está instalado
     */
    public function isInstalled(): bool {
        return $this->installed;
    }

    /**
     * Seleciona o banco de dados correto após criação
     */
    public function selectDatabase(): void {
        $this->connection->exec("USE `{$this->config['name']}`");
    }

    /**
     * Executa uma query SQL
     */
    public function query(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Erro na execução da query: " . $e->getMessage());
        }
    }

    /**
     * Inicia uma transação
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit de uma transação
     */
    public function commit(): bool {
        return $this->connection->commit();
    }

    /**
     * Rollback de uma transação
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }

    /**
     * Retorna o último ID inserido
     */
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }

    /**
     * Obtém a conexão PDO diretamente
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Impede clonagem da instância
     */
    private function __clone() {}

    /**
     * Impede desserialização da instância
     */
    public function __wakeup() {
        throw new Exception("Não é possível desserializar esta classe");
    }
}
