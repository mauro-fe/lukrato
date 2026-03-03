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

    /**
     * Exclui múltiplos lançamentos de uma vez.
     * Recebe JSON: { "ids": [1, 2, 3] }
     */
    public function bulkDelete(): void
    {
        $uid = Auth::id();
        if (!$uid) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $payload = $this->getRequestPayload();
        $ids = $payload['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            Response::error('Nenhum lançamento selecionado.', 422);
            return;
        }

        // Limitar a 100 itens por request para segurança
        if (count($ids) > 100) {
            Response::error('Máximo de 100 lançamentos por operação.', 422);
            return;
        }

        $deleted = 0;
        $errors  = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) continue;

            $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $uid);
            if (!$lancamento) {
                $errors[] = "Lançamento #{$id} não encontrado.";
                continue;
            }

            try {
                $this->deletionService->delete($lancamento, $uid, 'single');
                $deleted++;
            } catch (\Throwable $e) {
                $errors[] = "Erro ao excluir #{$id}: {$e->getMessage()}";
            }
        }

        Response::success([
            'deleted' => $deleted,
            'errors'  => $errors,
            'message' => "{$deleted} lançamento(s) excluído(s) com sucesso."
        ]);
    }
}
