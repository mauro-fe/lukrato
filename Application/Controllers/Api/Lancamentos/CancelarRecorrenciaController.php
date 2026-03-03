<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Lancamento\LancamentoCreationService;

class CancelarRecorrenciaController extends BaseController
{
    private LancamentoCreationService $creationService;

    public function __construct()
    {
        parent::__construct();
        $this->creationService = new LancamentoCreationService();
    }

    public function __invoke(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $result = $this->creationService->cancelarRecorrencia($id, $userId);

        if ($result->isError()) {
            Response::error($result->message, $result->httpCode);
            return;
        }

        Response::success($result->data, $result->message);
    }
}
