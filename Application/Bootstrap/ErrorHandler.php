<?php

declare(strict_types=1);

namespace Application\Bootstrap;

class ErrorHandler
{
    private string $environment;

    public function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    public function register(): void
    {
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
            $this->sendJsonError([
                'success' => false,
                'message' => "Erro: $message",
                'file' => $file,
                'line' => $line
            ]);
        } else {
            echo "<b>Erro:</b> $message<br><small>$file:$line</small>";
        }

        return true;
    }

    public function handleDevelopmentException(\Throwable $e): void
    {
        if ($this->isAjaxRequest()) {
            $this->sendJsonError([
                'success' => false,
                'message' => 'Exceção não tratada',
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        } else {
            echo "<h2>Exceção não tratada:</h2>";
            echo "<p><b>{$e->getMessage()}</b></p>";
            echo "<pre>{$e->getTraceAsString()}</pre>";
        }
    }

    public function handleProductionError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        error_log("Error: [$severity] $message in $file on line $line");

        if (in_array($severity, [E_ERROR, E_USER_ERROR, E_PARSE])) {
            http_response_code(500);
            $this->showErrorPage();
            exit;
        }

        return false;
    }

    public function handleProductionException(\Throwable $e): void
    {
        error_log(
            "Unhandled Exception: " . $e->getMessage() .
                " in " . $e->getFile() .
                " on line " . $e->getLine() .
                "\n" . $e->getTraceAsString()
        );

        if ($this->isAjaxRequest()) {
            $this->sendJsonError([
                'status' => 'error',
                'message' => 'Erro inesperado no servidor.',
                'details' => $e->getMessage()
            ]);
        } else {
            http_response_code(500);
            $this->showErrorPage();
        }

        exit;
    }

    public function handleRequestError(\Throwable $e): void
    {
        if ($this->environment === 'development') {
            echo '<h1>Erro na requisição:</h1>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            echo '<h2>Stack Trace:</h2>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            error_log(
                "Request Error: " . $e->getMessage() .
                    " in " . $e->getFile() .
                    " on line " . $e->getLine()
            );

            http_response_code(500);
            $this->showErrorPage();
        }
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

    private function sendJsonError(array $data, int $statusCode = 500): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    private function showErrorPage(): void
    {
        $errorPage = BASE_PATH . '/views/errors/500.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo 'Ocorreu um erro interno. Por favor, tente novamente mais tarde.';
        }
    }
}
