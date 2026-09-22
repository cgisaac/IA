<?php
/**
 * Script de teste de diagnóstico
 * Execute em: http://localhost/IA/public/test.php
 */

echo "<h1>Diagnóstico do Sistema Gestão TI</h1>";
echo "<hr>";

// Teste 1: Verifica se Autoloader existe
echo "<h2>1. Verificando Autoloader</h2>";
$autoloaderPath = dirname(__DIR__) . '/core/Autoloader.php';
if (file_exists($autoloaderPath)) {
    echo "✅ Autoloader encontrado em: $autoloaderPath<br>";
    require_once $autoloaderPath;
    echo "✅ Autoloader carregado com sucesso<br>";
} else {
    echo "❌ Autoloader NÃO encontrado em: $autoloaderPath<br>";
    echo "Caminho procurado: " . realpath(dirname(__DIR__)) . "/core/Autoloader.php<br>";
}

echo "<hr>";

// Teste 2: Verifica se Logger pode ser carregado
echo "<h2>2. Verificando Logger</h2>";
try {
    $logger = \GestaoTI\Core\Infrastructure\Logger::getInstance();
    echo "✅ Logger instanciado com sucesso<br>";
    
    // Tenta gravar log
    $logger->info('Teste de log realizado via test.php');
    echo "✅ Log gravado com sucesso<br>";
    
    // Mostra caminho do log
    $logPath = dirname(__DIR__) . '/storage/logs/app.log';
    echo "📁 Arquivo de log: $logPath<br>";
    
    if (file_exists($logPath)) {
        echo "✅ Arquivo de log existe<br>";
        echo "<pre style='background:#f0f0f0;padding:10px;'>";
        echo file_get_contents($logPath);
        echo "</pre>";
    } else {
        echo "⚠️ Arquivo de log ainda não foi criado<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao usar Logger: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

echo "<hr>";

// Teste 3: Verifica estrutura de pastas
echo "<h2>3. Verificando Estrutura de Pastas</h2>";
$dirs = [
    'Raiz' => dirname(__DIR__),
    'Core' => dirname(__DIR__) . '/core',
    'Config' => dirname(__DIR__) . '/config',
    'Public' => __DIR__,
    'Storage' => dirname(__DIR__) . '/storage',
    'Logs' => dirname(__DIR__) . '/storage/logs'
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        echo "✅ $name: $path<br>";
    } else {
        echo "❌ $name NÃO existe: $path<br>";
        // Tenta criar
        if (@mkdir($path, 0777, true)) {
            echo "   📁 Pasta criada com sucesso<br>";
        } else {
            echo "   ⚠️ Não foi possível criar a pasta<br>";
        }
    }
}

echo "<hr>";

// Teste 4: Verifica permissões de escrita
echo "<h2>4. Verificando Permissões de Escrita</h2>";
$testFile = dirname(__DIR__) . '/storage/logs/test_write.txt';
if (@file_put_contents($testFile, 'teste')) {
    echo "✅ Permissão de escrita OK<br>";
    @unlink($testFile);
} else {
    echo "❌ Sem permissão de escrita em storage/logs<br>";
    echo "Solução: Crie a pasta manualmente e dê permissão total<br>";
}

echo "<hr>";

// Teste 5: Versão do PHP
echo "<h2>5. Informações do Servidor</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
echo "Script Path: " . __FILE__ . "<br>";

echo "<hr>";
echo "<p><strong>Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Se todos os testes passaram, acesse <a href='index.php?route=login'>Tela de Login</a></li>";
echo "<li>Se houve erro no Logger, crie manualmente a pasta <code>storage/logs</code></li>";
echo "<li>Verifique o arquivo de log para mais detalhes de erros</li>";
echo "</ol>";
