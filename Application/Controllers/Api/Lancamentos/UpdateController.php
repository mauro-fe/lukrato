<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Metas\MetaProgressService;
use Application\Services\Lancamento\LancamentoUpdateService;
use Application\UseCases\Lancamentos\UpdateTransferenciaUseCase;

class UpdateController extends ApiController
{
    private LancamentoRepository $lancamentoRepo;
    private LancamentoUpdateService $updateService;
    private UpdateTransferenciaUseCase $updateTransferenciaUseCase;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoUpdateService $updateService = null,
        ?ContaRepository $contaRepo = null,
        ?MetaProgressService $metaProgressService = null,
        ?UpdateTransferenciaUseCase $updateTransferenciaUseCase = null
    ) {
        parent::__construct();

        $hasExplicitUpdateServiceDependencies = $lancamentoRepo !== null || $metaProgressService !== null;
        $hasExplicitTransferDependencies = $lancamentoRepo !== null || $contaRepo !== null || $metaProgressService !== null;
        $resolvedMetaProgressService = $metaProgressService !== null
            ? $this->resolveOrCreate($metaProgressService, MetaProgressService::class)
            : null;

        $this->lancamentoRepo = $this->resolveOrCreate($lancamentoRepo, LancamentoRepository::class);
        $this->updateService = $updateService !== null
            ? $updateService
            : ($hasExplicitUpdateServiceDependencies
                ? new LancamentoUpdateService($this->lancamentoRepo, null, $resolvedMetaProgressService)
                : $this->resolveOrCreate(
                    null,
                    LancamentoUpdateService::class,
                    fn(): LancamentoUpdateService => new LancamentoUpdateService($this->lancamentoRepo)
                ));

        if ($updateTransferenciaUseCase !== null) {
            $this->updateTransferenciaUseCase = $updateTransferenciaUseCase;

            return;
        }

        if ($hasExplicitTransferDependencies) {
            $this->updateTransferenciaUseCase = new UpdateTransferenciaUseCase(
                $this->lancamentoRepo,
                $this->resolveOrCreate($contaRepo, ContaRepository::class),
                $resolvedMetaProgressService ?? $this->resolveOrCreate(null, MetaProgressService::class)
            );

            return;
        }

        $this->updateTransferenciaUseCase = $this->resolveOrCreate(
            null,
            UpdateTransferenciaUseCase::class,
            fn(): UpdateTransferenciaUseCase => new UpdateTransferenciaUseCase($this->lancamentoRepo)
        );
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            return Response::errorResponse('Lancamento nao encontrado', 404);
        }

        $payload = $this->getRequestPayload();

        $result = (bool) ($lancamento->eh_transferencia ?? 0)
            ? $this->updateTransferenciaUseCase->execute($userId, $lancamento, $payload)
            : $this->updateService->updateFromPayload($userId, $lancamento, $payload);

        return $this->respondServiceResult(
            $result,
            successData: $result->data['lancamento'] ?? null
        );
    }
}
