<?php
/**
 * Funções auxiliares globais do sistema
 */

if (!function_exists('getConfig')) {
    function getConfig() {
        static $config = null;
        if ($config === null) {
            $config = require __DIR__ . '/config.php';
        }
        return $config;
    }
}

if (!function_exists('getSetting')) {
    function getSetting(string $key, $default = null) {
        $config = getConfig();
        return $config['settings'][$key] ?? $default;
    }
}

if (!function_exists('logMessage')) {
    function logMessage(string $message, string $level = 'INFO') {
        try {
            $logger = \GestaoTI\Core\Infrastructure\Logger::getInstance();
            $logger->log($message, $level);
        } catch (\Throwable $e) {
            error_log("[$level] $message");
        }
    }
}
