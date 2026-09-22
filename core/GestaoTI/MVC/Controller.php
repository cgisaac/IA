<?php
namespace GestaoTI\MVC;

abstract class Controller
{
    protected $view;
    protected $logger;

    public function __construct()
    {
        $this->view = new View();
        $this->logger = \GestaoTI\Core\Infrastructure\Logger::getInstance();
    }

    abstract public function index(): void;
}
