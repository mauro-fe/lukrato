<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', realpath(__DIR__));
define('ASSETS_URL', '/lukrato/public/assets/');

if (!function_exists('emitGenericBootstrapFailure')) {
    /**
     * Emite resposta genérica de bootstrap sem vazar detalhes internos.
     */
    function emitGenericBootstrapFailure(string $logMessage, ?Throwable $exception = null): never
    {
        http_response_code(500);

        if (class_exists(\Application\Services\Infrastructure\LogService::class)) {
            $errorId = bin2hex(random_bytes(8));
            \Application\Services\Infrastructure\LogService::safeErrorLog("[bootstrap_error_id:{$errorId}] {$logMessage}");

            if ($exception !== null) {
                \Application\Services\Infrastructure\LogService::safeErrorLog(sprintf(
                    '[bootstrap_error_id:%s] %s in %s:%d',
                    $errorId,
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                ));
            }
        }

        echo 'Erro interno do servidor. Por favor, tente novamente mais tarde.';
        exit;
    }
}

$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo 'Erro interno do servidor. Por favor, tente novamente mais tarde.';
    exit;
}
require_once $autoloadPath;

$envPath = BASE_PATH . '/.env';
if (!file_exists($envPath)) {
    emitGenericBootstrapFailure('Arquivo .env ausente durante bootstrap.');
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();

    $env = $_ENV['APP_ENV'] ?? 'production';
    if ($env === 'production') {
        $dotenv->required(['APP_NAME', 'DB_HOST', 'DB_NAME', 'DB_USER'])->notEmpty();
    }
} catch (Exception $e) {
    emitGenericBootstrapFailure('Falha ao carregar configurações de ambiente.', $e);
}

if (PHP_SAPI !== 'cli') {
    register_shutdown_function(static function (): void {
        try {
            if (function_exists('fastcgi_finish_request')) {
                @session_write_close();
                @fastcgi_finish_request();
            }

            (new \Application\Services\Communication\ScheduledCampaignHeartbeatService())->tick();
        } catch (Throwable $e) {
            if (class_exists(\Application\Services\Infrastructure\LogService::class)) {
                \Application\Services\Infrastructure\LogService::safeErrorLog(
                    '[scheduled_campaign_heartbeat] ' . $e->getMessage()
                );
            }
        }
    });
}

ini_set('session.cookie_lifetime', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $env === 'production' ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');

try {
    $app = new Application\Bootstrap\Application();
    $app->run();
} catch (Throwable $e) {
    emitGenericBootstrapFailure('Falha não tratada no bootstrap da aplicação.', $e);
}
