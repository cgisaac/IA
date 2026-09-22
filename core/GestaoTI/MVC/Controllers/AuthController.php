<?php
namespace GestaoTI\MVC\Controllers;

use GestaoTI\MVC\Controller;

class AuthController extends Controller
{
    public function index(): void
    {
        // Verifica se já está logado
        if (isset($_SESSION['user_id'])) {
            $this->view->redirect('dashboard');
            return;
        }
        
        $this->view->render('auth/login', [], 'default');
    }

    public function login(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Usuário hardcoded inicial
        if ($username === 'admin' && $password === 'gestaoti2024') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            
            $this->logger->info("Usuário admin logado com sucesso");
            $this->view->redirect('dashboard');
            return;
        }
        
        $this->logger->warning("Tentativa de login falhou para usuário: {$username}");
        $this->view->render('auth/login', ['error' => 'Usuário ou senha inválidos'], 'default');
    }

    public function logout(): void
    {
        session_destroy();
        $this->view->redirect('login');
    }
}
