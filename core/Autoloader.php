<?php
/**
 * Autoloader PSR-4 para Gestão TI
 * Mapeia namespaces para diretórios
 */

spl_autoload_register(function ($class) {
    // Diretório base do core
    $base_dir = dirname(__DIR__) . '/';
    
    // Prefixo Core\
    $prefix = 'Core\\';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) === 0) {
        // Remove o prefixo Core\
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    return false;
});
