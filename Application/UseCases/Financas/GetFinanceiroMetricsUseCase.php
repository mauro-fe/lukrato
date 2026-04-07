<?php

declare(strict_types=1);

namespace Application\UseCases\Financas;

use Application\DTO\ServiceResultDTO;
use Application\Repositories\LancamentoRepository;

class GetFinanceiroMetricsUseCase
{
    private readonly LancamentoRepository $lancamentoRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null
    ) {
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
    }

    public function execute(int $userId, string $start, string $end, string $viewType): ServiceResultDTO
    {
        $resolvedView = $viewType === 'competencia' ? 'competencia' : 'caixa';

        if ($resolvedView === 'competencia') {
            $receitas = $this->lancamentoRepo->sumReceitasCompetencia($userId, $start, $end);
            $despesas = $this->lancamentoRepo->sumDespesasCompetencia($userId, $start, $end);
            $despesasBrutas = $this->lancamentoRepo->sumDespesasBrutasCompetencia($userId, $start, $end);
            $usoMetas = $this->lancamentoRepo->sumUsoMetasDespesaCompetencia($userId, $start, $end);
        } else {
            $receitas = $this->lancamentoRepo->sumReceitasCaixa($userId, $start, $end);
            $despesas = $this->lancamentoRepo->sumDespesasCaixa($userId, $start, $end);
            $despesasBrutas = $this->lancamentoRepo->sumDespesasBrutasCaixa($userId, $start, $end);
            $usoMetas = $this->lancamentoRepo->sumUsoMetasDespesaCaixa($userId, $start, $end);
        }

        $saldoAcumulado = $this->lancamentoRepo->sumSaldoAcumuladoAte($userId, $end);
        $resultado = $receitas - $despesas;

        return new ServiceResultDTO(
            success: true,
            message: 'Success',
            data: [
                'saldo' => $saldoAcumulado,
                'receitas' => $receitas,
                'despesas' => $despesas,
                'despesas_brutas' => $despesasBrutas,
                'uso_metas' => $usoMetas,
                'resultado' => $resultado,
                'saldoAcumulado' => $saldoAcumulado,
                'view' => $resolvedView,
            ],
            httpCode: 200
        );
    }
}
