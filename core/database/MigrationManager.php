<?php

namespace GestaoTI\Core\Database;

use Exception;

/**
 * Classe MigrationManager - Gerencia versionamento e migrações do banco de dados
 * Cria e atualiza tabelas automaticamente conforme versão do sistema
 */
class MigrationManager {
    private Database $db;
    private string $currentVersion;
    private array $migrations = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $config = getConfig();
        $this->currentVersion = $config['version'];
        $this->defineMigrations();
    }

    /**
     * Define todas as migrações do sistema
     */
    private function defineMigrations(): void {
        $this->migrations = [
            '1.0.0' => [
                'system_version',
                'system_settings',
                'users',
                'glpi_integration',
                'tickets',
                'ticket_time_tracking',
                'periodic_tasks',
                'task_escalation',
                'technician_activities',
                'knowledge_base'
            ]
        ];
    }

    /**
     * Verifica e executa migrações necessárias
     */
    public function runMigrations(): bool {
        try {
            // Selecionar banco de dados
            $this->db->selectDatabase();

            // Criar tabela de versionamento se não existir
            $this->createVersionTable();

            // Obter versão atual do banco
            $dbVersion = $this->getDatabaseVersion();

            // Executar migrações pendentes
            foreach ($this->migrations as $version => $tables) {
                if (version_compare($version, $dbVersion, '>')) {
                    $this->runMigration($version, $tables);
                    $this->updateVersion($version);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Erro na migração: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria tabela de versionamento
     */
    private function createVersionTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS system_version (
            id INT PRIMARY KEY AUTO_INCREMENT,
            version VARCHAR(20) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            description TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Obtém versão atual do banco de dados
     */
    private function getDatabaseVersion(): string {
        try {
            $result = $this->db->query("SELECT version FROM system_version ORDER BY id DESC LIMIT 1");
            $row = $result->fetch();
            return $row ? $row['version'] : '0.0.0';
        } catch (Exception $e) {
            return '0.0.0';
        }
    }

    /**
     * Atualiza versão no banco de dados
     */
    private function updateVersion(string $version): void {
        $sql = "INSERT INTO system_version (version, description) VALUES (?, ?)";
        $this->db->query($sql, [$version, "Migração para versão $version"]);
    }

    /**
     * Executa migração para uma versão específica
     */
    private function runMigration(string $version, array $tables): void {
        foreach ($tables as $table) {
            $methodName = 'create' . ucfirst($table) . 'Table';
            if (method_exists($this, $methodName)) {
                $this->$methodName();
            }
        }
    }

    /**
     * Cria tabela system_settings
     */
    private function createSystemSettingsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS system_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            config_key VARCHAR(100) UNIQUE NOT NULL,
            config_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT,
            INDEX idx_config_key (config_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);

        // Inserir configurações padrão
        $this->insertDefaultSettings();
    }

    /**
     * Insere configurações padrão no sistema
     */
    private function insertDefaultSettings(): void {
        $defaultSettings = [
            'sync_interval' => 300,
            'notification_polling' => 30,
            'escalation_time' => 120,
            'default_category_id' => null,
            'max_attendance_time' => 8,
            'lunch_break' => 60
        ];

        foreach ($defaultSettings as $key => $value) {
            $sql = "INSERT IGNORE INTO system_settings (config_key, config_value) VALUES (?, ?)";
            $this->db->query($sql, [$key, json_encode($value)]);
        }
    }

    /**
     * Cria tabela users
     */
    private function createUsersTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role ENUM('administrator', 'manager', 'technician', 'viewer') NOT NULL DEFAULT 'technician',
            glpi_user_id INT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);

        // Criar usuário admin inicial
        $this->createInitialAdmin();
    }

    /**
     * Cria usuário administrador inicial
     */
    private function createInitialAdmin(): void {
        $config = getConfig();
        $initialUser = $config['initial_user'];
        
        $passwordHash = password_hash($initialUser['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT IGNORE INTO users (username, password_hash, role) VALUES (?, ?, ?)";
        $this->db->query($sql, [$initialUser['username'], $passwordHash, $initialUser['role']]);
    }

    /**
     * Cria tabela glpi_integration
     */
    private function createGlpiIntegrationTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS glpi_integration (
            id INT PRIMARY KEY AUTO_INCREMENT,
            glpi_url VARCHAR(255) NOT NULL,
            app_token VARCHAR(255),
            user_token VARCHAR(255),
            session_token VARCHAR(255),
            token_expires_at TIMESTAMP NULL,
            ssl_verify BOOLEAN DEFAULT FALSE,
            last_sync TIMESTAMP NULL,
            sync_status ENUM('pending', 'success', 'error') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Cria tabela tickets (cache local dos chamados do GLPI)
     */
    private function createTicketsTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS tickets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            glpi_ticket_id INT NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            status INT NOT NULL,
            priority INT,
            category_id INT,
            requester_id INT,
            technician_id INT,
            group_id INT,
            date_opened TIMESTAMP NOT NULL,
            date_closed TIMESTAMP NULL,
            date_modified TIMESTAMP NULL,
            sla_due_date TIMESTAMP NULL,
            is_overdue BOOLEAN DEFAULT FALSE,
            synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_glpi_id (glpi_ticket_id),
            INDEX idx_status (status),
            INDEX idx_technician (technician_id),
            INDEX idx_overdue (is_overdue)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Cria tabela ticket_time_tracking
     */
    private function createTicketTimeTrackingTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS ticket_time_tracking (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ticket_id INT NOT NULL,
            technician_id INT NOT NULL,
            start_time TIMESTAMP NOT NULL,
            end_time TIMESTAMP NULL,
            pause_time INT DEFAULT 0, -- em segundos
            lunch_break INT DEFAULT 0, -- em segundos
            external_service INT DEFAULT 0, -- em segundos
            total_attendance INT DEFAULT 0, -- em segundos
            is_active BOOLEAN DEFAULT FALSE,
            is_paused BOOLEAN DEFAULT FALSE,
            activity_type ENUM('attendance', 'pause', 'lunch', 'external') DEFAULT 'attendance',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket (ticket_id),
            INDEX idx_technician (technician_id),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Cria tabela periodic_tasks
     */
    private function createPeriodicTasksTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS periodic_tasks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            task_name VARCHAR(255) NOT NULL,
            description TEXT,
            frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
            schedule_time TIME,
            day_of_week INT, -- 0-6 para semanal
            day_of_month INT, -- 1-31 para mensal
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_frequency (frequency),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Cria tabela task_escalation
     */
    private function createTaskEscalationTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS task_escalation (
            id INT PRIMARY KEY AUTO_INCREMENT,
            task_id INT NOT NULL,
            technician_id INT NOT NULL,
            escalation_order INT NOT NULL,
            max_response_time INT DEFAULT 120, -- em minutos
            is_notified BOOLEAN DEFAULT FALSE,
            notified_at TIMESTAMP NULL,
            assumed_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            status ENUM('pending', 'assumed', 'completed', 'escalated') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES periodic_tasks(id) ON DELETE CASCADE,
            INDEX idx_task (task_id),
            INDEX idx_technician (technician_id),
            INDEX idx_order (escalation_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Cria tabela technician_activities
     */
    private function createTechnicianActivitiesTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS technician_activities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            technician_id INT NOT NULL,
            ticket_id INT NULL,
            activity_type ENUM('ticket_attendance', 'periodic_task', 'pause', 'lunch', 'external', 'idle') NOT NULL,
            start_time TIMESTAMP NOT NULL,
            end_time TIMESTAMP NULL,
            duration INT DEFAULT 0, -- em segundos
            description TEXT,
            efficiency_rating DECIMAL(3,2) DEFAULT 0.00,
            knowledge_base_created BOOLEAN DEFAULT FALSE,
            knowledge_base_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_technician (technician_id),
            INDEX idx_ticket (ticket_id),
            INDEX idx_type (activity_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Cria tabela knowledge_base
     */
    private function createKnowledgeBaseTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS knowledge_base (
            id INT PRIMARY KEY AUTO_INCREMENT,
            glpi_kb_id INT UNIQUE,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            category_id INT,
            technician_id INT,
            ticket_id INT,
            views INT DEFAULT 0,
            is_published BOOLEAN DEFAULT FALSE,
            synced_with_glpi BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_glpi_id (glpi_kb_id),
            INDEX idx_published (is_published)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->query($sql);
    }

    /**
     * Obtém configuração do sistema
     */
    public function getSetting(string $key, $default = null) {
        try {
            $sql = "SELECT config_value FROM system_settings WHERE config_key = ?";
            $result = $this->db->query($sql, [$key]);
            $row = $result->fetch();
            
            if ($row) {
                return json_decode($row['config_value'], true);
            }
            
            return $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Atualiza configuração do sistema
     */
    public function updateSetting(string $key, $value, int $userId = null): bool {
        try {
            $sql = "INSERT INTO system_settings (config_key, config_value, updated_by) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_by = VALUES(updated_by)";
            
            $this->db->query($sql, [$key, json_encode($value), $userId]);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar configuração: " . $e->getMessage());
            return false;
        }
    }
}
