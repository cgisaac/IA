<?php
namespace GestaoTI\MVC\Controllers;

use GestaoTI\MVC\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->view->redirect('login');
            return;
        }
        
        $data = [
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'] ?? 'user'
        ];
        
        $this->view->render('gerencial/dashboard', $data, 'default');
    }
}
