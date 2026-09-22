<?php
namespace GestaoTI\MVC\Controllers;

use GestaoTI\MVC\Controller;

class AcompanhamentoController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->view->redirect('login');
            return;
        }
        
        $data = [
            'chamados' => []
        ];
        
        $this->view->render('acompanhamento/dashboard', $data, 'default');
    }
}
