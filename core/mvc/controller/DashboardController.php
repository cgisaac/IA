<?php
namespace Core\MVC\Controller;

use Core\Agent\Analise\AnaliseAgent;
use Core\Agent\Chamados\ChamadosAgent;
use Core\Agent\Tempo\TempoAgent;

/**
 * DashboardController - Controlador do Dashboard Gerencial
 * Responsabilidade única: Exibir estatísticas e indicadores gerenciais
 */
class DashboardController extends Controller
{
    private AnaliseAgent $analiseAgent;
    private ChamadosAgent $chamadosAgent;
    private TempoAgent $tempoAgent;
    
    public function __construct()
    {
        $this->analiseAgent = new AnaliseAgent();
        $this->chamadosAgent = new ChamadosAgent();
        $this->tempoAgent = new TempoAgent();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
    }
    
    /**
     * Exibe dashboard gerencial completo
     */
    public function index(): void
    {
        try {
            // Obtém dados do agente de análise
            $stats = $this->analiseAgent->getDashboardStats();
            $insights = $this->analiseAgent->generateInsights();
            $performance = $this->analiseAgent->getTechnicianPerformance();
            
            // Dados de chamados
            $ticketsByStatus = $this->chamadosAgent->getTicketsByStatus();
            $recentTickets = $this->chamadosAgent->getRecentTickets(10);
            
            // Dados de tempo
            $timeMetrics = $this->tempoAgent->getTimeMetrics();
            
            $this->set('title', 'Dashboard Gerencial');
            $this->set('route', 'dashboard');
            $this->set('stats', $stats);
            $this->set('insights', $insights);
            $this->set('performance', $performance);
            $this->set('ticketsByStatus', $ticketsByStatus);
            $this->set('recentTickets', $recentTickets);
            $this->set('timeMetrics', $timeMetrics);
            
            echo $this->render('layouts.default', [
                'content' => $this->render('gerencial/dashboard')
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro no dashboard: " . $e->getMessage());
            echo $this->render('layouts.default', [
                'content' => '<div class="alert alert-error">Erro ao carregar dashboard: ' . \Core\MVC\View\ViewHelper::escape($e->getMessage()) . '</div>'
            ]);
        }
    }
    
    /**
     * API para dados do dashboard (AJAX)
     */
    public function apiData(): void
    {
        try {
            $this->json([
                'success' => true,
                'data' => [
                    'stats' => $this->analiseAgent->getDashboardStats(),
                    'insights' => $this->analiseAgent->generateInsights(),
                    'performance' => $this->analiseAgent->getTechnicianPerformance()
                ]
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
