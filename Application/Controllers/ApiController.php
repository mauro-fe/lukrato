<?php

declare(strict_types=1);

namespace Application\Controllers;

use Application\Controllers\Concerns\HandlesApiResponses;
use Application\Core\Response;
use Application\DTO\ServiceResultDTO;
use Application\Enums\LogCategory;
use Application\Models\Usuario;

abstract class ApiController extends BaseController
{
    use HandlesApiResponses;

    /**
     * API controllers should return auth errors instead of redirecting to login.
     */
    protected function requireAuth(): void
    {
        $this->requireAuthApiOrFail();
    }

    protected function requireUserId(): int
    {
        return $this->requireApiUserIdOrFail();
    }

    protected function requireUser(): Usuario
    {
        return $this->requireApiUserOrFail();
    }

    protected function requireAdminUser(): Usuario
    {
        return $this->requireApiAdminUserOrFail();
    }

    /**
     * Explicit passthrough helps static analyzers resolve inherited trait methods.
     *
     * @return array<string, mixed>
     */
    protected function getRequestPayload(): array
    {
        return parent::getRequestPayload();
    }

    protected function getJson(?string $key = null, mixed $default = null): mixed
    {
        return parent::getJson($key, $default);
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    protected function respondApiWorkflowResult(
        array $result,
        string $failureMessage = 'Erro interno do servidor.',
        LogCategory $category = LogCategory::GENERAL,
        array $context = [],
        bool $preserveSuccessMeta = false,
        bool $useWorkflowFailureOnFailure = true,
        bool $mapValidationFailedTo422 = false
    ): Response {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? $failureMessage);
            $status = (int) ($result['status'] ?? 400);
            $errors = $result['errors'] ?? null;

            if ($mapValidationFailedTo422 && $message === 'Validation failed') {
                return Response::validationErrorResponse(is_array($errors) ? $errors : []);
            }

            if ($useWorkflowFailureOnFailure) {
                return $this->workflowFailureResponse($result, $failureMessage, $category, $context);
            }

            return Response::errorResponse($message, $status, $errors);
        }

        if ($preserveSuccessMeta) {
            return Response::successResponse(
                $result['data'] ?? null,
                (string) ($result['message'] ?? 'Success'),
                (int) ($result['status'] ?? 200)
            );
        }

        return Response::successResponse($result['data'] ?? null);
    }

    protected function respondServiceResult(
        ServiceResultDTO $result,
        mixed $successData = null,
        ?string $successMessage = null,
        ?int $successStatus = null
    ): Response {
        if ($result->isValidationError()) {
            $errors = $result->data['errors'] ?? [];
            return Response::validationErrorResponse(is_array($errors) ? $errors : []);
        }

        if ($result->isError()) {
            return Response::errorResponse($result->message, $result->httpCode);
        }

        return Response::successResponse(
            $successData ?? $result->data,
            $successMessage ?? 'Success',
            $successStatus ?? 200
        );
    }
}
