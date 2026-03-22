<?php

declare(strict_types=1);

namespace Application\Bootstrap;

use Application\Core\Response;
use Application\Core\ResponseEmitter;
use Application\Core\Router;
use Application\Services\Infrastructure\MaintenanceService;

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
        $emitter = new ResponseEmitter();

        $this->errorHandler->register();
        $this->sessionManager->start();
        $this->securityHeaders->apply();
        $this->loadConfigurations();

        $maintenanceResponse = $this->getMaintenanceResponse();
        if ($maintenanceResponse instanceof Response) {
            $emitter->emit($maintenanceResponse);
        }

        $response = $this->handleRequest();
        if ($response instanceof Response) {
            $emitter->emit($response);
        }
    }

    private function loadConfigurations(): void
    {
        $configPath = BASE_PATH . '/config/config.php';
        if (!file_exists($configPath)) {
            throw new \RuntimeException('Erro: Arquivo de configuração não encontrado.');
        }
        require_once $configPath;

        require_once BASE_PATH . '/config/vite.php';

        $routeFiles = [
            BASE_PATH . '/routes/web.php',
            BASE_PATH . '/routes/auth.php',
            BASE_PATH . '/routes/admin.php',
            BASE_PATH . '/routes/api.php',
            BASE_PATH . '/routes/webhooks.php',
        ];

        foreach ($routeFiles as $routeFile) {
            if (!file_exists($routeFile)) {
                throw new \RuntimeException("Erro: Arquivo de rotas não encontrado: {$routeFile}");
            }
            require_once $routeFile;
        }
    }

    private function handleRequest(): ?Response
    {
        try {
            $requestHandler = new RequestHandler();
            $route = $requestHandler->parseRoute();
            $method = $requestHandler->getMethod();

            return Router::run($route, $method);
        } catch (\Throwable $e) {
            return $this->errorHandler->handleRequestError($e);
        }
    }

    private function getMaintenanceResponse(): ?Response
    {
        if (!MaintenanceService::isActive()) {
            return null;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH) ?? '';

        if (str_contains($path, '/api/sysadmin/') || str_contains($path, '/sysAdmin')) {
            return null;
        }

        if (str_contains($path, '/api/auth/') || str_contains($path, '/login') || str_contains($path, '/logout')) {
            return null;
        }

        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/i', $path)) {
            return null;
        }

        if ($path === '/' || $path === '') {
            return null;
        }

        $user = \Application\Lib\Auth::user();
        if ($user && (int) $user->is_admin === 1) {
            return null;
        }

        return $this->buildMaintenanceResponse();
    }

    private function buildMaintenanceResponse(): Response
    {
        $data = MaintenanceService::getData();
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

        if ($isApi) {
            return Response::jsonResponse([
                'error' => 'Sistema em manutenção',
                'message' => $data['reason'] ?? 'Estamos realizando melhorias. Voltamos em breve!',
                'retry_after' => 300,
            ], 503)->header('Retry-After', '300');
        }

        $maintenanceView = BASE_PATH . '/views/errors/maintenance.php';

        if (file_exists($maintenanceView)) {
            ob_start();
            $reason = $data['reason'] ?? '';
            $estimatedMinutes = $data['estimated_minutes'] ?? null;
            $activatedAt = $data['activated_at'] ?? null;
            include $maintenanceView;
            $html = (string) ob_get_clean();
        } else {
            $html = '<h1>Sistema em Manutenção</h1><p>Estamos realizando melhorias. Voltamos em breve!</p>';
        }

        return Response::htmlResponse($html, 503)
            ->header('Retry-After', '300');
    }
}
