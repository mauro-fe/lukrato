<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\DTO\ServiceResultDTO;

class StoreController extends BaseController
{
    private LancamentoCreationService $creationService;

    public function __construct()
    {
        parent::__construct();
        $this->creationService = new LancamentoCreationService();
    }

    public function __invoke(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $result = $this->creationService->createFromPayload($userId, $this->getRequestPayload());
        $this->sendResponse($result);
    }

    private function sendResponse(ServiceResultDTO $result): void
    {
        if ($result->isValidationError()) {
            Response::validationError($result->data['errors']);
            return;
        }
        if ($result->isError()) {
            Response::error($result->message, $result->httpCode);
            return;
        }
        Response::success($result->data, $result->message, 201);
    }
}