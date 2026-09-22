<?php
/**
 * Script de Diagnóstico do Sistema Gestão TI
 */

echo "<h1>Diagnóstico do Sistema Gestão TI</h1>";
echo "<hr>";

// 1. Verificando Autoloader
echo "<h2>1. Verificando Autoloader</h2>";
$autoloaderPath = __DIR__ . '/../core/GestaoTI/Core/Autoloader.php';
if (file_exists($autoloaderPath)) {
    echo "✅ Autoloader encontrado em: {$autoloaderPath}<br>";
    require_once $autoloaderPath;
    echo "✅ Autoloader carregado com sucesso<br>";
} else {
    echo "❌ Autoloader NÃO encontrado em: {$autoloaderPath}<br>";
    exit;
}

// 2. Verificando Logger
echo "<h2>2. Verificando Logger</h2>";
try {
    use GestaoTI\Core\Autoloader;
    $autoloader = Autoloader::getInstance();
    $autoloader->register();
    
    use GestaoTI\Core\Infrastructure\Logger;
    $logger = Logger::getInstance();
    echo "✅ Logger instanciado com sucesso<br>";
    
    // Testa escrita de log
    $logger->info("Teste de log realizado via test.php");
    echo "✅ Log de teste gravado com sucesso<br>";
    echo "📁 Arquivo de log: " . $logger->getLogFile() . "<br>";
} catch (\Exception $e) {
    echo "❌ Erro ao usar Logger: " . $e->getMessage() . "<br>";
    echo "📍 Stack trace:<pre>" . $e->getTraceAsString() . "</pre>";
}

// 3. Estrutura de pastas
echo "<h2>3. Estrutura de Pastas</h2>";
$dirs = [
    '/core/GestaoTI/Core',
    '/core/GestaoTI/Core/Infrastructure',
    '/core/GestaoTI/MVC',
    '/core/GestaoTI/MVC/Controllers',
    '/core/GestaoTI/MVC/Views',
    '/core/GestaoTI/Agent',
    '/storage/logs',
    '/config',
    '/public'
];

foreach ($dirs as $dir) {
    $fullPath = dirname(__DIR__) . $dir;
    if (is_dir($fullPath)) {
        echo "✅ {$dir}<br>";
    } else {
        echo "❌ {$dir} - NÃO EXISTE<br>";
    }
}

// 4. Arquivos essenciais
echo "<h2>4. Arquivos Essenciais</h2>";
$files = [
    '/core/GestaoTI/Core/Autoloader.php',
    '/core/GestaoTI/Core/Infrastructure/Logger.php',
    '/core/GestaoTI/Core/Infrastructure/Database.php',
    '/core/GestaoTI/MVC/Router.php',
    '/core/GestaoTI/MVC/Controller.php',
    '/core/GestaoTI/MVC/View.php',
    '/core/GestaoTI/MVC/Controllers/AuthController.php',
    '/config/config.php',
    '/public/index.php'
];

foreach ($files as $file) {
    $fullPath = dirname(__DIR__) . $file;
    if (file_exists($fullPath)) {
        echo "✅ {$file}<br>";
    } else {
        echo "❌ {$file} - NÃO EXISTE<br>";
    }
}

echo "<hr>";
echo "<h2>5. Informações do Servidor</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server OS: " . PHP_OS . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path: " . __FILE__ . "<br>";
