<?php

namespace GestaoTI\Agent\Analise;

use GestaoTI\Core\Database\Database;
use GestaoTI\Agent\Chamados\ChamadosAgent;
use GestaoTI\Agent\Tempo\TempoAgent;
use Exception;

/**
 * Agente de Análise - Produz dashboards e métricas gerenciais
 * Responsável por:
 * - Estatísticas gerais do setor
 * - Indicadores de performance (KPIs)
 * - Insights gerenciais
 * - Relatórios de produtividade
 */
class AnaliseAgent {
    private Database $db;
    private ChamadosAgent $chamadosAgent;
    private TempoAgent $tempoAgent;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->chamadosAgent = new ChamadosAgent();
        $this->tempoAgent = new TempoAgent();
    }

    /**
     * Obtém dashboard gerencial completo
     */
    public function getManagerialDashboard(string $period = 'month'): array {
        try {
            $dateRange = $this->getDateRange($period);
            
            return [
                'period' => $period,
                'date_range' => $dateRange,
                'summary' => $this->getSummaryMetrics($dateRange),
                'tickets_stats' => $this->getTicketsStatistics($dateRange),
                'technicians_performance' => $this->getTechniciansPerformance($dateRange),
                'insights' => $this->generateInsights($dateRange),
                'charts_data' => $this->getChartsData($dateRange)
            ];

        } catch (Exception $e) {
            error_log("Erro ao gerar dashboard gerencial: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém intervalo de datas baseado no período
     */
    private function getDateRange(string $period): array {
        $now = new DateTime();
        
        switch ($period) {
            case 'day':
                $start = clone $now;
                $start->setTime(0, 0, 0);
                $end = clone $now;
                $end->setTime(23, 59, 59);
                break;
                
            case 'week':
                $start = clone $now;
                $start->modify('monday this week')->setTime(0, 0, 0);
                $end = clone $now;
                $end->modify('sunday this week')->setTime(23, 59, 59);
                break;
                
            case 'month':
                $start = clone $now;
                $start->modify('first day of this month')->setTime(0, 0, 0);
                $end = clone $now;
                $end->modify('last day of this month')->setTime(23, 59, 59);
                break;
                
            case 'year':
                $start = clone $now;
                $start->modify('first day of january')->setTime(0, 0, 0);
                $end = clone $now;
                $end->modify('last day of december')->setTime(23, 59, 59);
                break;
                
            default:
                $start = clone $now;
                $start->modify('first day of this month')->setTime(0, 0, 0);
                $end = clone $now;
                $end->setTime(23, 59, 59);
        }
        
        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Obtém métricas resumidas
     */
    private function getSummaryMetrics(array $dateRange): array {
        try {
            $sql = "SELECT 
                    COUNT(DISTINCT t.id) as total_tickets,
                    COUNT(DISTINCT CASE WHEN t.status = 6 THEN t.id END) as closed_tickets,
                    COUNT(DISTINCT CASE WHEN t.is_overdue = 1 THEN t.id END) as overdue_tickets,
                    COUNT(DISTINCT t.technician_id) as active_technicians,
                    AVG(CASE WHEN t.date_closed IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, t.date_opened, t.date_closed) 
                        END) as avg_resolution_time
                    FROM tickets t
                    WHERE t.date_opened BETWEEN ? AND ?";

            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $data = $result->fetch();

            // Calcular taxa de fechamento
            $totalTickets = (int)($data['total_tickets'] ?? 0);
            $closedTickets = (int)($data['closed_tickets'] ?? 0);
            $closureRate = $totalTickets > 0 ? round(($closedTickets / $totalTickets) * 100, 2) : 0;

            // Calcular taxa de atraso
            $overdueTickets = (int)($data['overdue_tickets'] ?? 0);
            $overdueRate = $totalTickets > 0 ? round(($overdueTickets / $totalTickets) * 100, 2) : 0;

            return [
                'total_tickets' => $totalTickets,
                'closed_tickets' => $closedTickets,
                'overdue_tickets' => $overdueTickets,
                'closure_rate' => $closureRate,
                'overdue_rate' => $overdueRate,
                'active_technicians' => (int)($data['active_technicians'] ?? 0),
                'avg_resolution_time_seconds' => (float)($data['avg_resolution_time'] ?? 0),
                'avg_resolution_time_formatted' => $this->formatSeconds((int)($data['avg_resolution_time'] ?? 0))
            ];

        } catch (Exception $e) {
            error_log("Erro ao obter métricas resumidas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém estatísticas detalhadas de chamados
     */
    private function getTicketsStatistics(array $dateRange): array {
        try {
            // Por status
            $sql = "SELECT status, COUNT(*) as count 
                    FROM tickets 
                    WHERE date_opened BETWEEN ? AND ?
                    GROUP BY status";
            
            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $byStatus = $result->fetchAll();

            // Por prioridade
            $sql = "SELECT priority, COUNT(*) as count 
                    FROM tickets 
                    WHERE date_opened BETWEEN ? AND ?
                    GROUP BY priority";
            
            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $byPriority = $result->fetchAll();

            // Por categoria
            $sql = "SELECT category_id, COUNT(*) as count 
                    FROM tickets 
                    WHERE date_opened BETWEEN ? AND ? AND category_id IS NOT NULL
                    GROUP BY category_id";
            
            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $byCategory = $result->fetchAll();

            // Evolução diária
            $sql = "SELECT DATE(date_opened) as date, COUNT(*) as count 
                    FROM tickets 
                    WHERE date_opened BETWEEN ? AND ?
                    GROUP BY DATE(date_opened)
                    ORDER BY date";
            
            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $dailyEvolution = $result->fetchAll();

            return [
                'by_status' => $byStatus,
                'by_priority' => $byPriority,
                'by_category' => $byCategory,
                'daily_evolution' => $dailyEvolution
            ];

        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas de chamados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém performance dos técnicos
     */
    private function getTechniciansPerformance(array $dateRange): array {
        try {
            $sql = "SELECT 
                    u.id as technician_id,
                    u.username,
                    COUNT(DISTINCT t.id) as tickets_attended,
                    COUNT(DISTINCT CASE WHEN t.status = 6 THEN t.id END) as tickets_closed,
                    AVG(CASE WHEN t.date_closed IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, t.date_opened, t.date_closed) 
                        END) as avg_resolution_time,
                    COUNT(DISTINCT CASE WHEN t.is_overdue = 1 THEN t.id END) as overdue_tickets
                    FROM users u
                    LEFT JOIN tickets t ON u.id = t.technician_id 
                        AND t.date_opened BETWEEN ? AND ?
                    WHERE u.role IN ('technician', 'administrator')
                    GROUP BY u.id, u.username
                    ORDER BY tickets_attended DESC";

            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $technicians = $result->fetchAll();

            // Adicionar métricas de eficiência do TempoAgent
            foreach ($technicians as &$tech) {
                $efficiencyMetrics = $this->tempoAgent->getEfficiencyMetrics(
                    $tech['technician_id'],
                    $dateRange['start'],
                    $dateRange['end']
                );
                
                $tech['efficiency_score'] = $efficiencyMetrics['efficiency_score'] ?? 0;
                $tech['idle_time'] = $efficiencyMetrics['idle_time'] ?? 0;
                $tech['kb_articles'] = $efficiencyMetrics['kb_articles_created'] ?? 0;
            }

            return $technicians;

        } catch (Exception $e) {
            error_log("Erro ao obter performance dos técnicos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gera insights automáticos baseados nos dados
     */
    private function generateInsights(array $dateRange): array {
        $insights = [];
        $summary = $this->getSummaryMetrics($dateRange);
        
        // Insight sobre taxa de fechamento
        if ($summary['closure_rate'] < 50) {
            $insights[] = [
                'type' => 'warning',
                'category' => 'productivity',
                'message' => 'Taxa de fechamento abaixo de 50%. Considere revisar a distribuição de chamados.',
                'priority' => 'high'
            ];
        } elseif ($summary['closure_rate'] > 80) {
            $insights[] = [
                'type' => 'success',
                'category' => 'productivity',
                'message' => 'Excelente taxa de fechamento (' . $summary['closure_rate'] . '%). Equipe está produtiva.',
                'priority' => 'low'
            ];
        }

        // Insight sobre atrasos
        if ($summary['overdue_rate'] > 20) {
            $insights[] = [
                'type' => 'danger',
                'category' => 'sla',
                'message' => 'Mais de 20% dos chamados estão atrasados. Revise SLAs e capacidade da equipe.',
                'priority' => 'critical'
            ];
        }

        // Insight sobre tempo médio de resolução
        $avgResolutionHours = ($summary['avg_resolution_time_seconds'] ?? 0) / 3600;
        if ($avgResolutionHours > 24) {
            $insights[] = [
                'type' => 'warning',
                'category' => 'efficiency',
                'message' => 'Tempo médio de resolução superior a 24 horas. Identifique gargalos no processo.',
                'priority' => 'medium'
            ];
        }

        // Insight sobre ociosidade
        $technicians = $this->getTechniciansPerformance($dateRange);
        $avgIdleTime = 0;
        $techniciansCount = count($technicians);
        
        if ($techniciansCount > 0) {
            $totalIdleTime = array_sum(array_column($technicians, 'idle_time'));
            $avgIdleTime = $totalIdleTime / $techniciansCount;
            
            if ($avgIdleTime > 4 * 3600) { // Mais de 4 horas de ociosidade média
                $insights[] = [
                    'type' => 'info',
                    'category' => 'capacity',
                    'message' => 'Ociosidade média alta (' . round($avgIdleTime / 3600, 1) . 'h/técnico). Considere redistribuir tarefas.',
                    'priority' => 'medium'
                ];
            }
        }

        return $insights;
    }

    /**
     * Obtém dados para gráficos
     */
    private function getChartsData(array $dateRange): array {
        try {
            // Dados para gráfico de evolução temporal
            $sql = "SELECT 
                    DATE_FORMAT(date_opened, '%Y-%m-%d') as date,
                    COUNT(*) as opened,
                    COUNT(CASE WHEN status = 6 THEN 1 END) as closed
                    FROM tickets
                    WHERE date_opened BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(date_opened, '%Y-%m-%d')
                    ORDER BY date";
            
            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $timeline = $result->fetchAll();

            // Dados para gráfico de pizza (status)
            $sql = "SELECT 
                    CASE status
                        WHEN 1 THEN 'Novo'
                        WHEN 2 THEN 'Em andamento'
                        WHEN 3 THEN 'Pendente'
                        WHEN 4 THEN 'Resolvido'
                        WHEN 5 THEN 'Fechado'
                        WHEN 6 THEN 'Cancelado'
                        ELSE 'Outro'
                    END as status_name,
                    COUNT(*) as count
                    FROM tickets
                    WHERE date_opened BETWEEN ? AND ?
                    GROUP BY status";
            
            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $statusDistribution = $result->fetchAll();

            // Dados para gráfico de barras (técnicos)
            $sql = "SELECT 
                    u.username,
                    COUNT(t.id) as tickets_count
                    FROM users u
                    LEFT JOIN tickets t ON u.id = t.technician_id 
                        AND t.date_opened BETWEEN ? AND ?
                    WHERE u.role = 'technician'
                    GROUP BY u.id, u.username
                    ORDER BY tickets_count DESC
                    LIMIT 10";
            
            $result = $this->db->query($sql, [$dateRange['start'], $dateRange['end']]);
            $topTechnicians = $result->fetchAll();

            return [
                'timeline' => $timeline,
                'status_distribution' => $statusDistribution,
                'top_technicians' => $topTechnicians
            ];

        } catch (Exception $e) {
            error_log("Erro ao obter dados para gráficos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Formata segundos em string legível
     */
    private function formatSeconds(int $seconds): string {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($minutes > 0) {
            return "{$minutes}m";
        } else {
            return "{$seconds}s";
        }
    }

    /**
     * Obtém dashboard de acompanhamento em tempo real
     */
    public function getRealtimeDashboard(): array {
        try {
            // Chamados abertos agora
            $sql = "SELECT 
                    COUNT(*) as open_tickets,
                    COUNT(CASE WHEN is_overdue = 1 THEN 1 END) as overdue,
                    COUNT(CASE WHEN technician_id IS NULL THEN 1 END) as unassigned
                    FROM tickets
                    WHERE status NOT IN (5, 6)";
            
            $result = $this->db->query($sql);
            $openTickets = $result->fetch();

            // Técnicos ativos agora
            $sql = "SELECT 
                    COUNT(DISTINCT technician_id) as active_technicians,
                    COUNT(DISTINCT CASE WHEN is_active = 1 THEN technician_id END) as attending_now
                    FROM ticket_time_tracking
                    WHERE DATE(start_time) = CURDATE()";
            
            $result = $this->db->query($sql);
            $technicians = $result->fetch();

            // Últimas atividades
            $sql = "SELECT 
                    ta.*,
                    t.title as ticket_title,
                    u.username as technician_name
                    FROM technician_activities ta
                    LEFT JOIN tickets t ON ta.ticket_id = t.glpi_ticket_id
                    LEFT JOIN users u ON ta.technician_id = u.id
                    ORDER BY ta.start_time DESC
                    LIMIT 10";
            
            $result = $this->db->query($sql);
            $recentActivities = $result->fetchAll();

            // Tarefas do dia
            $planejamentoAgent = new \GestaoTI\Agent\Planejamento\PlanejamentoAgent();
            $todaysTasks = $planejamentoAgent->getTodaysTasks();

            return [
                'open_tickets' => (int)($openTickets['open_tickets'] ?? 0),
                'overdue_tickets' => (int)($openTickets['overdue'] ?? 0),
                'unassigned_tickets' => (int)($openTickets['unassigned'] ?? 0),
                'active_technicians' => (int)($technicians['active_technicians'] ?? 0),
                'attending_now' => (int)($technicians['attending_now'] ?? 0),
                'recent_activities' => $recentActivities,
                'todays_tasks' => $todaysTasks,
                'last_update' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("Erro ao gerar dashboard em tempo real: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém indicadores individuais do técnico
     */
    public function getTechnicianIndicators(int $technicianId, string $period = 'month'): array {
        try {
            $dateRange = $this->getDateRange($period);
            
            // Métricas de chamados
            $sql = "SELECT 
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN status = 6 THEN 1 END) as closed_tickets,
                    COUNT(CASE WHEN is_overdue = 1 THEN 1 END) as overdue_tickets,
                    AVG(CASE WHEN date_closed IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, date_opened, date_closed) 
                        END) as avg_resolution_time
                    FROM tickets
                    WHERE technician_id = ? AND date_opened BETWEEN ? AND ?";
            
            $result = $this->db->query($sql, [$technicianId, $dateRange['start'], $dateRange['end']]);
            $ticketsMetrics = $result->fetch();

            // Métricas de tempo
            $timeMetrics = $this->tempoAgent->getEfficiencyMetrics(
                $technicianId,
                $dateRange['start'],
                $dateRange['end']
            );

            // Calcular metas (pode ser parametrizado)
            $goals = [
                'monthly_tickets' => 50,
                'closure_rate' => 80,
                'max_avg_resolution_hours' => 8,
                'min_efficiency_score' => 0.7
            ];

            // Verificar cumprimento de metas
            $closureRate = $ticketsMetrics['total_tickets'] > 0 
                ? ($ticketsMetrics['closed_tickets'] / $ticketsMetrics['total_tickets']) * 100 
                : 0;

            $avgResolutionHours = ($ticketsMetrics['avg_resolution_time'] ?? 0) / 3600;

            return [
                'period' => $period,
                'tickets_metrics' => [
                    'total' => (int)($ticketsMetrics['total_tickets'] ?? 0),
                    'closed' => (int)($ticketsMetrics['closed_tickets'] ?? 0),
                    'overdue' => (int)($ticketsMetrics['overdue_tickets'] ?? 0),
                    'closure_rate' => round($closureRate, 2),
                    'avg_resolution_time' => $avgResolutionHours
                ],
                'time_metrics' => $timeMetrics,
                'goals' => $goals,
                'goals_achievement' => [
                    'tickets_goal' => ($ticketsMetrics['total_tickets'] ?? 0) >= $goals['monthly_tickets'],
                    'closure_rate_goal' => $closureRate >= $goals['closure_rate'],
                    'resolution_time_goal' => $avgResolutionHours <= $goals['max_avg_resolution_hours'],
                    'efficiency_goal' => ($timeMetrics['efficiency_score'] ?? 0) >= $goals['min_efficiency_score']
                ]
            ];

        } catch (Exception $e) {
            error_log("Erro ao obter indicadores do técnico: " . $e->getMessage());
            return [];
        }
    }
}
