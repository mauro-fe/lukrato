<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\DTO\ServiceResultDTO;
use Application\Services\Lancamento\LancamentoCreationService;

class StoreController extends BaseController
{
    private LancamentoCreationService $creationService;

    public function __construct(?LancamentoCreationService $creationService = null)
    {
        parent::__construct();
        $this->creationService = $creationService ?? new LancamentoCreationService();
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $result = $this->creationService->createFromPayload($userId, $this->getRequestPayload());
        return $this->buildResponse($result);
    }

    private function buildResponse(ServiceResultDTO $result): Response
    {
        if ($result->isValidationError()) {
            return Response::validationErrorResponse($result->data['errors']);
        }

        if ($result->isError()) {
            return Response::errorResponse($result->message, $result->httpCode);
        }

        return Response::successResponse($result->data, $result->message, 201);
    }
}
