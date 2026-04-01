<?php

declare(strict_types=1);

namespace Application\Services\Report\Insights\Concerns;

use Application\Models\Lancamento;
use Carbon\Carbon;

trait HandlesInsightsInitialization
{
    private function initPeriods(int $year, int $month): void
    {
        $this->currentStart = Carbon::create($year, $month, 1)->startOfMonth();
        $this->currentEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $this->previousStart = (clone $this->currentStart)->subMonth()->startOfMonth();
        $this->previousEnd = (clone $this->currentStart)->subMonth()->endOfMonth();
    }

    private function loadBaseData(): void
    {
        $currentData = $this->queryPeriodTotals($this->currentStart, $this->currentEnd);
        $previousData = $this->queryPeriodTotals($this->previousStart, $this->previousEnd);

        $this->currentReceitas = (float) ($currentData->receitas ?? 0);
        $this->currentDespesas = (float) ($currentData->despesas ?? 0);
        $this->previousReceitas = (float) ($previousData->receitas ?? 0);
        $this->previousDespesas = (float) ($previousData->despesas ?? 0);
    }

    private function queryPeriodTotals(Carbon $start, Carbon $end): object
    {
        return Lancamento::where('user_id', $this->userId)
            ->whereBetween('data', [$start->toDateString(), $end->toDateString()])
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->selectRaw('
                SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                SUM(CASE
                    WHEN tipo = "despesa"
                        AND (origem_tipo IS NULL OR origem_tipo != "pagamento_fatura")
                    THEN valor
                    ELSE 0
                END) as despesas
            ')
            ->first();
    }
}
