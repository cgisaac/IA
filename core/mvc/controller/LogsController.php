<?php

namespace Core\MVC\Controller;

use Core\Infrastructure\Logger;

/**
 * Controller de Logs - Visualização de logs do sistema
 */
class LogsController extends Controller {
    
    public function index() {
        $this->checkAuth();
        
        $logger = Logger::getInstance();
        $logs = $logger->getRecentLogs(100);
        $logFile = $logger->getLogFile();
        
        include __DIR__ . '/../view/logs/index.php';
    }
    
    public function view() {
        $this->checkAuth();
        
        $logger = Logger::getInstance();
        $logs = $logger->getRecentLogs(500);
        $logFile = $logger->getLogFile();
        
        include __DIR__ . '/../view/logs/full.php';
    }
    
    public function clear() {
        $this->checkAuth();
        
        $logger = Logger::getInstance();
        $logFile = $logger->getLogFile();
        
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        
        header('Location: ?route=logs');
        exit;
    }
    
    public function download() {
        $this->checkAuth();
        
        $logger = Logger::getInstance();
        $logFile = $logger->getLogFile();
        
        if (file_exists($logFile)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="app_' . date('Y-m-d_H-i-s') . '.log"');
            readfile($logFile);
            exit;
        }
        
        echo "Arquivo de log não encontrado.";
    }
}
