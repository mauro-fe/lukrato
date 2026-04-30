<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\DTO\ServiceResultDTO;
use Application\Enums\LogCategory;
use Application\Models\Usuario;

final class TestableApiController extends ApiController
{
    public function callRequireUserId(): int
    {
        return $this->requireUserId();
    }

    public function callRequireAdminUser(): Usuario
    {
        return $this->requireAdminUser();
    }

    public function callOk(array $payload = [], int $status = 200): Response
    {
        return $this->ok($payload, $status);
    }

    public function callFail(string $message, int $status = 400, mixed $extra = null, ?string $code = null): Response
    {
        return $this->fail($message, $status, $extra, $code);
    }

    public function callGetJson(?string $key = null, mixed $default = null): mixed
    {
        return $this->getJson($key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function callGetRequestPayload(): array
    {
        return $this->getRequestPayload();
    }

    public function callDomainErrorResponse(
        \Throwable $e,
        string $fallbackMessage,
        int $status = 400,
        array $extra = [],
        ?string $code = null
    ): Response {
        return $this->domainErrorResponse($e, $fallbackMessage, $status, $extra, $code);
    }

    public function callNotFoundFromThrowable(\Throwable $e, string $message = 'Recurso nao encontrado.'): Response
    {
        return $this->notFoundFromThrowable($e, $message);
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    public function callWorkflowFailureResponse(
        array $result,
        string $publicMessage = 'Erro interno do servidor.',
        LogCategory $category = LogCategory::GENERAL,
        array $context = []
    ): Response {
        return $this->workflowFailureResponse($result, $publicMessage, $category, $context);
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    public function callRespondApiWorkflowResult(
        array $result,
        string $failureMessage = 'Erro interno do servidor.',
        LogCategory $category = LogCategory::GENERAL,
        array $context = [],
        bool $preserveSuccessMeta = false,
        bool $useWorkflowFailureOnFailure = true,
        bool $mapValidationFailedTo422 = false
    ): Response {
        return $this->respondApiWorkflowResult(
            $result,
            $failureMessage,
            $category,
            $context,
            $preserveSuccessMeta,
            $useWorkflowFailureOnFailure,
            $mapValidationFailedTo422
        );
    }

    public function callRespondServiceResult(
        ServiceResultDTO $result,
        mixed $successData = null,
        ?string $successMessage = null,
        ?int $successStatus = null
    ): Response {
        return $this->respondServiceResult($result, $successData, $successMessage, $successStatus);
    }
}
