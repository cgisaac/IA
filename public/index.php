<?php
/**
 * Front Controller - Ponto único de entrada da aplicação
 * Gestão TI - Sistema de Controle de Atendimentos de TI
 */

// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// Carrega autoload
require_once __DIR__ . '/../core/Autoloader.php';

// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Carrega configurações
    $config = require __DIR__ . '/../config/config.php';
    
    // Carrega router
    $router = require __DIR__ . '/../core/mvc/Router.php';
    
    // Obtém route da URL
    $route = $_GET['route'] ?? 'login';
    
    // Dispatch da rota
    $router->dispatch($route);
    
} catch (\Exception $e) {
    // Log do erro
    error_log("Erro na aplicação: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    
    // Exibe mensagem amigável
    http_response_code(500);
    
    if ($config['debug'] ?? false) {
        echo "<h1>Erro na Aplicação</h1>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>Ops! Algo deu errado.</h1>";
        echo "<p>Por favor, tente novamente mais tarde ou contate o administrador.</p>";
        echo "<p><a href='/index.php?route=login'>Voltar ao login</a></p>";
    }
}
