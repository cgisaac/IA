<?php
namespace GestaoTI\MVC\Controllers;

use GestaoTI\MVC\Controller;

class LogsController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->view->redirect('login');
            return;
        }
        
        try {
            $logs = $this->logger->getLastLines(50);
            $logFile = $this->logger->getLogFile();
            
            $data = [
                'logs' => $logs,
                'logFile' => $logFile
            ];
            
            $this->view->render('logs/index', $data, 'default');
        } catch (\Exception $e) {
            $data = ['error' => 'Não foi possível carregar os logs: ' . $e->getMessage()];
            $this->view->render('logs/index', $data, 'default');
        }
    }

    public function full(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->view->redirect('login');
            return;
        }
        
        $logFile = $this->logger->getLogFile();
        
        if (file_exists($logFile)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="app.log"');
            readfile($logFile);
            exit;
        }
        
        $this->view->redirect('logs');
    }
}
