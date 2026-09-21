<?php

namespace GestaoTI\Agent\Infra;

use GestaoTI\Core\Database\Database;
use GestaoTI\Core\Database\MigrationManager;
use Exception;
use PDOException;

/**
 * Agente de Infraestrutura - Gerencia configuração, banco de dados e integração GLPI
 * Responsável por:
 * - Configuração do sistema
 * - Conexão com GLPI via API
 * - Sincronização de dados
 * - Versionamento do sistema
 */
class InfraAgent {
    private Database $db;
    private MigrationManager $migrationManager;
    private ?string $sessionToken = null;
    private array $glpiConfig;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->migrationManager = new MigrationManager();
        $this->glpiConfig = getConfig()['glpi'];
        
        // Executar migrações se necessário
        if (!$this->db->isInstalled()) {
            $this->migrationManager->runMigrations();
        }
    }

    /**
     * Inicializa o sistema (instalação/setup inicial)
     */
    public function initialize(): bool {
        try {
            return $this->migrationManager->runMigrations();
        } catch (Exception $e) {
            error_log("Erro na inicialização: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza configurações do sistema
     */
    public function updateSystemConfig(array $config, int $userId = null): bool {
        foreach ($config as $key => $value) {
            if (!$this->migrationManager->updateSetting($key, $value, $userId)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtém configuração do sistema
     */
    public function getSystemConfig(string $key = null) {
        if ($key === null) {
            return [
                'sync_interval' => $this->migrationManager->getSetting('sync_interval', 300),
                'notification_polling' => $this->migrationManager->getSetting('notification_polling', 30),
                'escalation_time' => $this->migrationManager->getSetting('escalation_time', 120),
                'default_category_id' => $this->migrationManager->getSetting('default_category_id'),
                'max_attendance_time' => $this->migrationManager->getSetting('max_attendance_time', 8),
                'lunch_break' => $this->migrationManager->getSetting('lunch_break', 60)
            ];
        }
        
        return $this->migrationManager->getSetting($key);
    }

    /**
     * Configura integração com GLPI
     */
    public function configureGLPI(string $url, string $appToken, string $userToken, bool $sslVerify = false): bool {
        try {
            $sql = "INSERT INTO glpi_integration (glpi_url, app_token, user_token, ssl_verify) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        glpi_url = VALUES(glpi_url),
                        app_token = VALUES(app_token),
                        user_token = VALUES(user_token),
                        ssl_verify = VALUES(ssl_verify)";
            
            $this->db->query($sql, [$url, $appToken, $userToken, $sslVerify]);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao configurar GLPI: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém configurações do GLPI
     */
    public function getGLPIConfig(): ?array {
        try {
            $sql = "SELECT * FROM glpi_integration ORDER BY id DESC LIMIT 1";
            $result = $this->db->query($sql);
            return $result->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Autentica na API do GLPI
     */
    public function authenticateGLPI(): ?string {
        try {
            $glpiConfig = $this->getGLPIConfig();
            
            if (!$glpiConfig) {
                throw new Exception("GLPI não configurado");
            }

            // Verificar se token de sessão ainda é válido
            if ($glpiConfig['session_token'] && $glpiConfig['token_expires_at']) {
                if (strtotime($glpiConfig['token_expires_at']) > time()) {
                    return $glpiConfig['session_token'];
                }
            }

            // Fazer autenticação na API do GLPI
            $url = rtrim($glpiConfig['glpi_url'], '/') . '/apirest.php/initSession';
            
            $headers = [
                'Content-Type: application/json',
                'App-Token: ' . $glpiConfig['app_token'],
                'Authorization: Basic ' . base64_encode('user_token:' . $glpiConfig['user_token'])
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $glpiConfig['ssl_verify']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("Erro na autenticação GLPI: HTTP $httpCode");
            }

            $data = json_decode($response, true);
            
            if (!isset($data['session_token'])) {
                throw new Exception("Token de sessão não retornado pelo GLPI");
            }

            $this->sessionToken = $data['session_token'];
            
            // Atualizar token no banco
            $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours'));
            $sql = "UPDATE glpi_integration SET session_token = ?, token_expires_at = ? WHERE id = ?";
            $this->db->query($sql, [$this->sessionToken, $expiresAt, $glpiConfig['id']]);

            return $this->sessionToken;
            
        } catch (Exception $e) {
            error_log("Erro na autenticação GLPI: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Faz requisição à API do GLPI
     */
    public function requestGLPI(string $endpoint, string $method = 'GET', array $data = []) {
        try {
            $sessionToken = $this->sessionToken ?? $this->authenticateGLPI();
            
            if (!$sessionToken) {
                throw new Exception("Não autenticado no GLPI");
            }

            $glpiConfig = $this->getGLPIConfig();
            $url = rtrim($glpiConfig['glpi_url'], '/') . '/apirest.php' . $endpoint;

            $headers = [
                'Content-Type: application/json',
                'Session-Token: ' . $sessionToken,
                'App-Token: ' . $glpiConfig['app_token']
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $glpiConfig['ssl_verify']);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } elseif ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } elseif ($method === 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                throw new Exception("Erro na requisição GLPI: HTTP $httpCode");
            }

            return json_decode($response, true);
            
        } catch (Exception $e) {
            error_log("Erro na requisição GLPI: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sincroniza chamados do GLPI
     */
    public function syncTickets(): bool {
        try {
            // Buscar tickets do GLPI
            $tickets = $this->requestGLPI('/Ticket');
            
            if (!$tickets) {
                return false;
            }

            $this->db->beginTransaction();

            foreach ($tickets as $ticket) {
                $this->syncSingleTicket($ticket);
            }

            // Atualizar data da última sincronização
            $sql = "UPDATE glpi_integration SET last_sync = NOW(), sync_status = 'success' WHERE id = (SELECT id FROM glpi_integration ORDER BY id DESC LIMIT 1)";
            $this->db->query($sql);

            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro na sincronização de tickets: " . $e->getMessage());
            
            // Atualizar status de erro
            $sql = "UPDATE glpi_integration SET sync_status = 'error' WHERE id = (SELECT id FROM glpi_integration ORDER BY id DESC LIMIT 1)";
            $this->db->query($sql);
            
            return false;
        }
    }

    /**
     * Sincroniza um único ticket
     */
    private function syncSingleTicket(array $ticket): void {
        $sql = "INSERT INTO tickets (
            glpi_ticket_id, title, description, status, priority, category_id,
            requester_id, technician_id, group_id, date_opened, date_closed,
            date_modified, sla_due_date, is_overdue, synced_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            status = VALUES(status),
            priority = VALUES(priority),
            category_id = VALUES(category_id),
            requester_id = VALUES(requester_id),
            technician_id = VALUES(technician_id),
            group_id = VALUES(group_id),
            date_closed = VALUES(date_closed),
            date_modified = VALUES(date_modified),
            sla_due_date = VALUES(sla_due_date),
            is_overdue = VALUES(is_overdue),
            synced_at = NOW()";

        $isOverdue = isset($ticket['slas_id']) && 
                     isset($ticket['time_to_resolve']) && 
                     strtotime($ticket['time_to_resolve']) < time();

        $this->db->query($sql, [
            $ticket['id'],
            $ticket['name'] ?? '',
            $ticket['content'] ?? '',
            $ticket['status'] ?? 1,
            $ticket['priority'] ?? 3,
            $ticket['itilcategories_id'] ?? null,
            $ticket['users_id_recipient'] ?? null,
            $ticket['users_id_assign'] ?? null,
            $ticket['groups_id_assign'] ?? null,
            $ticket['date'] ?? date('Y-m-d H:i:s'),
            isset($ticket['closedate']) ? $ticket['closedate'] : null,
            isset($ticket['solvedate']) ? $ticket['solvedate'] : null,
            isset($ticket['time_to_resolve']) ? $ticket['time_to_resolve'] : null,
            $isOverdue ? 1 : 0
        ]);
    }

    /**
     * Obtém versão atual do sistema
     */
    public function getVersion(): string {
        return getConfig()['version'];
    }

    /**
     * Verifica se há atualizações disponíveis
     */
    public function checkForUpdates(): bool {
        // Implementar lógica de verificação de updates
        // Por enquanto retorna false
        return false;
    }

    /**
     * Testa conexão com GLPI
     */
    public function testGLPIConnection(): array {
        try {
            $token = $this->authenticateGLPI();
            
            if ($token) {
                // Testar requisição simples
                $entity = $this->requestGLPI('/Entity');
                
                return [
                    'success' => true,
                    'message' => 'Conexão com GLPI estabelecida com sucesso',
                    'authenticated' => true,
                    'entities_count' => is_array($entity) ? count($entity) : 0
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Falha na autenticação',
                'authenticated' => false
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'authenticated' => false
            ];
        }
    }
}
