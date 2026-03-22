<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Lancamento\LancamentoCreationService;

class CancelarRecorrenciaController extends BaseController
{
    private LancamentoCreationService $creationService;

    public function __construct(?LancamentoCreationService $creationService = null)
    {
        parent::__construct();
        $this->creationService = $creationService ?? new LancamentoCreationService();
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $result = $this->creationService->cancelarRecorrencia($id, $userId);

        if ($result->isError()) {
            return Response::errorResponse($result->message, $result->httpCode);
        }

        return Response::successResponse($result->data, $result->message);
    }
}
