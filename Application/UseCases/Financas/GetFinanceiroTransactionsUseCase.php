<?php

declare(strict_types=1);

namespace Application\UseCases\Financas;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;

class GetFinanceiroTransactionsUseCase
{
    private readonly LancamentoRepository $lancamentoRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null
    ) {
        $this->lancamentoRepo = ApplicationContainer::resolveOrNew($lancamentoRepo, LancamentoRepository::class);
    }

    public function execute(int $userId, string $from, string $to, int $limit): ServiceResultDTO
    {
        $rows = $this->lancamentoRepo->findTransactionsForPeriod($userId, $from, $to, $limit);

        $out = $rows->map(function (Lancamento $t) {
            return [
                'id' => (int) $t->id,
                'data' => (string) $t->data,
                'tipo' => (string) $t->tipo,
                'descricao' => (string) ($t->descricao ?? ''),
                'observacao' => (string) ($t->observacao ?? ''),
                'valor' => (float) $t->valor,
                'eh_transferencia' => (bool) ($t->eh_transferencia ?? 0),
                'eh_saldo_inicial' => (bool) ($t->eh_saldo_inicial ?? 0),
                'categoria' => $t->categoria
                    ? ['id' => (int) $t->categoria->id, 'nome' => (string) $t->categoria->nome]
                    : null,
            ];
        })->all();

        return new ServiceResultDTO(
            success: true,
            message: 'Success',
            data: $out,
            httpCode: 200
        );
    }
}
