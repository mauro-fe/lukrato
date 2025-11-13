<?php

declare(strict_types=1);

namespace Application\Bootstrap;

use Application\Core\Router;

class Application
{
    private string $environment;
    private ErrorHandler $errorHandler;
    private SessionManager $sessionManager;
    private SecurityHeaders $securityHeaders;

    public function __construct()
    {
        $this->environment = $_ENV['APP_ENV'] ?? 'production';
        $this->errorHandler = new ErrorHandler($this->environment);
        $this->sessionManager = new SessionManager();
        $this->securityHeaders = new SecurityHeaders();
    }

    public function run(): void
    {
        $this->errorHandler->register();
        $this->sessionManager->start();
        $this->securityHeaders->apply();
        $this->loadConfigurations();
        $this->handleRequest();
    }

    private function loadConfigurations(): void
    {
        $configPath = BASE_PATH . '/config/config.php';
        if (!file_exists($configPath)) {
            die('Erro: Arquivo de configuração não encontrado.');
        }
        require_once $configPath;

        $routesPath = BASE_PATH . '/routes/web.php';
        if (!file_exists($routesPath)) {
            die('Erro: Arquivo de rotas não encontrado.');
        }
        require_once $routesPath;
    }

    private function handleRequest(): void
    {
        try {
            $requestHandler = new RequestHandler();
            $route = $requestHandler->parseRoute();
            $method = $requestHandler->getMethod();

            Router::run($route, $method);
        } catch (\Throwable $e) {
            $this->errorHandler->handleRequestError($e);
        }
    }
}
