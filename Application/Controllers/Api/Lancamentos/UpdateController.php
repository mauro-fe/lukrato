<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Repositories\LancamentoRepository;
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
        ?UpdateTransferenciaUseCase $updateTransferenciaUseCase = null
    ) {
        parent::__construct();

        $this->lancamentoRepo = $this->resolveOrCreate($lancamentoRepo, LancamentoRepository::class);
        $this->updateService = $this->resolveOrCreate($updateService, LancamentoUpdateService::class);
        $this->updateTransferenciaUseCase = $this->resolveOrCreate($updateTransferenciaUseCase, UpdateTransferenciaUseCase::class);
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
