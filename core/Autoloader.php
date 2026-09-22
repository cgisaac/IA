<?php
/**
 * Autoloader PSR-4 para Gestão TI
 * Mapeia namespaces para diretórios
 */

spl_autoload_register(function ($class) {
    // Prefixo do namespace
    $prefix = 'GestaoTI\\';
    
    // Diretório base da raiz do projeto
    $base_dir = dirname(__DIR__) . '/';
    
    // Verifica se a classe usa o prefixo
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Pega o nome da classe relativo ao prefixo
    $relative_class = substr($class, $len);
    
    // Substitui separadores de namespace por separadores de diretório
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Se o arquivo existir, inclui
    if (file_exists($file)) {
        require $file;
    } else {
        // Tenta encontrar em core/ se não achar na raiz
        $core_file = $base_dir . 'core/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($core_file)) {
            require $core_file;
        }
    }
});
