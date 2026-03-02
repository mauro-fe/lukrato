<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoUpdateService;

class UpdateController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoUpdateService $updateService;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->updateService = new LancamentoUpdateService();
    }

    public function __invoke(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ((bool) ($lancamento->eh_transferencia ?? 0) === true) {
            Response::error('Nao e possivel editar uma transferencia. Crie uma nova.', 422);
            return;
        }

        $result = $this->updateService->updateFromPayload($userId, $lancamento, $this->getRequestPayload());

        if ($result->isValidationError()) {
            Response::validationError($result->data['errors']);
            return;
        }

        if ($result->isError()) {
            Response::error($result->message, $result->httpCode);
            return;
        }

        Response::success($result->data['lancamento']);
    }
}