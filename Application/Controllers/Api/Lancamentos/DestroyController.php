<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;
use Application\Services\Infrastructure\LogService;

class DestroyController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoDeletionService $deletionService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoDeletionService $deletionService = null
    ) {
        parent::__construct();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->deletionService = $deletionService ?? new LancamentoDeletionService();
    }

    public function __invoke(int $id): Response
    {
        $uid = $this->requireApiUserIdOrFail();

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $uid);
        if (!$lancamento) {
            return Response::errorResponse('Lancamento nao encontrado', 404);
        }

        $scope = $_GET['scope'] ?? 'single';
        $result = $this->deletionService->delete($lancamento, $uid, $scope);

        return Response::successResponse($result);
    }

    public function bulkDelete(): Response
    {
        $uid = $this->requireApiUserIdOrFail();

        $payload = $this->getRequestPayload();
        $ids = $payload['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            return Response::errorResponse('Nenhum lançamento selecionado.', 422);
        }

        if (count($ids) > 100) {
            return Response::errorResponse('Máximo de 100 lançamentos por operação.', 422);
        }

        $deleted = 0;
        $errors = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $uid);
            if (!$lancamento) {
                $errors[] = "Lançamento #{$id} não encontrado.";
                continue;
            }

            try {
                $this->deletionService->delete($lancamento, $uid, 'single');
                $deleted++;
            } catch (\Throwable $e) {
                LogService::captureException($e, \Application\Enums\LogCategory::GENERAL, [
                    'action' => 'bulk_delete_lancamentos',
                    'lancamento_id' => $id,
                    'user_id' => $uid,
                ]);
                $errors[] = "Erro ao excluir #{$id}.";
            }
        }

        return Response::successResponse([
            'deleted' => $deleted,
            'errors' => $errors,
            'message' => "{$deleted} lançamento(s) excluído(s) com sucesso.",
        ]);
    }
}
