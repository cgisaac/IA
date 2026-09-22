<?php
namespace GestaoTI\Core;

class Autoloader
{
    private static $instance = null;
    private $basePath;

    private function __construct()
    {
        $this->basePath = dirname(dirname(__DIR__));
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    public function load(string $class): void
    {
        $prefix = 'GestaoTI\\';
        $baseDir = $this->basePath . '/GestaoTI/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        } else {
            throw new \Exception("Arquivo da classe {$class} não encontrado em {$file}");
        }
    }
}
