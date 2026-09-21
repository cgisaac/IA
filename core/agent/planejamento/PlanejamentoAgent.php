<?php

namespace GestaoTI\Agent\Planejamento;

use GestaoTI\Core\Database\Database;
use Exception;

/**
 * Agente de Planejamento - Gerencia tarefas periódicas e escalonamento
 * Responsável por:
 * - Criação de tarefas diárias, semanais, mensais
 * - Escalonamento de responsabilidade entre técnicos
 * - Notificações de tarefas pendentes
 * - Controle de ausência para repasse de tarefas
 */
class PlanejamentoAgent {
    private Database $db;
    private int $defaultEscalationTime; // em minutos

    public function __construct() {
        $this->db = Database::getInstance();
        $config = getConfig();
        $this->defaultEscalationTime = $config['settings']['escalation_time'] ?? 120;
    }

    /**
     * Cria nova tarefa periódica
     */
    public function createPeriodicTask(
        string $taskName,
        string $description,
        string $frequency,
        ?string $scheduleTime = null,
        ?int $dayOfWeek = null,
        ?int $dayOfMonth = null,
        int $createdBy = 0
    ): array {
        try {
            // Validar frequência
            $validFrequencies = ['daily', 'weekly', 'monthly'];
            if (!in_array($frequency, $validFrequencies)) {
                throw new Exception("Frequência inválida. Use: daily, weekly, monthly");
            }

            // Validar dia da semana para tarefas semanais
            if ($frequency === 'weekly' && ($dayOfWeek === null || $dayOfWeek < 0 || $dayOfWeek > 6)) {
                throw new Exception("Dia da semana deve ser entre 0 (domingo) e 6 (sábado)");
            }

            // Validar dia do mês para tarefas mensais
            if ($frequency === 'monthly' && ($dayOfMonth === null || $dayOfMonth < 1 || $dayOfMonth > 31)) {
                throw new Exception("Dia do mês deve ser entre 1 e 31");
            }

            $sql = "INSERT INTO periodic_tasks (
                task_name, description, frequency, schedule_time, day_of_week, day_of_month, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $this->db->query($sql, [
                $taskName,
                $description,
                $frequency,
                $scheduleTime ?: '09:00:00',
                $dayOfWeek,
                $dayOfMonth,
                $createdBy
            ]);

            $taskId = $this->db->lastInsertId();

            return [
                'success' => true,
                'task_id' => $taskId,
                'message' => 'Tarefa criada com sucesso'
            ];

        } catch (Exception $e) {
            error_log("Erro ao criar tarefa periódica: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Adiciona técnico ao escalonamento de uma tarefa
     */
    public function addTechnicianToEscalation(
        int $taskId,
        int $technicianId,
        int $escalationOrder,
        ?int $maxResponseTime = null
    ): array {
        try {
            $sql = "INSERT INTO task_escalation (
                task_id, technician_id, escalation_order, max_response_time
            ) VALUES (?, ?, ?, ?)";

            $this->db->query($sql, [
                $taskId,
                $technicianId,
                $escalationOrder,
                $maxResponseTime ?: $this->defaultEscalationTime
            ]);

            return [
                'success' => true,
                'message' => 'Técnico adicionado ao escalonamento'
            ];

        } catch (Exception $e) {
            error_log("Erro ao adicionar técnico ao escalonamento: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém tarefa que deve ser executada hoje
     */
    public function getTodaysTasks(): array {
        try {
            $today = date('w'); // Dia da semana (0-6)
            $dayOfMonth = (int)date('j'); // Dia do mês (1-31)

            $sql = "SELECT pt.*, 
                           GROUP_CONCAT(CONCAT(u.username, ':', te.escalation_order) ORDER BY te.escalation_order SEPARATOR '|') as technicians
                    FROM periodic_tasks pt
                    LEFT JOIN task_escalation te ON pt.id = te.task_id
                    LEFT JOIN users u ON te.technician_id = u.id
                    WHERE pt.is_active = TRUE
                    AND (
                        (pt.frequency = 'daily')
                        OR (pt.frequency = 'weekly' AND pt.day_of_week = ?)
                        OR (pt.frequency = 'monthly' AND pt.day_of_month = ?)
                    )
                    GROUP BY pt.id";

            $result = $this->db->query($sql, [$today, $dayOfMonth]);
            $tasks = $result->fetchAll();

            // Processar lista de técnicos
            foreach ($tasks as &$task) {
                $task['technicians_list'] = $this->parseTechniciansList($task['technicians']);
            }

            return $tasks;

        } catch (Exception $e) {
            error_log("Erro ao buscar tarefas do dia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parseia lista de técnicos do formato concatenado
     */
    private function parseTechniciansList(?string $technicians): array {
        if (!$technicians) {
            return [];
        }

        $list = explode('|', $technicians);
        $result = [];

        foreach ($list as $item) {
            $parts = explode(':', $item);
            if (count($parts) === 2) {
                $result[] = [
                    'username' => $parts[0],
                    'order' => (int)$parts[1]
                ];
            }
        }

        usort($result, fn($a, $b) => $a['order'] - $b['order']);
        return $result;
    }

    /**
     * Obtém técnico responsável atual por uma tarefa
     * Considera ausências e tempo de escalonamento
     */
    public function getCurrentResponsible(int $taskId): ?array {
        try {
            // Obter todos os técnicos no escalonamento
            $sql = "SELECT te.*, u.username, u.is_active
                    FROM task_escalation te
                    JOIN users u ON te.technician_id = u.id
                    WHERE te.task_id = ?
                    ORDER BY te.escalation_order";

            $result = $this->db->query($sql, [$taskId]);
            $technicians = $result->fetchAll();

            if (empty($technicians)) {
                return null;
            }

            // Verificar cada técnico na ordem de escalonamento
            foreach ($technicians as $tech) {
                $status = $this->checkTechnicianAvailability($tech['technician_id'], $tech['max_response_time']);
                
                if ($status['available']) {
                    return [
                        'technician_id' => $tech['technician_id'],
                        'username' => $tech['username'],
                        'escalation_order' => $tech['escalation_order'],
                        'is_available' => true
                    ];
                }
            }

            // Se nenhum estiver disponível, retornar o último da lista
            $lastTech = end($technicians);
            return [
                'technician_id' => $lastTech['technician_id'],
                'username' => $lastTech['username'],
                'escalation_order' => $lastTech['escalation_order'],
                'is_available' => false
            ];

        } catch (Exception $e) {
            error_log("Erro ao buscar responsável atual: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica disponibilidade do técnico
     * Considera: status ativo, última atividade, tempo de resposta
     */
    private function checkTechnicianAvailability(int $technicianId, int $maxResponseTime): array {
        try {
            // Verificar se técnico está ativo
            $sql = "SELECT is_active FROM users WHERE id = ?";
            $result = $this->db->query($sql, [$technicianId]);
            $user = $result->fetch();

            if (!$user || !$user['is_active']) {
                return ['available' => false, 'reason' => 'inactive'];
            }

            // Verificar última atividade
            $sql = "SELECT activity_type, start_time, end_time 
                    FROM technician_activities 
                    WHERE technician_id = ? 
                    ORDER BY start_time DESC LIMIT 1";

            $result = $this->db->query($sql, [$technicianId]);
            $lastActivity = $result->fetch();

            if (!$lastActivity) {
                // Nunca realizou atividade, considerar disponível
                return ['available' => true];
            }

            // Verificar se está em pausa, almoço ou externo
            $inactivityTypes = ['pause', 'lunch', 'external'];
            if (in_array($lastActivity['activity_type'], $inactivityTypes) && $lastActivity['end_time'] === null) {
                return ['available' => false, 'reason' => 'busy'];
            }

            // Calcular tempo desde última atividade
            $lastActivityTime = strtotime($lastActivity['start_time']);
            $now = time();
            $minutesSinceLastActivity = ($now - $lastActivityTime) / 60;

            // Se passou do tempo máximo de resposta sem atividade, considerar indisponível
            if ($minutesSinceLastActivity > $maxResponseTime) {
                return ['available' => false, 'reason' => 'timeout'];
            }

            return ['available' => true];

        } catch (Exception $e) {
            error_log("Erro ao verificar disponibilidade: " . $e->getMessage());
            return ['available' => false, 'reason' => 'error'];
        }
    }

    /**
     * Registra assume de tarefa por técnico
     */
    public function assumeTask(int $taskId, int $technicianId): array {
        try {
            $sql = "UPDATE task_escalation 
                    SET status = 'assumed', assumed_at = NOW()
                    WHERE task_id = ? AND technician_id = ?";

            $this->db->query($sql, [$taskId, $technicianId]);

            // Registrar atividade
            $sql = "INSERT INTO technician_activities (
                technician_id, activity_type, start_time, description
            ) VALUES (?, 'periodic_task', NOW(), ?)";

            $taskInfo = $this->getTaskInfo($taskId);
            $this->db->query($sql, [$technicianId, "Tarefa periódica: " . ($taskInfo['task_name'] ?? 'Desconhecida')]);

            return [
                'success' => true,
                'message' => 'Tarefa assumida com sucesso'
            ];

        } catch (Exception $e) {
            error_log("Erro ao assumir tarefa: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Completa tarefa
     */
    public function completeTask(int $taskId, int $technicianId): array {
        try {
            $sql = "UPDATE task_escalation 
                    SET status = 'completed', completed_at = NOW()
                    WHERE task_id = ? AND technician_id = ?";

            $this->db->query($sql, [$taskId, $technicianId]);

            // Atualizar duração da atividade
            $sql = "UPDATE technician_activities 
                    SET end_time = NOW(),
                        duration = TIMESTAMPDIFF(SECOND, start_time, NOW())
                    WHERE technician_id = ? 
                    AND activity_type = 'periodic_task' 
                    AND end_time IS NULL
                    ORDER BY start_time DESC LIMIT 1";

            $this->db->query($sql, [$technicianId]);

            return [
                'success' => true,
                'message' => 'Tarefa completada com sucesso'
            ];

        } catch (Exception $e) {
            error_log("Erro ao completar tarefa: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém informações de uma tarefa
     */
    private function getTaskInfo(int $taskId): ?array {
        try {
            $sql = "SELECT * FROM periodic_tasks WHERE id = ?";
            $result = $this->db->query($sql, [$taskId]);
            return $result->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Obtém todas as tarefas de um técnico
     */
    public function getTechnicianTasks(int $technicianId, ?string $status = null): array {
        try {
            $where = "te.technician_id = ?";
            $params = [$technicianId];

            if ($status) {
                $where .= " AND te.status = ?";
                $params[] = $status;
            }

            $sql = "SELECT te.*, pt.task_name, pt.description, pt.frequency
                    FROM task_escalation te
                    JOIN periodic_tasks pt ON te.task_id = pt.id
                    WHERE $where
                    ORDER BY te.escalation_order, te.created_at DESC";

            $result = $this->db->query($sql, $params);
            return $result->fetchAll();

        } catch (Exception $e) {
            error_log("Erro ao buscar tarefas do técnico: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca técnico como ausente para escalonamento
     */
    public function markTechnicianAbsent(int $technicianId, string $reason, ?string $until = null): array {
        try {
            // Esta implementação poderia usar uma tabela de ausências
            // Por enquanto, apenas logamos a ausência
            error_log("Técnico $technicianId marcado como ausente: $reason até " . ($until ?? 'indeterminado'));

            return [
                'success' => true,
                'message' => 'Ausência registrada'
            ];

        } catch (Exception $e) {
            error_log("Erro ao marcar ausência: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Desativa/Ativa tarefa periódica
     */
    public function toggleTaskStatus(int $taskId): array {
        try {
            $sql = "UPDATE periodic_tasks 
                    SET is_active = NOT is_active 
                    WHERE id = ?";

            $this->db->query($sql, [$taskId]);

            return [
                'success' => true,
                'message' => 'Status da tarefa alterado'
            ];

        } catch (Exception $e) {
            error_log("Erro ao alterar status da tarefa: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove técnico do escalonamento
     */
    public function removeTechnicianFromEscalation(int $taskId, int $technicianId): array {
        try {
            $sql = "DELETE FROM task_escalation 
                    WHERE task_id = ? AND technician_id = ?";

            $this->db->query($sql, [$taskId, $technicianId]);

            // Reordenar escalonamento
            $sql = "UPDATE task_escalation 
                    SET escalation_order = escalation_order - 1
                    WHERE task_id = ? AND escalation_order > (
                        SELECT MAX(escalation_order) FROM (
                            SELECT escalation_order FROM task_escalation 
                            WHERE task_id = ? AND technician_id = ?
                        ) as temp
                    )";

            $this->db->query($sql, [$taskId, $taskId, $technicianId]);

            return [
                'success' => true,
                'message' => 'Técnico removido do escalonamento'
            ];

        } catch (Exception $e) {
            error_log("Erro ao remover técnico: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
