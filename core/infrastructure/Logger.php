<?php

namespace GestaoTI\Core\Infrastructure;

/**
 * Classe Logger - Gerencia logs da aplicação
 */
class Logger {
    private static ?Logger $instance = null;
    private string $logFile;
    private string $logDir;
    
    private function __construct() {
        $this->logDir = dirname(__DIR__, 2) . '/storage/logs';
        $this->logFile = $this->logDir . '/app.log';
        
        // Garante que o diretório existe
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
        
        // Garante que o arquivo existe
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0666);
        }
    }
    
    public static function getInstance(): Logger {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log(string $message, string $level = 'INFO'): void {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        error_log($formattedMessage, 3, $this->logFile);
        
        // Se estiver em modo debug, também exibe no erro padrão do PHP
        if ($this->isDebugMode()) {
            error_log($formattedMessage);
        }
    }
    
    public function info(string $message): void {
        $this->log($message, 'INFO');
    }
    
    public function warning(string $message): void {
        $this->log($message, 'WARNING');
    }
    
    public function error(string $message, \Throwable $exception = null): void {
        $fullMessage = $message;
        if ($exception) {
            $fullMessage .= " | Exception: " . $exception->getMessage();
            $fullMessage .= " | File: " . $exception->getFile() . ":" . $exception->getLine();
            $fullMessage .= " | Stack: " . $exception->getTraceAsString();
        }
        $this->log($fullMessage, 'ERROR');
    }
    
    public function critical(string $message): void {
        $this->log($message, 'CRITICAL');
    }
    
    public function getLogFile(): string {
        return $this->logFile;
    }
    
    public function getRecentLogs(int $lines = 50): string {
        if (!file_exists($this->logFile)) {
            return "Nenhum log encontrado.";
        }
        
        $content = file_get_contents($this->logFile);
        if (!$content) {
            return "Nenhum log encontrado.";
        }
        
        $linesArray = explode("\n", $content);
        $recentLines = array_slice($linesArray, -$lines);
        return implode("\n", $recentLines);
    }
    
    private function isDebugMode(): bool {
        return defined('DEBUG_MODE') && DEBUG_MODE === true;
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new \Exception("Não é possível desserializar esta classe");
    }
}
