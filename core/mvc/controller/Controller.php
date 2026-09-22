<?php
namespace Core\MVC\Controller;

/**
 * Controller Base - Classe abstrata para todos os controllers
 * Responsabilidade única: Gerenciar requisições e retornar respostas
 */
abstract class Controller
{
    protected array $data = [];
    protected string $layout = 'default';
    
    /**
     * Define dados para a view
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
    
    /**
     * Retorna dados para a view
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
    
    /**
     * Renderiza uma view
     */
    public function render(string $view, array $extraData = []): string
    {
        $data = array_merge($this->data, $extraData);
        extract($data);
        
        $viewPath = __DIR__ . "/../view/{$view}.php";
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View {$view} não encontrada");
        }
        
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
    
    /**
     * Retorna JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redireciona para outra URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Verifica se usuário está autenticado
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Requer autenticação
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/index.php?route=login');
        }
    }
    
    /**
     * Retorna usuário logado
     */
    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user_data'] ?? null;
    }
}
