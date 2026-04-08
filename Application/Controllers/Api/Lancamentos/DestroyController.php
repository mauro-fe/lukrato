<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\UseCases\Lancamentos\BulkDeleteLancamentosUseCase;
use Application\UseCases\Lancamentos\DeleteLancamentoUseCase;

class DestroyController extends ApiController
{
    private BulkDeleteLancamentosUseCase $bulkDeleteUseCase;
    private DeleteLancamentoUseCase $deleteUseCase;

    public function __construct(
        ?BulkDeleteLancamentosUseCase $bulkDeleteUseCase = null,
        ?DeleteLancamentoUseCase $deleteUseCase = null
    ) {
        parent::__construct();
        $this->bulkDeleteUseCase = $this->resolveOrCreate($bulkDeleteUseCase, BulkDeleteLancamentosUseCase::class);
        $this->deleteUseCase = $this->resolveOrCreate($deleteUseCase, DeleteLancamentoUseCase::class);
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
