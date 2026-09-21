<?php
/**
 * Autoloader do Sistema Gestão TI
 * Carrega automaticamente as classes do sistema
 */

spl_autoload_register(function ($class) {
    // Mapeamento de namespaces para diretórios
    $baseDir = __DIR__ . '/../';
    
    // Substituir namespace por separador de diretório
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // Mapear namespaces principais
    $namespaceMap = [
        'GestaoTI\\Config' => 'config',
        'GestaoTI\\Core\\Database' => 'core/database',
        'GestaoTI\\Core\\MVC\\Controller' => 'core/mvc/controller',
        'GestaoTI\\Core\\MVC\\Model' => 'core/mvc/model',
        'GestaoTI\\Core\\MVC\\View' => 'core/mvc/view',
        'GestaoTI\\Agent\\Infra' => 'core/agent/infra',
        'GestaoTI\\Agent\\Chamados' => 'core/agent/chamados',
        'GestaoTI\\Agent\\Tempo' => 'core/agent/tempo',
        'GestaoTI\\Agent\\Planejamento' => 'core/agent/planejamento',
        'GestaoTI\\Agent\\Analise' => 'core/agent/analise',
        'GestaoTI\\Modules' => 'modules'
    ];
    
    foreach ($namespaceMap as $namespace => $directory) {
        if (strpos($class, $namespace) === 0) {
            $relativeClass = substr($class, strlen($namespace) + 1);
            $file = $baseDir . $directory . '/' . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
    
    return false;
});

// Carregar arquivo de configuração
if (!function_exists('getConfig')) {
    function getConfig() {
        static $config = null;
        if ($config === null) {
            $configFile = __DIR__ . '/../config/config.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                throw new Exception('Arquivo de configuração não encontrado');
            }
        }
        return $config;
    }
}

// Carregar configurações salvas do banco se existirem
if (!function_exists('getSystemConfig')) {
    function getSystemConfig($key = null) {
        $config = getConfig();
        
        // Tentar carregar configurações do banco de dados
        try {
            $db = \GestaoTI\Core\Database\Database::getInstance();
            $settingsTable = $db->query("SELECT config_key, config_value FROM system_settings");
            $dbSettings = [];
            
            while ($row = $settingsTable->fetch(PDO::FETCH_ASSOC)) {
                $dbSettings[$row['config_key']] = json_decode($row['config_value'], true);
            }
            
            // Mesclar configurações: banco sobrescreve o padrão
            $mergedConfig = array_merge_recursive($config, ['settings' => $dbSettings]);
            
            if ($key !== null) {
                return $mergedConfig[$key] ?? null;
            }
            
            return $mergedConfig;
        } catch (Exception $e) {
            // Se não conseguir conectar ao banco, retorna configurações padrão
            if ($key !== null) {
                return $config[$key] ?? null;
            }
            return $config;
        }
    }
}
