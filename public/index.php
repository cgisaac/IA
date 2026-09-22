<?php
/**
 * Front Controller - Ponto único de entrada da aplicação
 * Gestão TI - Sistema de Controle de Atendimentos de TI
 */

// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// Define modo debug (altere para false em produção)
define('DEBUG_MODE', true);

// Carrega autoload
require_once __DIR__ . '/../core/Autoloader.php';

// Carrega funções auxiliares
require_once __DIR__ . '/../config/functions.php';

// Imports de classes
use GestaoTI\Core\Infrastructure\Logger;

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Carrega configurações
    $config = getConfig();
    
    // Inicializa logger
    $logger = Logger::getInstance();
    $logger->info('Aplicação iniciada - Route: ' . ($_GET['route'] ?? 'login'));
    
    // Carrega router
    $router = require __DIR__ . '/../core/mvc/Router.php';
    
    // Obtém route da URL
    $route = $_GET['route'] ?? 'login';
    
    // Dispatch da rota
    $router->dispatch($route);
    
} catch (\Exception $e) {
    // Tenta obter logger
    try {
        $logger = Logger::getInstance();
        $logger->error('Erro na aplicação', $e);
    } catch (\Throwable $t) {
        // Se não conseguir usar logger, usa error_log padrão
        error_log("Erro crítico sem logger: " . $t->getMessage());
    }
    
    // Log do erro (fallback)
    error_log("Erro na aplicação: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    
    // Exibe mensagem amigável
    http_response_code(500);
    
    // Caminho do log para exibição
    $logFile = dirname(__DIR__) . '/storage/logs/app.log';
    $recentLogs = '';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLogs = implode('<br>', array_slice(array_reverse($lines), 0, 20));
    }
    
    if (DEBUG_MODE) {
        echo "<h1>Erro na Aplicação</h1>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
        echo "<p><strong>URL:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
        echo "<h2>Logs Recentes (últimas 20 linhas):</h2>";
        echo "<div style='background:#f5f5f5;padding:10px;border:1px solid #ddd;font-family:monospace;font-size:12px;max-height:400px;overflow:auto;'>";
        echo $recentLogs ?: '<em>Nenhum log encontrado.</em>';
        echo "</div>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ddd;font-size:11px;overflow:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "<p><a href='?route=login'>Voltar ao login</a> | <a href='?route=logs&view=full'>Ver log completo</a></p>";
    } else {
        echo "<h1>Ops! Algo deu errado.</h1>";
        echo "<p>Por favor, tente novamente mais tarde ou contate o administrador.</p>";
        echo "<p><strong>Caminho do Log:</strong> " . htmlspecialchars($logFile) . "</p>";
        echo "<p><a href='/index.php?route=login'>Voltar ao login</a></p>";
    }
}
