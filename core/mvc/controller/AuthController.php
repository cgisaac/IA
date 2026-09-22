<?php
namespace Core\MVC\Controller;

use Core\Agent\Infra\InfraAgent;

/**
 * AuthController - Controlador de Autenticação
 * Responsabilidade única: Gerenciar login/logout de usuários
 */
class AuthController extends Controller
{
    private InfraAgent $infraAgent;
    
    public function __construct()
    {
        $this->infraAgent = new InfraAgent();
        
        // Inicia sessão se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Exibe formulário de login
     */
    public function login(): void
    {
        // Se já estiver logado, redireciona para dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/index.php?route=dashboard');
        }
        
        echo $this->render('auth/login');
    }
    
    /**
     * Processa login
     */
    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }
        
        // Verifica CSRF
        if (!ViewHelper::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['error' => 'Token CSRF inválido'], 403);
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $this->json(['error' => 'Usuário e senha são obrigatórios'], 400);
        }
        
        try {
            // Verifica usuário hardcoded inicial
            $config = require __DIR__ . '/../../../config/config.php';
            
            $user = null;
            
            // Usuário hardcoded para configuração inicial
            if ($username === $config['initial_user']['username'] && 
                password_verify($password, $config['initial_user']['password'])) {
                $user = [
                    'id' => 1,
                    'username' => $username,
                    'name' => 'Administrador',
                    'email' => 'admin@gestaoti.local',
                    'role' => 'admin',
                    'is_hardcoded' => true
                ];
            } else {
                // Busca no banco de dados
                $db = \Core\Database\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
                $stmt->execute([$username]);
                $userRecord = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($userRecord && password_verify($password, $userRecord['password'])) {
                    $user = $userRecord;
                }
            }
            
            if (!$user) {
                $this->json(['error' => 'Usuário ou senha inválidos'], 401);
            }
            
            // Define sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_data'] = $user;
            $_SESSION['last_activity'] = time();
            
            // Regenera token CSRF
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            $this->json([
                'success' => true,
                'redirect' => '/index.php?route=dashboard',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? $user['username'],
                    'role' => $user['role']
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            $this->json(['error' => 'Erro ao processar login. Tente novamente.'], 500);
        }
    }
    
    /**
     * Processa logout
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        $this->redirect('/index.php?route=login');
    }
    
    /**
     * Verifica sessão ativa (AJAX)
     */
    public function checkSession(): void
    {
        $this->json([
            'authenticated' => $this->isAuthenticated(),
            'user' => $this->getCurrentUser()
        ]);
    }
}
