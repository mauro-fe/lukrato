<?php

declare(strict_types=1);

namespace Application\Services\AI\Analysis;

use Application\Models\Lancamento;
use Application\Models\Categoria;
use Carbon\Carbon;

/**
 * Pré-processa dados financeiros para análise de IA.
 * Enviar agregados ao LLM em vez de registros individuais — reduz ~80% dos tokens.
 */
class FinancialAnalysisPreprocessor
{
    /**
     * Prepara dados agregados para análise financeira.
     *
     * @param int|null $userId  ID do usuário (null = global)
     * @param string   $period  Período descritivo ("último mês", "março/2026", etc.)
     * @return array Dados agregados prontos para o LLM
     */
    public function prepare(?int $userId, string $period = 'último mês'): array
    {
        $dates = $this->parsePeriod($period);
        $startDate = $dates['start'];
        $endDate   = $dates['end'];

        // Período anterior para comparação
        $diffDays = $startDate->diffInDays($endDate);
        $prevStart = $startDate->copy()->subDays($diffDays + 1);
        $prevEnd   = $startDate->copy()->subDay();

        $current  = $this->getAggregates($userId, $startDate, $endDate);
        $previous = $this->getAggregates($userId, $prevStart, $prevEnd);

        if (empty($current['por_categoria'])) {
            return [];
        }

        // Calcular variações
        $variacao = [];
        if ($previous['total_despesas'] > 0) {
            $variacao['despesas_pct'] = round(
                (($current['total_despesas'] - $previous['total_despesas']) / $previous['total_despesas']) * 100,
                1
            );
        }
        if ($previous['total_receitas'] > 0) {
            $variacao['receitas_pct'] = round(
                (($current['total_receitas'] - $previous['total_receitas']) / $previous['total_receitas']) * 100,
                1
            );
        }

        return [
            'periodo'    => $period,
            'data_inicio' => $startDate->format('d/m/Y'),
            'data_fim'    => $endDate->format('d/m/Y'),

            // Resumo do período atual
            'receitas_total'       => $current['total_receitas'],
            'despesas_total'       => $current['total_despesas'],
            'saldo_periodo'        => $current['total_receitas'] - $current['total_despesas'],
            'total_lancamentos'    => $current['count'],
            'ticket_medio_despesa' => $current['count_despesas'] > 0
                ? round($current['total_despesas'] / $current['count_despesas'], 2)
                : 0,
            'taxa_economia'        => $current['total_receitas'] > 0
                ? round((($current['total_receitas'] - $current['total_despesas']) / $current['total_receitas']) * 100, 1)
                : 0,

            // Top categorias de gasto (máx 8)
            'top_categorias_gasto' => array_slice($current['por_categoria'], 0, 8),

            // Formas de pagamento
            'por_forma_pagamento'  => $current['por_forma'],

            // Status de pagamento
            'pagos'     => $current['pagos'],
            'pendentes' => $current['pendentes'],
            'vencidos'  => $current['vencidos'],

            // Comparação com período anterior
            'variacao_periodo_anterior' => $variacao,

            // Recorrências ativas
            'total_recorrencias' => $current['recorrencias'],
        ];
    }

    /**
     * Coleta agregados financeiros para um período.
     */
    private function getAggregates(?int $userId, Carbon $start, Carbon $end): array
    {
        $baseQuery = Lancamento::query()
            ->whereNull('cancelado_em')
            ->whereBetween('data', [$start->toDateString(), $end->toDateString()]);

        if ($userId !== null) {
            $baseQuery->where('user_id', $userId);
        }

        $totalReceitas = (float) (clone $baseQuery)->where('tipo', 'receita')->sum('valor');
        $totalDespesas = (float) (clone $baseQuery)->where('tipo', 'despesa')->sum('valor');
        $count         = (int) (clone $baseQuery)->count();
        $countDespesas = (int) (clone $baseQuery)->where('tipo', 'despesa')->count();

        // Top categorias de gasto
        $porCategoria = (clone $baseQuery)
            ->where('tipo', 'despesa')
            ->whereNotNull('categoria_id')
            ->select('categoria_id')
            ->selectRaw('SUM(valor) as total')
            ->selectRaw('COUNT(*) as qtd')
            ->groupBy('categoria_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $cat = Categoria::find($row->categoria_id);
                return [
                    'categoria' => $cat?->nome ?? 'Sem categoria',
                    'total'     => round((float) $row->total, 2),
                    'qtd'       => (int) $row->qtd,
                ];
            })
            ->toArray();

        // Por forma de pagamento
        $porForma = (clone $baseQuery)
            ->whereNotNull('forma_pagamento')
            ->select('forma_pagamento')
            ->selectRaw('COUNT(*) as qtd')
            ->selectRaw('SUM(valor) as total')
            ->groupBy('forma_pagamento')
            ->get()
            ->mapWithKeys(fn($r) => [$r->forma_pagamento => [
                'qtd'   => (int) $r->qtd,
                'total' => round((float) $r->total, 2),
            ]])
            ->toArray();

        // Status de pagamento
        $pagos     = (int) (clone $baseQuery)->where('pago', true)->count();
        $pendentes = (int) (clone $baseQuery)->where('pago', false)->where('data', '>=', now()->toDateString())->count();
        $vencidos  = (int) (clone $baseQuery)->where('pago', false)->where('data', '<', now()->toDateString())->count();

        // Recorrências ativas
        $recorrencias = (int) Lancamento::query()
            ->where('recorrente', true)
            ->whereNull('cancelado_em')
            ->whereNull('recorrencia_pai_id')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->count();

        return [
            'total_receitas'  => $totalReceitas,
            'total_despesas'  => $totalDespesas,
            'count'           => $count,
            'count_despesas'  => $countDespesas,
            'por_categoria'   => $porCategoria,
            'por_forma'       => $porForma,
            'pagos'           => $pagos,
            'pendentes'       => $pendentes,
            'vencidos'        => $vencidos,
            'recorrencias'    => $recorrencias,
        ];
    }

    /**
     * Parseia o período descritivo em datas.
     */
    private function parsePeriod(string $period): array
    {
        $normalized = mb_strtolower(trim($period));

        // "último mês" / "mês passado"
        if (preg_match('/último\s*mês|mês\s*passado|mes\s*passado/', $normalized)) {
            return [
                'start' => now()->subMonth()->startOfMonth(),
                'end'   => now()->subMonth()->endOfMonth(),
            ];
        }

        // "este mês" / "mês atual"
        if (preg_match('/este\s*mês|mês\s*atual|mes\s*atual/', $normalized)) {
            return [
                'start' => now()->startOfMonth(),
                'end'   => now(),
            ];
        }

        // "março/2026" / "03/2026"
        if (preg_match('/(\d{1,2})\s*[\/\-]\s*(\d{4})/', $normalized, $m)) {
            $date = Carbon::createFromDate((int) $m[2], (int) $m[1], 1);
            return [
                'start' => $date->startOfMonth(),
                'end'   => $date->endOfMonth(),
            ];
        }

        // Nomes de meses em PT
        $meses = [
            'janeiro' => 1, 'fevereiro' => 2, 'março' => 3, 'marco' => 3,
            'abril' => 4, 'maio' => 5, 'junho' => 6,
            'julho' => 7, 'agosto' => 8, 'setembro' => 9,
            'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
        ];

        foreach ($meses as $nome => $num) {
            if (str_contains($normalized, $nome)) {
                $year = now()->year;
                if (preg_match('/(\d{4})/', $normalized, $ym)) {
                    $year = (int) $ym[1];
                }
                $date = Carbon::createFromDate($year, $num, 1);
                return [
                    'start' => $date->startOfMonth(),
                    'end'   => $date->endOfMonth(),
                ];
            }
        }

        // Default: mês atual
        return [
            'start' => now()->startOfMonth(),
            'end'   => now(),
        ];
    }
}
