<?php
namespace GestaoTI\Core\Infrastructure;

/**
 * Logger da aplicação
 * Grava logs em arquivo texto - Padrão Singleton
 */
class Logger {
    private static $instance = null;
    private $logFile;
    private $initialized = false;
    
    /**
     * Construtor privado para Singleton
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Previne clonagem
     */
    private function __clone() {}
    
    /**
     * Previne desserialização
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * Obtém instância Singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializa o logger
     */
    private function init() {
        if (!$this->initialized) {
            $logDir = dirname(__DIR__, 3) . '/storage/logs';
            
            // Cria diretório se não existir
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            
            $this->logFile = $logDir . '/app.log';
            $this->initialized = true;
        }
    }
    
    /**
     * Grava log de erro
     */
    public function error($message, $context = []) {
        $this->write('ERROR', $message, $context);
    }
    
    /**
     * Grava log de informação
     */
    public function info($message, $context = []) {
        $this->write('INFO', $message, $context);
    }
    
    /**
     * Grava log de debug
     */
    public function debug($message, $context = []) {
        $this->write('DEBUG', $message, $context);
    }
    
    /**
     * Grava log genérico
     */
    private function write($level, $message, $context = []) {
        $this->init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_PRETTY_PRINT) : '';
        $logLine = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Limpa logs antigos (opcional)
     */
    public function clearOldLogs($days = 7) {
        $this->init();
        $cutoff = time() - ($days * 86400);
        
        if (file_exists($this->logFile)) {
            $lines = file($this->logFile);
            $newLines = [];
            
            foreach ($lines as $line) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                    $lineTime = strtotime($matches[1]);
                    if ($lineTime >= $cutoff) {
                        $newLines[] = $line;
                    }
                } else {
                    $newLines[] = $line;
                }
            }
            
            file_put_contents($this->logFile, implode('', $newLines));
        }
    }
}
