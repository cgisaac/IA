<?php
namespace GestaoTI\MVC\Controllers;

use GestaoTI\MVC\Controller;

class GerencialController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->view->redirect('login');
            return;
        }
        
        // Dados temporários para teste
        $data = [
            'stats' => [
                'total_chamados' => 0,
                'chamados_abertos' => 0,
                'chamados_fechados' => 0,
                'tempo_medio_atendimento' => '0h'
            ]
        ];
        
        $this->view->render('gerencial/dashboard', $data, 'default');
    }
}
