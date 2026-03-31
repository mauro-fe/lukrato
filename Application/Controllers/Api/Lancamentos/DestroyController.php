<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;
use Application\UseCases\Lancamentos\BulkDeleteLancamentosUseCase;
use Application\UseCases\Lancamentos\DeleteLancamentoUseCase;

class DestroyController extends ApiController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoDeletionService $deletionService;
    private BulkDeleteLancamentosUseCase $bulkDeleteUseCase;
    private DeleteLancamentoUseCase $deleteUseCase;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoDeletionService $deletionService = null,
        ?BulkDeleteLancamentosUseCase $bulkDeleteUseCase = null,
        ?DeleteLancamentoUseCase $deleteUseCase = null
    ) {
        parent::__construct();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->deletionService = $deletionService ?? new LancamentoDeletionService();
        $this->bulkDeleteUseCase = $bulkDeleteUseCase
            ?? new BulkDeleteLancamentosUseCase($this->lancamentoRepo, $this->deletionService);
        $this->deleteUseCase = $deleteUseCase
            ?? new DeleteLancamentoUseCase($this->lancamentoRepo, $this->deletionService);
    }

    public function __invoke(int $id): Response
    {
        $uid = $this->requireApiUserIdOrFail();
        $scope = $this->getStringQuery('scope', 'single');
        $result = $this->deleteUseCase->execute($uid, $id, $scope);

        return $this->respondServiceResult($result);
    }

    public function bulkDelete(): Response
    {
        $uid = $this->requireApiUserIdOrFail();

        $payload = $this->getRequestPayload();
        $result = $this->bulkDeleteUseCase->execute($uid, $payload['ids'] ?? []);

        return $this->respondServiceResult($result);
    }
}
