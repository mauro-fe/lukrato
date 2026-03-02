<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;

class DestroyController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoDeletionService $deletionService;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->deletionService = new LancamentoDeletionService();
    }

    public function __invoke(int $id): void
    {
        $uid = Auth::id();
        if (!$uid) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $uid);
        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        $scope  = $_GET['scope'] ?? 'single';
        $result = $this->deletionService->delete($lancamento, $uid, $scope);

        Response::success($result);
    }
}
