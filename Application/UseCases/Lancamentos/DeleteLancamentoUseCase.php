<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\DTO\ServiceResultDTO;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;

class DeleteLancamentoUseCase
{
    public function __construct(
        private readonly LancamentoRepository $lancamentoRepo = new LancamentoRepository(),
        private readonly LancamentoDeletionService $deletionService = new LancamentoDeletionService()
    ) {
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
