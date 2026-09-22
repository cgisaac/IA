<?php
namespace GestaoTI\MVC;

class View
{
    public function render(string $view, array $data = [], string $layout = 'default'): void
    {
        extract($data);
        
        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        $layoutFile = dirname(__DIR__) . "/Views/layouts/{$layout}.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View {$view} não encontrada em {$viewFile}");
        }
        
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    public function renderPartial(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View parcial {$view} não encontrada em {$viewFile}");
        }
        
        include $viewFile;
    }

    public function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function redirect(string $route): void
    {
        header("Location: index.php?route={$route}");
        exit;
    }
}
