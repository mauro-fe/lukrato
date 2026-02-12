<?php

declare(strict_types=1);

namespace Application\Bootstrap;

use Application\Core\Router;
use Application\Services\MaintenanceService;

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

        // Verificar modo manutenção antes de processar a request
        if ($this->isMaintenanceMode()) {
            return;
        }

        $this->handleRequest();
    }

    private function loadConfigurations(): void
    {
        $configPath = BASE_PATH . '/config/config.php';
        if (!file_exists($configPath)) {
            die('Erro: Arquivo de configuração não encontrado.');
        }
        require_once $configPath;

        // Carregar arquivos de rotas
        $routeFiles = [
            BASE_PATH . '/routes/web.php',
            BASE_PATH . '/routes/auth.php',
            BASE_PATH . '/routes/admin.php',
            BASE_PATH . '/routes/api.php',
            BASE_PATH . '/routes/webhooks.php',
        ];

        foreach ($routeFiles as $routeFile) {
            if (!file_exists($routeFile)) {
                die("Erro: Arquivo de rotas não encontrado: {$routeFile}");
            }
            require_once $routeFile;
        }
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

    /**
     * Verifica modo manutenção.
     * Permite acesso de SysAdmins e rotas da API de manutenção.
     */
    private function isMaintenanceMode(): bool
    {
        if (!MaintenanceService::isActive()) {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH) ?? '';

        // Permitir rotas de sysadmin (para poder desativar manutenção)
        if (str_contains($path, '/api/sysadmin/') || str_contains($path, '/sysAdmin')) {
            return false;
        }

        // Permitir rotas de auth (para sysadmin conseguir logar)
        if (str_contains($path, '/api/auth/') || str_contains($path, '/login')) {
            return false;
        }

        // Permitir assets estáticos
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/i', $path)) {
            return false;
        }

        // SysAdmin logado pode navegar normalmente
        $user = \Application\Lib\Auth::user();
        if ($user && (int) $user->is_admin === 1) {
            return false;
        }

        // Mostrar página de manutenção
        $this->showMaintenancePage();
        return true;
    }

    /**
     * Renderiza a página de manutenção
     */
    private function showMaintenancePage(): void
    {
        $data = MaintenanceService::getData();
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');

        if ($isApi) {
            http_response_code(503);
            header('Content-Type: application/json');
            header('Retry-After: 300');
            echo json_encode([
                'error' => 'Sistema em manutenção',
                'message' => $data['reason'] ?? 'Estamos realizando melhorias. Voltamos em breve!',
                'retry_after' => 300,
            ]);
            exit;
        }

        http_response_code(503);
        header('Retry-After: 300');

        $maintenanceView = BASE_PATH . '/views/errors/maintenance.php';
        if (file_exists($maintenanceView)) {
            $reason = $data['reason'] ?? '';
            $estimatedMinutes = $data['estimated_minutes'] ?? null;
            $activatedAt = $data['activated_at'] ?? null;
            include $maintenanceView;
        } else {
            echo '<h1>Sistema em Manutenção</h1><p>Estamos realizando melhorias. Voltamos em breve!</p>';
        }
        exit;
    }
}
