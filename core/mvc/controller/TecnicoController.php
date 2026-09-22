<?php
namespace Core\MVC\Controller;

use Core\Agent\Chamados\ChamadosAgent;
use Core\Agent\Tempo\TempoAgent;
use Core\Agent\Planejamento\PlanejamentoAgent;

/**
 * TecnicoController - Aplicativo do Técnico
 * Responsabilidade única: Interface do técnico para atendimento de chamados
 */
class TecnicoController extends Controller
{
    private ChamadosAgent $chamadosAgent;
    private TempoAgent $tempoAgent;
    private PlanejamentoAgent $planejamentoAgent;
    
    public function __construct()
    {
        $this->chamadosAgent = new ChamadosAgent();
        $this->tempoAgent = new TempoAgent();
        $this->planejamentoAgent = new PlanejamentoAgent();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->requireAuth();
    }
    
    /**
     * Exibe dashboard do técnico
     */
    public function index(): void
    {
        try {
            $userId = $_SESSION['user_id'];
            
            // Meus chamados atribuídos
            $myTickets = $this->chamadosAgent->getTicketsByTechnician($userId);
            $myOpenTickets = array_filter($myTickets, fn($t) => $t['status'] !== 'closed');
            
            // Minhas tarefas periódicas
            $myTasks = $this->planejamentoAgent->getTasksByTechnician($userId);
            
            // Meus indicadores
            $myMetrics = $this->tempoAgent->getTechnicianMetrics($userId);
            
            $this->set('title', 'Área do Técnico');
            $this->set('route', 'tecnico');
            $this->set('myTickets', $myTickets);
            $this->set('myOpenTickets', $myOpenTickets);
            $this->set('myTasks', $myTasks);
            $this->set('myMetrics', $myMetrics);
            
            echo $this->render('layouts.default', [
                'content' => $this->render('tecnico/dashboard')
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro no dashboard técnico: " . $e->getMessage());
            echo $this->render('layouts.default', [
                'content' => '<div class="alert alert-error">Erro: ' . \Core\MVC\View\ViewHelper::escape($e->getMessage()) . '</div>'
            ]);
        }
    }
    
    /**
     * Inicia atendimento de um chamado
     */
    public function startAttendance(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        
        if (!$ticketId) {
            $this->json(['error' => 'ID do chamado inválido'], 400);
        }
        
        try {
            $result = $this->tempoAgent->startAttendance($ticketId, $userId);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Pausa atendimento
     */
    public function pauseAttendance(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        $trackingId = (int) ($_POST['tracking_id'] ?? 0);
        $reason = $_POST['reason'] ?? 'Pausa';
        
        if (!$trackingId) {
            $this->json(['error' => 'Tracking ID inválido'], 400);
        }
        
        try {
            $result = $this->tempoAgent->pauseAttendance($trackingId, $reason);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Retoma atendimento
     */
    public function resumeAttendance(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        $trackingId = (int) ($_POST['tracking_id'] ?? 0);
        
        if (!$trackingId) {
            $this->json(['error' => 'Tracking ID inválido'], 400);
        }
        
        try {
            $result = $this->tempoAgent->resumeAttendance($trackingId);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Registra almoço
     */
    public function registerLunch(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        $userId = $_SESSION['user_id'];
        $type = $_POST['type'] ?? 'start'; // start ou end
        
        try {
            $result = $this->tempoAgent->registerLunch($userId, $type);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Registra atendimento externo
     */
    public function registerExternal(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        $userId = $_SESSION['user_id'];
        $description = $_POST['description'] ?? '';
        
        try {
            $result = $this->tempoAgent->registerExternalActivity($userId, $description);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Finaliza atendimento e fecha chamado
     */
    public function closeTicket(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $solution = $_POST['solution'] ?? '';
        $rating = (int) ($_POST['rating'] ?? 3);
        
        if (!$ticketId || empty($solution)) {
            $this->json(['error' => 'Dados inválidos'], 400);
        }
        
        try {
            $result = $this->chamadosAgent->closeTicket($ticketId, $solution, $_SESSION['user_id'], $rating);
            $this->tempoAgent->endAttendance($ticketId, $_SESSION['user_id']);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Abertura rápida de chamado
     */
    public function quickOpen(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        $requesterId = (int) ($_POST['requester_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (!$requesterId || empty($title)) {
            $this->json(['error' => 'Solicitante e título são obrigatórios'], 400);
        }
        
        try {
            $result = $this->chamadosAgent->quickOpenTicket($requesterId, $title, $description, $_SESSION['user_id']);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * API para polling de novos chamados
     */
    public function apiNewTickets(): void
    {
        try {
            $lastCheck = isset($_SESSION['last_ticket_check']) ? $_SESSION['last_ticket_check'] : null;
            $newTickets = $this->chamadosAgent->getNewTicketsForUser($_SESSION['user_id'], $lastCheck);
            
            $_SESSION['last_ticket_check'] = date('Y-m-d H:i:s');
            
            $this->json([
                'success' => true,
                'new_tickets' => $newTickets,
                'count' => count($newTickets)
            ]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * API para status atual do atendimento
     */
    public function apiCurrentAttendance(): void
    {
        try {
            $current = $this->tempoAgent->getCurrentAttendance($_SESSION['user_id']);
            $this->json(['success' => true, 'data' => $current]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
