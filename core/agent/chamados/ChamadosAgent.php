<?php

namespace GestaoTI\Agent\Chamados;

use GestaoTI\Core\Database\Database;
use GestaoTI\Agent\Infra\InfraAgent;
use Exception;

/**
 * Agente de Chamados - Gerencia ciclo de vida dos tickets
 * Responsável por:
 * - CRUD de chamados
 * - Abertura rápida de chamados no GLPI
 * - Status e acompanhamento de tickets
 * - Vinculação com técnicos
 */
class ChamadosAgent {
    private Database $db;
    private InfraAgent $infraAgent;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->infraAgent = new InfraAgent();
    }

    /**
     * Abre chamado rápido no GLPI
     * @param int $requesterId ID do usuário solicitante no GLPI
     * @param string $title Título do chamado
     * @param string $description Descrição do problema
     * @param int|null $categoryId Categoria do chamado (usa padrão se null)
     * @return array|false Dados do chamado criado ou false em caso de erro
     */
    public function openQuickTicket(int $requesterId, string $title, string $description, ?int $categoryId = null): array|false {
        try {
            // Obter categoria padrão se não fornecida
            if ($categoryId === null) {
                $categoryId = $this->infraAgent->getSystemConfig('default_category_id');
                
                if ($categoryId === null) {
                    throw new Exception("Categoria padrão não configurada");
                }
            }

            // Preparar dados para API do GLPI
            $ticketData = [
                'input' => [
                    'name' => $title,
                    'content' => $description,
                    '_users_id_requester' => $requesterId,
                    'itilcategories_id' => $categoryId,
                    'priority' => 3, // Prioridade média como padrão
                    'impact' => 3,
                    'urgency' => 3,
                    'type' => 1, // Incidente
                    'source' => 4 // Telefone (pode ser parametrizado)
                ]
            ];

            // Criar chamado no GLPI via API
            $result = $this->infraAgent->requestGLPI('/Ticket', 'POST', $ticketData);

            if (!$result || !isset($result['id'])) {
                throw new Exception("Falha ao criar chamado no GLPI");
            }

            // Sincronizar chamado localmente
            $this->syncTicket($result['id']);

            // Registrar atividade de abertura
            $this->registerOpenActivity($result['id'], $requesterId);

            return [
                'success' => true,
                'ticket_id' => $result['id'],
                'message' => 'Chamado aberto com sucesso'
            ];

        } catch (Exception $e) {
            error_log("Erro ao abrir chamado rápido: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Sincroniza um ticket específico do GLPI
     */
    private function syncTicket(int $glpiTicketId): bool {
        try {
            $ticket = $this->infraAgent->requestGLPI('/Ticket/' . $glpiTicketId);
            
            if (!$ticket) {
                return false;
            }

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
                technician_id = VALUES(technician_id),
                date_closed = VALUES(date_closed),
                date_modified = VALUES(date_modified),
                sla_due_date = VALUES(sla_due_date),
                is_overdue = VALUES(is_overdue),
                synced_at = NOW()";

            $isOverdue = isset($ticket['time_to_resolve']) && 
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

            return true;

        } catch (Exception $e) {
            error_log("Erro ao sincronizar ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra atividade de abertura de chamado
     */
    private function registerOpenActivity(int $ticketId, int $requesterId): void {
        $sql = "INSERT INTO technician_activities (
            technician_id, ticket_id, activity_type, start_time, description
        ) VALUES (?, ?, 'ticket_attendance', NOW(), ?)";
        
        $this->db->query($sql, [$requesterId, $ticketId, 'Abertura de chamado']);
    }

    /**
     * Obtém lista de chamados com filtros
     */
    public function getTickets(array $filters = []): array {
        try {
            $where = ['1=1'];
            $params = [];

            if (isset($filters['status'])) {
                $where[] = "t.status = ?";
                $params[] = $filters['status'];
            }

            if (isset($filters['technician_id'])) {
                $where[] = "t.technician_id = ?";
                $params[] = $filters['technician_id'];
            }

            if (isset($filters['is_overdue'])) {
                $where[] = "t.is_overdue = ?";
                $params[] = $filters['is_overdue'] ? 1 : 0;
            }

            if (isset($filters['search'])) {
                $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql = "SELECT t.*, 
                           u.username as technician_name,
                           TIMESTAMPDIFF(SECOND, t.date_opened, NOW()) as open_seconds
                    FROM tickets t
                    LEFT JOIN users u ON t.technician_id = u.id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY t.is_overdue DESC, t.date_modified DESC";

            $result = $this->db->query($sql, $params);
            return $result->fetchAll();

        } catch (Exception $e) {
            error_log("Erro ao buscar chamados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém detalhes de um chamado específico
     */
    public function getTicketDetails(int $ticketId): ?array {
        try {
            $sql = "SELECT t.*, 
                           u.username as technician_name,
                           r.username as requester_name
                    FROM tickets t
                    LEFT JOIN users u ON t.technician_id = u.id
                    LEFT JOIN users r ON t.requester_id = r.id
                    WHERE t.glpi_ticket_id = ?";

            $result = $this->db->query($sql, [$ticketId]);
            return $result->fetch() ?: null;

        } catch (Exception $e) {
            error_log("Erro ao buscar detalhes do chamado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atribui técnico a um chamado
     */
    public function assignTechnician(int $ticketId, int $technicianId): bool {
        try {
            // Atualizar no GLPI
            $result = $this->infraAgent->requestGLPI('/Ticket/' . $ticketId, 'PUT', [
                'input' => [
                    'users_id_assign' => $technicianId
                ]
            ]);

            if (!$result) {
                return false;
            }

            // Atualizar localmente
            $sql = "UPDATE tickets SET technician_id = ?, date_modified = NOW() 
                    WHERE glpi_ticket_id = ?";
            $this->db->query($sql, [$technicianId, $ticketId]);

            return true;

        } catch (Exception $e) {
            error_log("Erro ao atribuir técnico: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fecha chamado no GLPI
     */
    public function closeTicket(int $ticketId, string $solution, int $userId): bool {
        try {
            // Adicionar solução no GLPI
            $solutionData = [
                'input' => [
                    'tickets_id' => $ticketId,
                    'content' => $solution,
                    'users_id' => $userId,
                    'status' => 2 // Aprovado
                ]
            ];

            $result = $this->infraAgent->requestGLPI('/ITILSolution', 'POST', $solutionData);

            if (!$result) {
                return false;
            }

            // Fechar chamado no GLPI
            $closeResult = $this->infraAgent->requestGLPI('/Ticket/' . $ticketId, 'PUT', [
                'input' => [
                    'status' => 6 // Fechado
                ]
            ]);

            if (!$closeResult) {
                return false;
            }

            // Atualizar localmente
            $sql = "UPDATE tickets SET status = 6, date_closed = NOW(), date_modified = NOW() 
                    WHERE glpi_ticket_id = ?";
            $this->db->query($sql, [$ticketId]);

            // Finalizar tracking de tempo
            $this->finalizeTimeTracking($ticketId, $userId);

            return true;

        } catch (Exception $e) {
            error_log("Erro ao fechar chamado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finaliza tracking de tempo do chamado
     */
    private function finalizeTimeTracking(int $ticketId, int $userId): void {
        $sql = "UPDATE ticket_time_tracking 
                SET end_time = NOW(), 
                    is_active = FALSE,
                    total_attendance = TIMESTAMPDIFF(SECOND, start_time, NOW()) - pause_time - lunch_break - external_service
                WHERE ticket_id = ? AND is_active = TRUE";
        
        $this->db->query($sql, [$ticketId]);

        // Registrar atividade final
        $sql = "INSERT INTO technician_activities (
            technician_id, ticket_id, activity_type, start_time, end_time, duration, description
        ) VALUES (?, ?, 'ticket_attendance', NOW(), NOW(), 
                  TIMESTAMPDIFF(SECOND, start_time, NOW()), ?)";
        
        $this->db->query($sql, [$userId, $ticketId, 'Atendimento finalizado']);
    }

    /**
     * Obtém estatísticas de chamados
     */
    public function getStatistics(): array {
        try {
            $stats = [];

            // Total de chamados
            $result = $this->db->query("SELECT COUNT(*) as total FROM tickets");
            $stats['total'] = $result->fetch()['total'];

            // Por status
            $result = $this->db->query("
                SELECT status, COUNT(*) as count 
                FROM tickets 
                GROUP BY status
            ");
            $stats['by_status'] = $result->fetchAll();

            // Atrasados
            $result = $this->db->query("SELECT COUNT(*) as overdue FROM tickets WHERE is_overdue = 1");
            $stats['overdue'] = $result->fetch()['overdue'];

            // Tempo médio de atendimento
            $result = $this->db->query("
                SELECT AVG(TIMESTAMPDIFF(SECOND, date_opened, date_closed)) as avg_time 
                FROM tickets 
                WHERE date_closed IS NOT NULL
            ");
            $stats['avg_resolution_time'] = $result->fetch()['avg_time'] ?? 0;

            return $stats;

        } catch (Exception $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém categorias do GLPI
     */
    public function getCategories(): array {
        try {
            $categories = $this->infraAgent->requestGLPI('/ITILCategory');
            return $categories ?: [];
        } catch (Exception $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém usuários do GLPI
     */
    public function getUsers(): array {
        try {
            $users = $this->infraAgent->requestGLPI('/User');
            return $users ?: [];
        } catch (Exception $e) {
            error_log("Erro ao buscar usuários: " . $e->getMessage());
            return [];
        }
    }
}
