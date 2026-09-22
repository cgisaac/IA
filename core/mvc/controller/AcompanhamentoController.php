<?php
namespace Core\MVC\Controller;

use Core\Agent\Chamados\ChamadosAgent;
use Core\Agent\Planejamento\PlanejamentoAgent;

/**
 * AcompanhamentoController - Dashboard de Acompanhamento em Tempo Real
 * Responsabilidade única: Exibir status de chamados em tempo real para toda equipe
 */
class AcompanhamentoController extends Controller
{
    private ChamadosAgent $chamadosAgent;
    private PlanejamentoAgent $planejamentoAgent;
    
    public function __construct()
    {
        $this->chamadosAgent = new ChamadosAgent();
        $this->planejamentoAgent = new PlanejamentoAgent();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
    }
    
    /**
     * Exibe dashboard de acompanhamento em tempo real
     */
    public function index(): void
    {
        try {
            // Obtém todos os chamados abertos e em andamento
            $openTickets = $this->chamadosAgent->getOpenTickets();
            $inProgressTickets = $this->chamadosAgent->getInProgressTickets();
            $overdueTickets = $this->chamadosAgent->getOverdueTickets();
            
            // Obtém tarefas periódicas ativas
            $periodicTasks = $this->planejamentoAgent->getActiveTasks();
            
            $this->set('title', 'Acompanhamento em Tempo Real');
            $this->set('route', 'acompanhamento');
            $this->set('openTickets', $openTickets);
            $this->set('inProgressTickets', $inProgressTickets);
            $this->set('overdueTickets', $overdueTickets);
            $this->set('periodicTasks', $periodicTasks);
            
            echo $this->render('layouts.default', [
                'content' => $this->render('acompanhamento/dashboard')
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro no acompanhamento: " . $e->getMessage());
            echo $this->render('layouts.default', [
                'content' => '<div class="alert alert-error">Erro ao carregar acompanhamento: ' . \Core\MVC\View\ViewHelper::escape($e->getMessage()) . '</div>'
            ]);
        }
    }
    
    /**
     * API para dados em tempo real (AJAX polling)
     */
    public function apiData(): void
    {
        try {
            $this->json([
                'success' => true,
                'data' => [
                    'open_tickets' => $this->chamadosAgent->getOpenTickets(),
                    'in_progress_tickets' => $this->chamadosAgent->getInProgressTickets(),
                    'overdue_tickets' => $this->chamadosAgent->getOverdueTickets(),
                    'stats' => [
                        'total_open' => count($this->chamadosAgent->getOpenTickets()),
                        'total_in_progress' => count($this->chamadosAgent->getInProgressTickets()),
                        'total_overdue' => count($this->chamadosAgent->getOverdueTickets())
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * API para tarefas periódicas
     */
    public function apiTasks(): void
    {
        try {
            $this->json([
                'success' => true,
                'data' => [
                    'tasks' => $this->planejamentoAgent->getActiveTasks()
                ]
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
