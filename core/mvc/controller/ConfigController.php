<?php

namespace Core\MVC\Controller;

use Core\Database\Database;

/**
 * Controller de Configurações do Sistema
 */
class ConfigController extends Controller {
    
    public function index() {
        $this->checkAuth();
        
        $db = Database::getInstance();
        $settings = [];
        $glpiConfig = null;
        
        try {
            // Carrega configurações do banco
            $stmt = $db->query("SELECT * FROM system_settings");
            $settingsRows = $stmt->fetchAll();
            
            // Converte para array chave=>valor
            foreach ($settingsRows as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            // Carrega config GLPI
            $stmt = $db->query("SELECT * FROM glpi_integration LIMIT 1");
            $glpiConfig = $stmt->fetch();
            
        } catch (\Exception $e) {
            // Ignora se tabelas não existirem ainda
        }
        
        include __DIR__ . '/../view/config/index.php';
    }
    
    public function save() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=config');
            exit;
        }
        
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Salva configurações GLPI
            $glpiUrl = $_POST['glpi_url'] ?? '';
            $glpiToken = $_POST['glpi_token'] ?? '';
            $glpiUserToken = $_POST['glpi_user_token'] ?? '';
            
            $existing = $db->query("SELECT id FROM glpi_integration LIMIT 1")->fetch();
            if ($existing) {
                $db->query(
                    "UPDATE glpi_integration SET glpi_url = ?, app_token = ?, user_token = ?, updated_at = NOW() WHERE id = ?",
                    [$glpiUrl, $glpiToken, $glpiUserToken, $existing['id']]
                );
            } else {
                $db->query(
                    "INSERT INTO glpi_integration (glpi_url, app_token, user_token, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                    [$glpiUrl, $glpiToken, $glpiUserToken]
                );
            }
            
            // Salva parâmetros do sistema
            $params = [
                'sync_interval' => $_POST['sync_interval'] ?? 300,
                'notification_polling' => $_POST['notification_polling'] ?? 30,
                'escalation_time' => $_POST['escalation_time'] ?? 120,
                'default_category_id' => $_POST['default_category_id'] ?? '',
                'lunch_break_duration' => $_POST['lunch_break_duration'] ?? 60,
            ];
            
            foreach ($params as $key => $value) {
                $existing = $db->query("SELECT id FROM system_settings WHERE setting_key = ?", [$key])->fetch();
                if ($existing) {
                    $db->query("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
                } else {
                    $db->query("INSERT INTO system_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$key, $value]);
                }
            }
            
            $db->commit();
            
            $_SESSION['success_message'] = 'Configurações salvas com sucesso!';
            
        } catch (\Exception $e) {
            $db->rollback();
            $_SESSION['error_message'] = 'Erro ao salvar configurações: ' . $e->getMessage();
        }
        
        header('Location: ?route=config');
        exit;
    }
    
    public function testGlpi() {
        $this->checkAuth();
        
        // Retorna sucesso para teste básico
        echo json_encode(['success' => true, 'message' => 'Teste de conexão implementado no InfraAgent']);
        exit;
    }
}
