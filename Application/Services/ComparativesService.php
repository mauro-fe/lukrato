<?php

namespace Application\Services;

use Application\Models\Lancamento;
use Carbon\Carbon;

class ComparativesService
{
    private int $userId;
    private Carbon $currentStart;
    private Carbon $currentEnd;
    private Carbon $previousMonthStart;
    private Carbon $previousMonthEnd;
    private Carbon $currentYearStart;
    private Carbon $currentYearEnd;
    private Carbon $previousYearStart;
    private Carbon $previousYearEnd;

    private ?object $currentMonthData = null;
    private ?object $previousMonthData = null;
    private ?object $currentYearData = null;
    private ?object $previousYearData = null;

    /**
     * Gera todos os dados comparativos para o usuário/período
     */
    public function generate(int $userId, int $year, int $month): array
    {
        $this->userId = $userId;
        $this->initPeriods($year, $month);
        $this->loadBaseData();

        return [
            'monthly'         => $this->buildMonthlyComparison(),
            'yearly'          => $this->buildYearlyComparison(),
            'categories'      => $this->buildCategoryComparison(),
            'evolucao'        => $this->buildEvolution(),
            'mediaDiaria'     => $this->buildDailyAverage(),
            'taxaEconomia'    => $this->buildSavingsRate(),
            'formasPagamento' => $this->buildPaymentMethods(),
        ];
    }

    // ─── Inicialização ─────────────────────────────────────

    private function initPeriods(int $year, int $month): void
    {
        $this->currentStart      = Carbon::create($year, $month, 1)->startOfMonth();
        $this->currentEnd        = Carbon::create($year, $month, 1)->endOfMonth();
        $this->previousMonthStart = (clone $this->currentStart)->subMonth()->startOfMonth();
        $this->previousMonthEnd   = (clone $this->currentStart)->subMonth()->endOfMonth();
        $this->currentYearStart  = Carbon::create($year, 1, 1)->startOfDay();
        $this->currentYearEnd    = Carbon::create($year, 12, 31)->endOfDay();
        $this->previousYearStart = Carbon::create($year - 1, 1, 1)->startOfDay();
        $this->previousYearEnd   = Carbon::create($year - 1, 12, 31)->endOfDay();
    }

    private function loadBaseData(): void
    {
        $this->currentMonthData  = $this->queryPeriodTotals($this->currentStart, $this->currentEnd);
        $this->previousMonthData = $this->queryPeriodTotals($this->previousMonthStart, $this->previousMonthEnd);
        $this->currentYearData   = $this->queryPeriodTotals($this->currentYearStart, $this->currentYearEnd);
        $this->previousYearData  = $this->queryPeriodTotals($this->previousYearStart, $this->previousYearEnd);
    }

    private function queryPeriodTotals(Carbon $start, Carbon $end): object
    {
        return Lancamento::where('user_id', $this->userId)
            ->whereBetween('data', [$start->toDateString(), $end->toDateString()])
            ->where('eh_transferencia', 0)
            ->where(function ($q) {
                $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
            })
            ->selectRaw('
                SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
            ')
            ->first();
    }

    // ─── Comparativos ──────────────────────────────────────

    /** Comparação mensal (mês atual vs anterior) */
    private function buildMonthlyComparison(): array
    {
        $cm = $this->currentMonthData;
        $pm = $this->previousMonthData;

        return [
            'current' => [
                'receitas' => (float)($cm->receitas ?? 0),
                'despesas' => (float)($cm->despesas ?? 0),
                'saldo'    => (float)(($cm->receitas ?? 0) - ($cm->despesas ?? 0)),
            ],
            'previous' => [
                'receitas' => (float)($pm->receitas ?? 0),
                'despesas' => (float)($pm->despesas ?? 0),
                'saldo'    => (float)(($pm->receitas ?? 0) - ($pm->despesas ?? 0)),
            ],
            'variation' => [
                'receitas' => $this->calculateVariation($pm->receitas ?? 0, $cm->receitas ?? 0),
                'despesas' => $this->calculateVariation($pm->despesas ?? 0, $cm->despesas ?? 0),
                'saldo'    => $this->calculateVariation(
                    ($pm->receitas ?? 0) - ($pm->despesas ?? 0),
                    ($cm->receitas ?? 0) - ($cm->despesas ?? 0)
                ),
            ],
        ];
    }

    /** Comparação anual (ano atual vs anterior) */
    private function buildYearlyComparison(): array
    {
        $cy = $this->currentYearData;
        $py = $this->previousYearData;

        return [
            'current' => [
                'receitas' => (float)($cy->receitas ?? 0),
                'despesas' => (float)($cy->despesas ?? 0),
                'saldo'    => (float)(($cy->receitas ?? 0) - ($cy->despesas ?? 0)),
            ],
            'previous' => [
                'receitas' => (float)($py->receitas ?? 0),
                'despesas' => (float)($py->despesas ?? 0),
                'saldo'    => (float)(($py->receitas ?? 0) - ($py->despesas ?? 0)),
            ],
            'variation' => [
                'receitas' => $this->calculateVariation($py->receitas ?? 0, $cy->receitas ?? 0),
                'despesas' => $this->calculateVariation($py->despesas ?? 0, $cy->despesas ?? 0),
                'saldo'    => $this->calculateVariation(
                    ($py->receitas ?? 0) - ($py->despesas ?? 0),
                    ($cy->receitas ?? 0) - ($cy->despesas ?? 0)
                ),
            ],
        ];
    }

    /** Top 5 categorias: mês atual vs anterior */
    private function buildCategoryComparison(): array
    {
        $topCategorias = Lancamento::where('lancamentos.user_id', $this->userId)
            ->where('lancamentos.tipo', 'despesa')
            ->where('lancamentos.eh_transferencia', 0)
            ->whereBetween('lancamentos.data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->join('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.id, categorias.nome, categorias.icone, SUM(lancamentos.valor) as total')
            ->groupBy('categorias.id', 'categorias.nome', 'categorias.icone')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $result = [];
        foreach ($topCategorias as $cat) {
            $previousTotal = Lancamento::where('user_id', $this->userId)
                ->where('tipo', 'despesa')
                ->where('categoria_id', $cat->id)
                ->whereBetween('data', [$this->previousMonthStart->toDateString(), $this->previousMonthEnd->toDateString()])
                ->sum('valor');

            $result[] = [
                'nome'     => $cat->nome,
                'icone'    => $cat->icone,
                'atual'    => round((float)$cat->total, 2),
                'anterior' => round((float)$previousTotal, 2),
                'variacao' => $this->calculateVariation($previousTotal, $cat->total),
            ];
        }

        return $result;
    }

    /** Evolução dos últimos 6 meses */
    private function buildEvolution(): array
    {
        $evolucao = [];

        for ($i = 5; $i >= 0; $i--) {
            $mesRef = (clone $this->currentStart)->subMonths($i);
            $mesEnd = (clone $mesRef)->endOfMonth();

            $data = Lancamento::where('user_id', $this->userId)
                ->whereBetween('data', [$mesRef->toDateString(), $mesEnd->toDateString()])
                ->where('eh_transferencia', 0)
                ->where(function ($q) {
                    $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
                })
                ->selectRaw('
                    SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END) as despesas
                ')
                ->first();

            $r = (float)($data->receitas ?? 0);
            $d = (float)($data->despesas ?? 0);

            $evolucao[] = [
                'label'    => $mesRef->translatedFormat('M/y'),
                'mes'      => $mesRef->format('Y-m'),
                'receitas' => round($r, 2),
                'despesas' => round($d, 2),
                'saldo'    => round($r - $d, 2),
            ];
        }

        return $evolucao;
    }

    /** Média diária: mês atual vs anterior */
    private function buildDailyAverage(): array
    {
        $diasAtual    = $this->currentEnd->day;
        $diasAnterior = $this->previousMonthEnd->day;
        $despAtual    = (float)($this->currentMonthData->despesas ?? 0);
        $despAnterior = (float)($this->previousMonthData->despesas ?? 0);

        $mediaAtual    = $diasAtual > 0 ? $despAtual / $diasAtual : 0;
        $mediaAnterior = $diasAnterior > 0 ? $despAnterior / $diasAnterior : 0;

        return [
            'atual'    => round($mediaAtual, 2),
            'anterior' => round($mediaAnterior, 2),
            'variacao' => $this->calculateVariation($mediaAnterior, $mediaAtual),
        ];
    }

    /** Taxa de economia: mês atual vs anterior */
    private function buildSavingsRate(): array
    {
        $recAtual    = (float)($this->currentMonthData->receitas ?? 0);
        $despAtual   = (float)($this->currentMonthData->despesas ?? 0);
        $recAnterior = (float)($this->previousMonthData->receitas ?? 0);
        $despAnterior = (float)($this->previousMonthData->despesas ?? 0);

        $taxaAtual    = $recAtual > 0 ? round((($recAtual - $despAtual) / $recAtual) * 100, 1) : 0;
        $taxaAnterior = $recAnterior > 0 ? round((($recAnterior - $despAnterior) / $recAnterior) * 100, 1) : 0;

        return [
            'atual'     => $taxaAtual,
            'anterior'  => $taxaAnterior,
            'diferenca' => round($taxaAtual - $taxaAnterior, 1),
        ];
    }

    /** Distribuição por forma de pagamento */
    private function buildPaymentMethods(): array
    {
        $nomeForma = [
            'pix'             => 'Pix',
            'cartao_credito'  => 'Cartão de Crédito',
            'cartao_debito'   => 'Cartão de Débito',
            'dinheiro'        => 'Dinheiro',
            'boleto'          => 'Boleto',
            'deposito'        => 'Depósito',
            'transferencia'   => 'Transferência',
            'estorno_cartao'  => 'Estorno',
        ];

        $formasCurrent  = $this->queryPaymentMethods($this->currentStart, $this->currentEnd);
        $formasPrevious = $this->queryPaymentMethods($this->previousMonthStart, $this->previousMonthEnd);

        $map = [];
        foreach ($formasCurrent as $f) {
            $key = $f->forma_pagamento;
            $map[$key] = [
                'nome'         => $nomeForma[$key] ?? ucfirst($key),
                'atual'        => round((float)$f->total, 2),
                'atual_qtd'    => (int)$f->qtd,
                'anterior'     => 0,
                'anterior_qtd' => 0,
            ];
        }

        foreach ($formasPrevious as $f) {
            $key = $f->forma_pagamento;
            if (!isset($map[$key])) {
                $map[$key] = [
                    'nome'         => $nomeForma[$key] ?? ucfirst($key),
                    'atual'        => 0,
                    'atual_qtd'    => 0,
                    'anterior'     => 0,
                    'anterior_qtd' => 0,
                ];
            }
            $map[$key]['anterior']     = round((float)$f->total, 2);
            $map[$key]['anterior_qtd'] = (int)$f->qtd;
        }

        usort($map, fn($a, $b) => $b['atual'] <=> $a['atual']);

        return array_values($map);
    }

    private function queryPaymentMethods(Carbon $start, Carbon $end)
    {
        return Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->whereBetween('data', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('forma_pagamento')
            ->where('forma_pagamento', '!=', '')
            ->selectRaw('forma_pagamento, COUNT(*) as qtd, SUM(valor) as total')
            ->groupBy('forma_pagamento')
            ->orderByDesc('total')
            ->get();
    }

    // ─── Helpers ────────────────────────────────────────────

    private function calculateVariation(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
