<?php

namespace Core\Agent\Tempo;

use Core\Database\Database;
use Exception;

/**
 * Agente de Tempo - Gerencia cronômetros e produtividade dos técnicos
 * Responsável por:
 * - Tracking de tempo de atendimento
 * - Controle de pausas, almoço e atendimentos externos
 * - Cálculo de ociosidade
 * - Métricas de eficiência
 */
class TempoAgent {
    private Database $db;
    private int $defaultLunchBreak; // em minutos

    public function __construct() {
        $this->db = Database::getInstance();
        $config = getConfig();
        $this->defaultLunchBreak = $config['settings']['lunch_break'] ?? 60;
    }

    /**
     * Inicia atendimento de um chamado
     */
    public function startAttendance(int $ticketId, int $technicianId): array {
        try {
            // Verificar se já existe atendimento ativo para este técnico
            $activeAttendance = $this->getActiveAttendance($technicianId);
            
            if ($activeAttendance) {
                return [
                    'success' => false,
                    'message' => 'Técnico já possui atendimento em andamento',
                    'active_ticket' => $activeAttendance['ticket_id']
                ];
            }

            // Iniciar novo atendimento
            $sql = "INSERT INTO ticket_time_tracking (
                ticket_id, technician_id, start_time, is_active, activity_type
            ) VALUES (?, ?, NOW(), TRUE, 'attendance')";
            
            $this->db->query($sql, [$ticketId, $technicianId]);

            // Registrar atividade
            $activityId = $this->db->lastInsertId();
            
            $sql = "INSERT INTO technician_activities (
                technician_id, ticket_id, activity_type, start_time, description
            ) VALUES (?, ?, 'ticket_attendance', NOW(), 'Início do atendimento')";
            
            $this->db->query($sql, [$technicianId, $ticketId]);

            return [
                'success' => true,
                'message' => 'Atendimento iniciado',
                'tracking_id' => $activityId,
                'start_time' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("Erro ao iniciar atendimento: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Pausa atendimento
     */
    public function pauseAttendance(int $ticketId, int $technicianId): array {
        try {
            // Atualizar tracking para pausado
            $sql = "UPDATE ticket_time_tracking 
                    SET is_paused = TRUE, 
                        pause_time = TIMESTAMPDIFF(SECOND, start_time, NOW())
                    WHERE ticket_id = ? AND technician_id = ? AND is_active = TRUE";
            
            $this->db->query($sql, [$ticketId, $technicianId]);

            // Calcular tempo até a pausa
            $sql = "SELECT start_time FROM ticket_time_tracking 
                    WHERE ticket_id = ? AND technician_id = ? AND is_active = TRUE";
            
            $result = $this->db->query($sql, [$ticketId, $technicianId]);
            $tracking = $result->fetch();

            if (!$tracking) {
                throw new Exception("Tracking não encontrado");
            }

            $pauseDuration = time() - strtotime($tracking['start_time']);

            return [
                'success' => true,
                'message' => 'Atendimento pausado',
                'pause_duration' => $pauseDuration
            ];

        } catch (Exception $e) {
            error_log("Erro ao pausar atendimento: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Retoma atendimento após pausa
     */
    public function resumeAttendance(int $ticketId, int $technicianId): array {
        try {
            $sql = "UPDATE ticket_time_tracking 
                    SET is_paused = FALSE
                    WHERE ticket_id = ? AND technician_id = ? AND is_active = TRUE";
            
            $this->db->query($sql, [$ticketId, $technicianId]);

            return [
                'success' => true,
                'message' => 'Atendimento retomado'
            ];

        } catch (Exception $e) {
            error_log("Erro ao retomar atendimento: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Registra pausa para almoço
     */
    public function registerLunchBreak(int $technicianId): array {
        try {
            // Finalizar qualquer atendimento ativo
            $activeAttendance = $this->getActiveAttendance($technicianId);
            
            if ($activeAttendance) {
                $this->pauseAttendance($activeAttendance['ticket_id'], $technicianId);
            }

            // Registrar almoço
            $sql = "INSERT INTO ticket_time_tracking (
                technician_id, start_time, is_active, activity_type, lunch_break
            ) VALUES (?, NOW(), TRUE, 'lunch', ?)";
            
            $lunchSeconds = $this->defaultLunchBreak * 60;
            $this->db->query($sql, [$technicianId, $lunchSeconds]);

            // Registrar atividade
            $sql = "INSERT INTO technician_activities (
                technician_id, activity_type, start_time, description
            ) VALUES (?, 'lunch', NOW(), 'Pausa para almoço')";
            
            $this->db->query($sql, [$technicianId]);

            return [
                'success' => true,
                'message' => 'Almoço registrado',
                'duration_minutes' => $this->defaultLunchBreak
            ];

        } catch (Exception $e) {
            error_log("Erro ao registrar almoço: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Registra atendimento externo
     */
    public function registerExternalService(int $technicianId, string $description = ''): array {
        try {
            // Finalizar qualquer atendimento ativo
            $activeAttendance = $this->getActiveAttendance($technicianId);
            
            if ($activeAttendance) {
                $this->pauseAttendance($activeAttendance['ticket_id'], $technicianId);
            }

            // Registrar atendimento externo
            $sql = "INSERT INTO ticket_time_tracking (
                technician_id, start_time, is_active, activity_type, external_service
            ) VALUES (?, NOW(), TRUE, 'external', 0)";
            
            $this->db->query($sql, [$technicianId]);

            // Registrar atividade
            $sql = "INSERT INTO technician_activities (
                technician_id, activity_type, start_time, description
            ) VALUES (?, 'external', NOW(), ?)";
            
            $this->db->query($sql, [$technicianId, $description ?: 'Atendimento externo']);

            return [
                'success' => true,
                'message' => 'Atendimento externo registrado'
            ];

        } catch (Exception $e) {
            error_log("Erro ao registrar atendimento externo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Finaliza atendimento externo
     */
    public function finishExternalService(int $technicianId): array {
        try {
            $sql = "UPDATE ticket_time_tracking 
                    SET end_time = NOW(), 
                        is_active = FALSE,
                        external_service = TIMESTAMPDIFF(SECOND, start_time, NOW())
                    WHERE technician_id = ? AND activity_type = 'external' AND is_active = TRUE";
            
            $this->db->query($sql, [$technicianId]);

            // Atualizar atividade
            $sql = "UPDATE technician_activities 
                    SET end_time = NOW(),
                        duration = TIMESTAMPDIFF(SECOND, start_time, NOW())
                    WHERE technician_id = ? AND activity_type = 'external' AND end_time IS NULL";
            
            $this->db->query($sql, [$technicianId]);

            return [
                'success' => true,
                'message' => 'Atendimento externo finalizado'
            ];

        } catch (Exception $e) {
            error_log("Erro ao finalizar atendimento externo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém atendimento ativo do técnico
     */
    public function getActiveAttendance(int $technicianId): ?array {
        try {
            $sql = "SELECT * FROM ticket_time_tracking 
                    WHERE technician_id = ? AND is_active = TRUE AND activity_type = 'attendance'
                    ORDER BY start_time DESC LIMIT 1";
            
            $result = $this->db->query($sql, [$technicianId]);
            return $result->fetch() ?: null;

        } catch (Exception $e) {
            error_log("Erro ao buscar atendimento ativo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcula tempo total de atendimento do técnico em um período
     */
    public function calculateAttendanceTime(int $technicianId, string $startDate, string $endDate): array {
        try {
            $sql = "SELECT 
                    SUM(CASE WHEN activity_type = 'attendance' THEN total_attendance ELSE 0 END) as attendance_time,
                    SUM(CASE WHEN activity_type = 'pause' THEN pause_time ELSE 0 END) as pause_time,
                    SUM(CASE WHEN activity_type = 'lunch' THEN lunch_break ELSE 0 END) as lunch_time,
                    SUM(CASE WHEN activity_type = 'external' THEN external_service ELSE 0 END) as external_time,
                    COUNT(CASE WHEN activity_type = 'attendance' THEN 1 END) as tickets_attended
                    FROM ticket_time_tracking
                    WHERE technician_id = ? 
                    AND start_time BETWEEN ? AND ?";
            
            $result = $this->db->query($sql, [$technicianId, $startDate, $endDate]);
            $data = $result->fetch();

            return [
                'attendance_time' => (int)($data['attendance_time'] ?? 0),
                'pause_time' => (int)($data['pause_time'] ?? 0),
                'lunch_time' => (int)($data['lunch_time'] ?? 0),
                'external_time' => (int)($data['external_time'] ?? 0),
                'tickets_attended' => (int)($data['tickets_attended'] ?? 0),
                'total_tracked_time' => (int)($data['attendance_time'] ?? 0) + 
                                        (int)($data['pause_time'] ?? 0) + 
                                        (int)($data['lunch_time'] ?? 0) + 
                                        (int)($data['external_time'] ?? 0)
            ];

        } catch (Exception $e) {
            error_log("Erro ao calcular tempo de atendimento: " . $e->getMessage());
            return [
                'attendance_time' => 0,
                'pause_time' => 0,
                'lunch_time' => 0,
                'external_time' => 0,
                'tickets_attended' => 0,
                'total_tracked_time' => 0
            ];
        }
    }

    /**
     * Calcula ociosidade do técnico
     */
    public function calculateIdleTime(int $technicianId, string $date): array {
        try {
            // Obter horário de expediente (configurável)
            $workStart = '08:00:00';
            $workEnd = '17:00:00';
            $workHours = 8 * 3600; // 8 horas em segundos

            // Calcular tempo trabalhado no dia
            $timeData = $this->calculateAttendanceTime(
                $technicianId, 
                $date . ' 00:00:00', 
                $date . ' 23:59:59'
            );

            $workedTime = $timeData['attendance_time'] + $timeData['external_time'];
            $idleTime = max(0, $workHours - $workedTime - $timeData['lunch_time']);

            return [
                'work_start' => $workStart,
                'work_end' => $workEnd,
                'total_work_hours' => $workHours,
                'worked_time' => $workedTime,
                'idle_time' => $idleTime,
                'idle_percentage' => $workHours > 0 ? round(($idleTime / $workHours) * 100, 2) : 0
            ];

        } catch (Exception $e) {
            error_log("Erro ao calcular ociosidade: " . $e->getMessage());
            return [
                'idle_time' => 0,
                'idle_percentage' => 0
            ];
        }
    }

    /**
     * Obtém métricas de eficiência do técnico
     */
    public function getEfficiencyMetrics(int $technicianId, string $startDate, string $endDate): array {
        try {
            $timeData = $this->calculateAttendanceTime($technicianId, $startDate, $endDate);
            
            // Buscar atividades no período
            $sql = "SELECT 
                    COUNT(*) as total_activities,
                    AVG(efficiency_rating) as avg_efficiency,
                    SUM(CASE WHEN knowledge_base_created = 1 THEN 1 ELSE 0 END) as kb_articles
                    FROM technician_activities
                    WHERE technician_id = ? 
                    AND start_time BETWEEN ? AND ?";
            
            $result = $this->db->query($sql, [$technicianId, $startDate, $endDate]);
            $activityData = $result->fetch();

            // Calcular eficiência baseada em múltiplos fatores
            $efficiencyScore = $this->calculateEfficiencyScore($timeData, $activityData);

            return [
                'tickets_attended' => $timeData['tickets_attended'],
                'total_attendance_time' => $timeData['attendance_time'],
                'avg_attendance_time' => $timeData['tickets_attended'] > 0 
                    ? round($timeData['attendance_time'] / $timeData['tickets_attended']) 
                    : 0,
                'idle_time' => $timeData['total_tracked_time'] - $timeData['attendance_time'],
                'efficiency_score' => $efficiencyScore,
                'kb_articles_created' => (int)($activityData['kb_articles'] ?? 0),
                'avg_efficiency_rating' => (float)($activityData['avg_efficiency'] ?? 0)
            ];

        } catch (Exception $e) {
            error_log("Erro ao calcular métricas de eficiência: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula score de eficiência
     */
    private function calculateEfficiencyScore(array $timeData, array $activityData): float {
        $score = 0;
        
        // Fator 1: Utilização do tempo (40%)
        $totalTime = $timeData['attendance_time'] + $timeData['external_time'];
        $utilizationRate = $totalTime > 0 ? min(1, $timeData['attendance_time'] / $totalTime) : 0;
        $score += $utilizationRate * 40;

        // Fator 2: Volume de atendimentos (30%)
        $ticketsAttended = $timeData['tickets_attended'];
        $ticketScore = min(1, $ticketsAttended / 10); // 10 tickets = score máximo
        $score += $ticketScore * 30;

        // Fator 3: Eficiência média das atividades (20%)
        $avgEfficiency = $activityData['avg_efficiency'] ?? 0;
        $score += $avgEfficiency * 20;

        // Fator 4: Contribuição para base de conhecimento (10%)
        $kbArticles = $activityData['kb_articles'] ?? 0;
        $kbScore = min(1, $kbArticles / 5); // 5 artigos = score máximo
        $score += $kbScore * 10;

        return round($score / 100, 2);
    }

    /**
     * Obtém tempo decorrido de atendimento atual
     */
    public function getCurrentAttendanceTime(int $ticketId, int $technicianId): array {
        try {
            $sql = "SELECT start_time, pause_time, is_paused 
                    FROM ticket_time_tracking 
                    WHERE ticket_id = ? AND technician_id = ? AND is_active = TRUE";
            
            $result = $this->db->query($sql, [$ticketId, $technicianId]);
            $tracking = $result->fetch();

            if (!$tracking) {
                return ['elapsed' => 0, 'is_active' => false];
            }

            $startTime = strtotime($tracking['start_time']);
            $now = time();
            
            if ($tracking['is_paused']) {
                $elapsed = $tracking['pause_time'];
            } else {
                $elapsed = $now - $startTime;
            }

            return [
                'elapsed' => $elapsed,
                'is_active' => true,
                'is_paused' => (bool)$tracking['is_paused'],
                'start_time' => $tracking['start_time']
            ];

        } catch (Exception $e) {
            error_log("Erro ao obter tempo atual: " . $e->getMessage());
            return ['elapsed' => 0, 'is_active' => false];
        }
    }

    /**
     * Atualiza rating de eficiência de uma atividade
     */
    public function updateEfficiencyRating(int $activityId, float $rating): bool {
        try {
            $rating = max(0, min(1, $rating)); // Garantir entre 0 e 1
            
            $sql = "UPDATE technician_activities 
                    SET efficiency_rating = ? 
                    WHERE id = ?";
            
            $this->db->query($sql, [$rating, $activityId]);
            return true;

        } catch (Exception $e) {
            error_log("Erro ao atualizar rating de eficiência: " . $e->getMessage());
            return false;
        }
    }
}
