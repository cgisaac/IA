<?php
/**
 * Configuração Inicial do Sistema Gestão TI
 * Este arquivo contém as configurações hardcoded iniciais
 * para setup do sistema e conexão com banco de dados
 */

return [
    // Versão atual do sistema
    'version' => '1.0.0',
    
    // Usuário inicial hardcoded para configuração
    'initial_user' => [
        'username' => 'admin',
        'password' => 'gestaoti2024', // Deve ser trocado no primeiro acesso
        'role' => 'administrator'
    ],
    
    // Configurações padrão do banco de dados local
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'gestao_ti',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    
    // Configurações padrão da API GLPI
    'glpi' => [
        'url' => 'http://localhost/glpi',
        'app_token' => '',
        'user_token' => '',
        'ssl_verify' => false
    ],
    
    // Parâmetros configuráveis do sistema
    'settings' => [
        // Sincronismo com GLPI (em segundos)
        'sync_interval' => 300, // 5 minutos padrão
        
        // Polling para notificações (em segundos)
        'notification_polling' => 30, // 30 segundos padrão
        
        // Tempo para escalonamento de tarefas (em minutos)
        'escalation_time' => 120, // 120 minutos padrão
        
        // Categoria padrão para abertura rápida de chamados
        'default_category_id' => null, // Deve ser configurado
        
        // Tempo máximo de atendimento (em horas)
        'max_attendance_time' => 8,
        
        // Tempo de almoço (em minutos)
        'lunch_break' => 60
    ],
    
    // Caminhos do sistema
    'paths' => [
        'root' => dirname(__DIR__),
        'config' => dirname(__DIR__) . '/config',
        'logs' => dirname(__DIR__) . '/logs',
        'modules' => dirname(__DIR__) . '/modules',
        'public' => dirname(__DIR__) . '/public'
    ],
    
    // Timezone
    'timezone' => 'America/Sao_Paulo'
];
