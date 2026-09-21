<?php
/**
 * Router - Sistema de Rotas MVC
 * Responsabilidade única: Mapear rotas para controllers/actions
 */

class Router
{
    private array $routes = [];
    
    /**
     * Registra uma rota
     */
    public function addRoute(string $route, string $controller, string $action = 'index'): void
    {
        $this->routes[$route] = [
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    /**
     * Processa a requisição
     */
    public function dispatch(string $route): void
    {
        // Remove query parameters da route
        $route = strtok($route, '?');
        $route = trim($route, '/');
        
        if (empty($route)) {
            $route = 'login';
        }
        
        // Verifica se existe rota específica
        if (!isset($this->routes[$route])) {
            // Tenta mapeamento automático: route_name -> RouteNameController
            $parts = explode('_', $route);
            $controllerName = '';
            foreach ($parts as $part) {
                $controllerName .= ucfirst($part);
            }
            $controllerName .= 'Controller';
            
            $controllerClass = "Core\\MVC\\Controller\\{$controllerName}";
            
            if (class_exists($controllerClass)) {
                $this->routes[$route] = [
                    'controller' => $controllerClass,
                    'action' => 'index'
                ];
            } else {
                // Rota não encontrada
                http_response_code(404);
                echo "<h1>404 - Página não encontrada</h1>";
                echo "<p>A rota '{$route}' não foi encontrada.</p>";
                echo "<p><a href='/index.php?route=login'>Voltar ao login</a></p>";
                return;
            }
        }
        
        $routeConfig = $this->routes[$route];
        $controllerClass = $routeConfig['controller'];
        $action = $routeConfig['action'];
        
        // Verifica se controller existe
        if (!class_exists($controllerClass)) {
            throw new Exception("Controller {$controllerClass} não encontrado");
        }
        
        // Instancia controller e executa action
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $action)) {
            throw new Exception("Action {$action} não existe no controller {$controllerClass}");
        }
        
        $controller->$action();
    }
    
    /**
     * Retorna todas as rotas registradas
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}

// Registro das rotas do sistema
$router = new Router();

// Auth
$router->addRoute('login', Core\MVC\Controller\AuthController::class, 'login');
$router->addRoute('authenticate', Core\MVC\Controller\AuthController::class, 'authenticate');
$router->addRoute('logout', Core\MVC\Controller\AuthController::class, 'logout');

// Dashboard Gerencial
$router->addRoute('dashboard', Core\MVC\Controller\DashboardController::class, 'index');
$router->addRoute('dashboard_api', Core\MVC\Controller\DashboardController::class, 'apiData');

// Acompanhamento
$router->addRoute('acompanhamento', Core\MVC\Controller\AcompanhamentoController::class, 'index');
$router->addRoute('acompanhamento_api', Core\MVC\Controller\AcompanhamentoController::class, 'apiData');

// Técnico
$router->addRoute('tecnico', Core\MVC\Controller\TecnicoController::class, 'index');
$router->addRoute('tecnico_start', Core\MVC\Controller\TecnicoController::class, 'startAttendance');
$router->addRoute('tecnico_pause', Core\MVC\Controller\TecnicoController::class, 'pauseAttendance');
$router->addRoute('tecnico_resume', Core\MVC\Controller\TecnicoController::class, 'resumeAttendance');
$router->addRoute('tecnico_close', Core\MVC\Controller\TecnicoController::class, 'closeTicket');
$router->addRoute('tecnico_quick_open', Core\MVC\Controller\TecnicoController::class, 'quickOpen');
$router->addRoute('tecnico_new_tickets', Core\MVC\Controller\TecnicoController::class, 'apiNewTickets');
$router->addRoute('tecnico_current_attendance', Core\MVC\Controller\TecnicoController::class, 'apiCurrentAttendance');

// Configurações (será implementado)
$router->addRoute('config', Core\MVC\Controller\ConfigController::class, 'index');

return $router;
