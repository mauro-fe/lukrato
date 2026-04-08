<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Enums\LogCategory;
use Application\Repositories\LancamentoRepository;
use Application\Services\Infrastructure\LogService;
use Application\Services\Lancamento\LancamentoDeletionService;

class BulkDeleteLancamentosUseCase
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

    /**
     * @param mixed $ids
     */
    public function execute(int $userId, mixed $ids): ServiceResultDTO
    {
        if (!is_array($ids) || empty($ids)) {
            return ServiceResultDTO::fail('Nenhum lancamento selecionado.', 422);
        }

        if (count($ids) > 100) {
            return ServiceResultDTO::fail('Maximo de 100 lancamentos por operacao.', 422);
        }

        $deleted = 0;
        $errors = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
            if (!$lancamento) {
                $errors[] = "Lancamento #{$id} nao encontrado.";
                continue;
            }

            try {
                $this->deletionService->delete($lancamento, $userId, 'single');
                $deleted++;
            } catch (\Throwable $e) {
                LogService::captureException($e, LogCategory::GENERAL, [
                    'action' => 'bulk_delete_lancamentos',
                    'lancamento_id' => $id,
                    'user_id' => $userId,
                ]);

                $errors[] = "Erro ao excluir #{$id}.";
            }
        }

        return ServiceResultDTO::ok('Bulk delete concluido', [
            'deleted' => $deleted,
            'errors' => $errors,
            'message' => "{$deleted} lancamento(s) excluido(s) com sucesso.",
        ]);
    }
}
