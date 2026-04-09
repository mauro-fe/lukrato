<?php

declare(strict_types=1);

namespace Application\Core\Routing;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Exceptions\ClientErrorException;
use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Core\Response;
use Application\Services\Infrastructure\LogService;
use DomainException;
use InvalidArgumentException;
use Throwable;
use ValueError;

class HttpExceptionHandler
{
    private ErrorResponseFactory $errorResponseFactory;
    private InfrastructureRuntimeConfig $runtimeConfig;

    public function __construct(
        ?ErrorResponseFactory $errorResponseFactory = null,
        ?InfrastructureRuntimeConfig $runtimeConfig = null
    ) {
        $this->errorResponseFactory = ApplicationContainer::resolveOrNew(
            $errorResponseFactory,
            ErrorResponseFactory::class
        );
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, InfrastructureRuntimeConfig::class);
    }

    public function handle(Throwable $e, ?array $routeContext = null, ?Request $request = null): Response
    {
        if ($e instanceof AuthException || $e instanceof ValidationException) {
            return $this->handleKnownException($e);
        }

        if ($this->isSafeDomainException($e)) {
            return Response::errorResponse(
                $this->safeThrowableMessage($e, 'Operacao invalida.'),
                $this->normalizeStatusCode($e->getCode(), 400),
                ['request_id' => LogService::currentRequestId()]
            );
        }

        return $this->handleThrowable($e, $routeContext, $request);
    }

    public function handleKnownException(Throwable $e): Response
    {
        if ($e instanceof AuthException) {
            $statusCode = $this->normalizeStatusCode((int) $e->getCode(), 401);
            $message = $this->safeThrowableMessage($e, $statusCode === 403 ? 'Acesso negado' : 'Nao autenticado');

            return Response::errorResponse($message, $statusCode, [
                'request_id' => LogService::currentRequestId(),
            ]);
        }

        if ($e instanceof ValidationException) {
            return Response::errorResponse(
                $this->safeThrowableMessage($e, 'Validation failed'),
                $this->normalizeStatusCode((int) $e->getCode(), 422),
                array_merge($e->getErrors(), [
                    'request_id' => LogService::currentRequestId(),
                ])
            );
        }

        return Response::errorResponse('Erro interno no servidor.', 500, [
            'request_id' => LogService::currentRequestId(),
        ]);
    }

    public function handleThrowable(Throwable $e, ?array $routeContext = null, ?Request $request = null): Response
    {
        $errorId = LogService::reportException(
            e: $e,
            publicMessage: 'Erro interno no servidor.',
            context: [
                'route_path' => $routeContext['path'] ?? 'desconhecida',
                'route_method' => $routeContext['method'] ?? ($_SERVER['REQUEST_METHOD'] ?? null),
                'url' => ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-'),
            ],
            category: \Application\Enums\LogCategory::GENERAL,
        );

        $isDev = $this->runtimeConfig->isDevelopment();
        $wantsJson = $request?->wantsJson() || $request?->isAjax();
        $requestId = LogService::currentRequestId();

        if ($wantsJson && !$isDev) {
            return Response::errorResponse('Erro interno no servidor.', 500, [
                'error_id' => $errorId,
                'request_id' => $requestId,
            ]);
        }

        if ($isDev) {
            $html = '<h1>Erro na Aplicação</h1><pre>';
            $html .= '<strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n";
            $html .= '<strong>Arquivo:</strong> ' . $e->getFile() . ' (Linha ' . $e->getLine() . ")\n\n";
            $html .= '<strong>Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString());
            $html .= '</pre>';

            return Response::htmlResponse($html, 500);
        }

        return $this->errorResponses()->viewError(500, BASE_PATH . '/views/errors/500.php', 'Erro no servidor', [
            'error_id' => $errorId,
            'request_id' => $requestId,
        ]);
    }

    private function normalizeStatusCode(int $statusCode, int $fallback): int
    {
        return $statusCode >= 400 && $statusCode <= 599
            ? $statusCode
            : $fallback;
    }

    private function isSafeDomainException(Throwable $e): bool
    {
        return $e instanceof ClientErrorException
            || $e instanceof DomainException
            || $e instanceof InvalidArgumentException
            || $e instanceof ValueError;
    }

    private function safeThrowableMessage(Throwable $e, string $fallbackMessage): string
    {
        $message = trim($e->getMessage());

        if ($message === '' || $this->looksSensitive($message)) {
            return $fallbackMessage;
        }

        return $message;
    }

    private function looksSensitive(string $message): bool
    {
        $normalized = strtolower($message);
        $markers = [
            'sqlstate',
            'syntax error',
            'stack trace',
            'failed to open stream',
            'uncaught',
            'pdoexception',
            'queryexception',
            'integrity constraint',
            'table ',
            'column ',
            'insert into',
            'update `',
            'delete from',
            'select *',
            ' on line ',
            ' in c:\\',
            ' in /',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function errorResponses(): ErrorResponseFactory
    {
        return $this->errorResponseFactory;
    }
}
