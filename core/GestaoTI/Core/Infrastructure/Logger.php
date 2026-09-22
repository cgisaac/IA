<?php
namespace GestaoTI\Core\Infrastructure;

class Logger
{
    private static $instance = null;
    private $logFile;
    private $logDir;

    private function __construct()
    {
        $this->logDir = dirname(dirname(dirname(__DIR__))) . '/storage/logs';
        $this->logFile = $this->logDir . '/app.log';
        
        // Cria pasta se não existir
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $formattedMessage .= ' | Context: ' . json_encode($context);
        }
        
        $formattedMessage .= PHP_EOL;
        
        file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function getLogFile(): string
    {
        return $this->logFile;
    }

    public function getLastLines(int $lines = 20): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $file = file($this->logFile);
        return array_slice(array_reverse($file), 0, $lines);
    }
}
