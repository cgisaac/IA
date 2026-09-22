<?php
namespace GestaoTI\MVC\Controllers;

use GestaoTI\MVC\Controller;

class ConfigController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->view->redirect('login');
            return;
        }
        
        $data = ['message' => ''];
        
        // Processa formulário de configuração
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Salvar configurações (implementar depois)
                $data['message'] = 'Configurações salvas com sucesso!';
            } catch (\Exception $e) {
                $data['error'] = 'Erro ao salvar configurações: ' . $e->getMessage();
            }
        }
        
        $this->view->render('config/index', $data, 'default');
    }
}
