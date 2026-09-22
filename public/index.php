<?php
/**
 * Front Controller - Ponto único de entrada da aplicação
 * Gestão TI - Sistema de Controle de Atendimentos de TI
 */

// Inicia sessão antes de qualquer coisa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// Carrega Autoloader
require_once __DIR__ . '/../core/GestaoTI/Core/Autoloader.php';

use GestaoTI\Core\Autoloader;
use GestaoTI\Core\Infrastructure\Logger;
use GestaoTI\MVC\Router;

try {
    // Registra autoloader
    $autoloader = Autoloader::getInstance();
    $autoloader->register();
    
    // Inicializa logger
    $logger = Logger::getInstance();
    
    // Pega rota da URL
    $route = $_GET['route'] ?? '';
    
    // Remove barras duplas e normaliza
    $route = trim($route, '/');
    
    // Dispatch da rota
    $router = new Router();
    $router->dispatch($route);
    
} catch (\Exception $e) {
    // Erro fatal durante inicialização
    http_response_code(500);
    echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <title>Erro Fatal</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; }
        h1 { color: #d32f2f; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Erro Fatal na Aplicação</h1>
    <div class='error'>
        <strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "<br>
        <strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br><br>
        Por favor, contate o administrador do sistema.
    </div>
</body>
</html>";
}
