<?php
namespace GestaoTI\Agent;

use GestaoTI\Core\Infrastructure\Database;
use GestaoTI\Core\Infrastructure\Logger;

class InfraAgent
{
    private $db;
    private $logger;
    private $glpiConfig;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->loadGlpiConfig();
    }

    private function loadGlpiConfig(): void
    {
        // Carrega configuração do GLPI do banco ou config
        $this->glpiConfig = [
            'url' => $_ENV['GLPI_URL'] ?? '',
            'app_token' => $_ENV['GLPI_APP_TOKEN'] ?? '',
            'user_token' => $_ENV['GLPI_USER_TOKEN'] ?? ''
        ];
    }

    public function testGlpiConnection(): bool
    {
        // Testa conexão com API do GLPI
        if (empty($this->glpiConfig['url'])) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->glpiConfig['url'] . '/apirest.php/initSession');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'App-Token: ' . $this->glpiConfig['app_token'],
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }

    public function syncTickets(): array
    {
        // Sincroniza tickets do GLPI
        $this->logger->info('Iniciando sincronização de tickets com GLPI');
        
        // Implementação da sincronização
        return [];
    }

    public function getGlpiConfig(): array
    {
        return $this->glpiConfig;
    }
}
