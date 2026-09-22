<?php
namespace GestaoTI\MVC;

class Router
{
    private $routes = [];
    private $logger;

    public function __construct()
    {
        $this->logger = \GestaoTI\Core\Infrastructure\Logger::getInstance();
        
        // Rotas padrão
        $this->routes = [
            '' => ['controller' => 'AuthController', 'action' => 'index'],
            'login' => ['controller' => 'AuthController', 'action' => 'index'],
            'logout' => ['controller' => 'AuthController', 'action' => 'logout'],
            'dashboard' => ['controller' => 'DashboardController', 'action' => 'index'],
            'gerencial' => ['controller' => 'GerencialController', 'action' => 'index'],
            'acompanhamento' => ['controller' => 'AcompanhamentoController', 'action' => 'index'],
            'tecnico' => ['controller' => 'TecnicoController', 'action' => 'index'],
            'config' => ['controller' => 'ConfigController', 'action' => 'index'],
            'logs' => ['controller' => 'LogsController', 'action' => 'index'],
        ];
    }

    public function dispatch(string $route): void
    {
        $this->logger->info("Aplicação iniciada - Route: {$route}");
        
        if (!isset($this->routes[$route])) {
            $this->logger->error("Rota não encontrada: {$route}");
            $this->showError('Rota não encontrada', 404);
            return;
        }

        $routeConfig = $this->routes[$route];
        $controllerName = "GestaoTI\\MVC\\Controllers\\" . $routeConfig['controller'];
        $action = $routeConfig['action'];

        try {
            if (!class_exists($controllerName)) {
                throw new \Exception("Controller {$controllerName} não encontrado");
            }

            $controller = new $controllerName();
            
            if (!method_exists($controller, $action)) {
                throw new \Exception("Ação {$action} não encontrada no controller {$controllerName}");
            }

            $controller->$action();
            
        } catch (\Exception $e) {
            $this->logger->error("Erro na aplicação", [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->showError($e->getMessage());
        }
    }

    private function showError(string $message, int $code = 500): void
    {
        http_response_code($code);
        echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <title>Erro na Aplicação</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; }
        h1 { color: #d32f2f; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Ops! Algo deu errado.</h1>
    <div class='error'>
        <strong>Mensagem:</strong> {$message}<br><br>
        Por favor, tente novamente mais tarde ou contate o administrador.
    </div>
    <h3>Logs Recentes (últimas 20 linhas):</h3>
    <pre>";
        
        try {
            $logs = array_reverse($this->logger->getLastLines(20));
            foreach ($logs as $log) {
                echo htmlspecialchars($log);
            }
        } catch (\Exception $e) {
            echo "Não foi possível carregar os logs.";
        }
        
        echo "</pre>
</body>
</html>";
    }
}
