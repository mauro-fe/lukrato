<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;

class DeleteLancamentoUseCase
{
    private readonly LancamentoRepository $lancamentoRepo;
    private readonly LancamentoDeletionService $deletionService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoDeletionService $deletionService = null
    ) {
        $this->lancamentoRepo = ApplicationContainer::resolveOrNew($lancamentoRepo, LancamentoRepository::class);
        $this->deletionService = ApplicationContainer::resolveOrNew($deletionService, LancamentoDeletionService::class);
    }

    public function execute(int $userId, int $id, string $scope = 'single'): ServiceResultDTO
    {
        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            return ServiceResultDTO::fail('Lancamento nao encontrado', 404);
        }

        $result = $this->deletionService->delete($lancamento, $userId, $scope);

        return ServiceResultDTO::ok('Lancamento excluido', $result);
    }
}
