<?php
namespace GestaoTI\MVC\Controllers;

use GestaoTI\MVC\Controller;

class TecnicoController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->view->redirect('login');
            return;
        }
        
        $data = [
            'chamados' => [],
            'atividades' => []
        ];
        
        $this->view->render('tecnico/dashboard', $data, 'default');
    }
}
