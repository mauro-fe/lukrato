<?php

declare(strict_types=1);

namespace Application\Controllers\Concerns;

use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\ClientErrorException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Infrastructure\LogService;
use DomainException;
use InvalidArgumentException;
use Throwable;
use ValueError;

trait HandlesApiResponses
{
    private ?array $apiJsonBodyCache = null;

    protected function ok(array $payload = [], int $status = 200): Response
    {
        $message = $payload['message'] ?? 'Success';
        if (array_key_exists('message', $payload)) {
            unset($payload['message']);
        }

        return Response::successResponse($payload, $message, $status);
    }

    protected function fail(string $message, int $status = 400, mixed $extra = null, ?string $code = null): Response
    {
        return Response::errorResponse($message, $status, $extra, $code);
    }

    protected function failResponse(string $message, int $status = 400, mixed $extra = null, ?string $code = null): Response
    {
        return Response::errorResponse($message, $status, $extra, $code);
    }

    protected function getJson(?string $key = null, mixed $default = null): mixed
    {
        $this->ensureValidJsonPayloadForApiConcern();

        if ($this->apiJsonBodyCache === null) {
            $this->apiJsonBodyCache = $this->request->json() ?? [];
        }

        if ($key === null) {
            return $this->apiJsonBodyCache;
        }

        return $this->apiJsonBodyCache[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequestPayload(): array
    {
        $payload = $this->getJson();
        if ($payload === []) {
            $payload = $this->request->post();
        }

        return is_array($payload) ? $payload : [];
    }

    private function ensureValidJsonPayloadForApiConcern(): void
    {
        if (!$this->request->hasJsonError()) {
            return;
        }

        throw new ValidationException([
            'json' => $this->request->jsonError() ?? 'JSON invalido na requisicao.',
        ], 'Validation failed', 400);
    }

    protected function failAndLog(
        Throwable $e,
        string $userMessage = 'Erro interno.',
        int $status = 500,
        array $extra = [],
        ?string $code = null
    ): Response {
        $meta = $this->reportExceptionWithReferenceForApiConcern($e, $userMessage, $extra);

        return Response::errorResponse($userMessage, $status, [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ], $code);
    }

    protected function failAndLogResponse(
        Throwable $e,
        string $userMessage = 'Erro interno.',
        int $status = 500,
        array $extra = [],
        ?string $code = null
    ): Response {
        $meta = $this->reportExceptionWithReferenceForApiConcern($e, $userMessage, $extra);

        return Response::errorResponse($userMessage, $status, [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ], $code);
    }

    protected function internalErrorResponse(
        Throwable $e,
        string $userMessage = 'Erro interno do servidor.',
        int $status = 500,
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL,
        ?string $code = null
    ): Response {
        $meta = $this->reportExceptionWithReferenceForApiConcern($e, $userMessage, $extra, $category);

        return Response::errorResponse($userMessage, $status, [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ], $code);
    }

    protected function internalErrorMessage(
        Throwable $e,
        string $userMessage = 'Erro interno. Tente novamente mais tarde.',
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL
    ): string {
        $meta = $this->reportExceptionWithReferenceForApiConcern($e, $userMessage, $extra, $category);

        return $userMessage . ' (ref: ' . $meta['error_id'] . ')';
    }

    protected function internalErrorMeta(
        Throwable $e,
        string $userMessage = 'Erro interno do servidor.',
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL
    ): array {
        $meta = $this->reportExceptionWithReferenceForApiConcern($e, $userMessage, $extra, $category);

        return [
            'error_id' => $meta['error_id'],
            'request_id' => $meta['request_id'],
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    protected function workflowFailureResponse(
        array $result,
        string $publicMessage = 'Erro interno do servidor.',
        LogCategory $category = LogCategory::GENERAL,
        array $context = []
    ): Response {
        $status = (int) ($result['status'] ?? 400);
        $message = (string) ($result['message'] ?? $publicMessage);
        $errors = $result['errors'] ?? null;
        $code = isset($result['code']) && is_string($result['code']) ? $result['code'] : null;

        if ($errors === []) {
            $errors = null;
        }

        if ($status < 500) {
            return Response::errorResponse($message, $status, is_array($errors) ? $errors : null, $code);
        }

        if (is_array($errors) && (isset($errors['error_id']) || isset($errors['request_id']))) {
            return Response::errorResponse($publicMessage, $status, $errors, $code);
        }

        $errorId = LogService::reportException(
            e: new \RuntimeException($message),
            publicMessage: $publicMessage,
            context: array_merge($context, [
                'workflow_status' => $status,
            ]),
            userId: $this->userId ?? null,
            category: $category,
        );

        return Response::errorResponse($publicMessage, $status, [
            'error_id' => $errorId,
            'request_id' => LogService::currentRequestId(),
        ], $code);
    }

    private function reportExceptionWithReferenceForApiConcern(
        Throwable $e,
        string $userMessage,
        array $extra = [],
        LogCategory $category = LogCategory::GENERAL
    ): array {
        $errorId = LogService::reportException(
            e: $e,
            publicMessage: $userMessage,
            context: array_merge([
                'url' => ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-'),
                'user_id' => $this->userId ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ], $extra),
            userId: $this->userId ?? null,
            category: $category,
        );

        return [
            'error_id' => $errorId,
            'request_id' => LogService::currentRequestId(),
        ];
    }

    protected function domainErrorResponse(
        Throwable $e,
        string $fallbackMessage,
        int $status = 400,
        array $extra = [],
        ?string $code = null
    ): Response {
        return $this->failResponse(
            $this->safeThrowableMessageForApiConcern($e, $fallbackMessage),
            $status,
            $extra !== [] ? $extra : null,
            $code
        );
    }

    protected function notFoundFromThrowable(
        Throwable $e,
        string $fallbackMessage = 'Recurso não encontrado.',
        array $extra = []
    ): Response {
        return $this->domainErrorResponse($e, $fallbackMessage, 404, $extra, 'RESOURCE_NOT_FOUND');
    }

    private function safeThrowableMessageForApiConcern(Throwable $e, string $fallbackMessage): string
    {
        if (
            !$e instanceof ValidationException
            && !$e instanceof AuthException
            && !$e instanceof ClientErrorException
            && !$e instanceof DomainException
            && !$e instanceof InvalidArgumentException
            && !$e instanceof ValueError
        ) {
            return $fallbackMessage;
        }

        $message = trim($e->getMessage());
        if ($message === '' || $this->looksLikeSensitiveThrowableMessageForApiConcern($message)) {
            return $fallbackMessage;
        }

        return $message;
    }

    private function looksLikeSensitiveThrowableMessageForApiConcern(string $message): bool
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
}
