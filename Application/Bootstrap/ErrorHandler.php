<?php

declare(strict_types=1);

namespace Application\Bootstrap;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Response;
use Application\Core\ResponseEmitter;
use Application\Services\Infrastructure\LogService;

class ErrorHandler
{
    private string $environment;
    private ResponseEmitter $responseEmitter;
    private InfrastructureRuntimeConfig $runtimeConfig;

    public function __construct(
        ?string $environment = null,
        ?ResponseEmitter $responseEmitter = null,
        ?InfrastructureRuntimeConfig $runtimeConfig = null
    ) {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, InfrastructureRuntimeConfig::class);
        $this->environment = $environment ?? $this->runtimeConfig->appEnvironment();
        $this->responseEmitter = ApplicationContainer::resolveOrNew($responseEmitter, ResponseEmitter::class);
    }

    public function register(): void
    {
        if (!defined('APP_RUNTIME_ERROR_HANDLER_REGISTERED')) {
            define('APP_RUNTIME_ERROR_HANDLER_REGISTERED', true);
        }

        if ($this->environment === 'development') {
            $this->registerDevelopmentHandlers();
        } else {
            $this->registerProductionHandlers();
        }
    }

    private function registerDevelopmentHandlers(): void
    {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL & ~E_DEPRECATED);

        set_error_handler([$this, 'handleDevelopmentError']);
        set_exception_handler([$this, 'handleDevelopmentException']);
    }

    private function registerProductionHandlers(): void
    {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

        set_error_handler([$this, 'handleProductionError']);
        set_exception_handler([$this, 'handleProductionException']);
    }

    public function handleDevelopmentError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if ($this->isAjaxRequest()) {
            $this->emit($this->buildJsonErrorResponse([
                'success' => false,
                'message' => "Erro: $message",
                'file' => $file,
                'line' => $line,
            ]));

            return true;
        }

        echo "<b>Erro:</b> $message<br><small>$file:$line</small>";

        return true;
    }

    public function handleDevelopmentException(\Throwable $e): void
    {
        if ($this->isAjaxRequest()) {
            $this->emit($this->buildJsonErrorResponse([
                'success' => false,
                'message' => 'Exceção não tratada',
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            return;
        }

        $this->emit($this->buildDevelopmentExceptionResponse($e));
    }

    public function handleProductionError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        LogService::safeErrorLog("Error: [$severity] $message in $file on line $line");

        if (in_array($severity, [E_ERROR, E_USER_ERROR, E_PARSE], true)) {
            $this->emit($this->buildProductionErrorResponse(
                new \ErrorException($message, 0, $severity, $file, $line)
            ));
        }

        return false;
    }

    public function handleProductionException(\Throwable $e): void
    {
        LogService::safeErrorLog(
            "Unhandled Exception: " . $e->getMessage() .
                " in " . $e->getFile() .
                " on line " . $e->getLine() .
                "\n" . $e->getTraceAsString()
        );

        $errorId = bin2hex(random_bytes(8));
        LogService::safeErrorLog("[error_id:{$errorId}] {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");

        $this->emit($this->buildProductionExceptionResponse($e, $errorId));
    }

    public function handleRequestError(\Throwable $e): Response
    {
        if ($this->environment === 'development') {
            return $this->buildRequestErrorResponse($e);
        }

        LogService::safeErrorLog(
            "Request Error: " . $e->getMessage() .
                " in " . $e->getFile() .
                " on line " . $e->getLine()
        );

        return $this->buildProductionErrorResponse($e);
    }

    private function isAjaxRequest(): bool
    {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['CONTENT_TYPE']) &&
            stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        );
    }

    private function buildJsonErrorResponse(array $data, int $statusCode = 500): Response
    {
        return Response::jsonResponse($data, $statusCode);
    }

    private function buildDevelopmentExceptionResponse(\Throwable $e): Response
    {
        $html = '<h2>Exceção não tratada:</h2>';
        $html .= '<p><b>' . htmlspecialchars($e->getMessage()) . '</b></p>';
        $html .= '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';

        return Response::htmlResponse($html, 500);
    }

    private function buildProductionErrorResponse(\Throwable $exception): Response
    {
        return Response::htmlResponse($this->renderErrorPageHtml($exception), 500);
    }

    private function buildProductionExceptionResponse(\Throwable $e, string $errorId): Response
    {
        if ($this->isAjaxRequest()) {
            return $this->buildJsonErrorResponse([
                'status' => 'error',
                'message' => 'Erro inesperado no servidor.',
                'error_id' => $errorId,
            ]);
        }

        return $this->buildProductionErrorResponse($e);
    }

    private function buildRequestErrorResponse(\Throwable $e): Response
    {
        $html = '<h1>Erro na requisição:</h1>';
        $html .= '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        $html .= '<h2>Stack Trace:</h2>';
        $html .= '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';

        return Response::htmlResponse($html, 500);
    }

    private function renderErrorPageHtml(?\Throwable $exception = null): string
    {
        $errorPage = BASE_PATH . '/views/errors/500.php';
        if (file_exists($errorPage)) {
            ob_start();
            include $errorPage;
            return (string) ob_get_clean();
        }

        return 'Ocorreu um erro interno. Por favor, tente novamente mais tarde.';
    }

    private function emit(Response $response): void
    {
        $this->responseEmitter->emit($response);
    }
}
