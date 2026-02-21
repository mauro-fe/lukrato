<?php

namespace Application\Services;

use Application\DTO\InsightItemDTO;
use Application\Enums\InsightType;
use Application\Models\Lancamento;
use Application\Models\CartaoCredito;
use Application\Models\OrcamentoCategoria;
use Application\Models\Meta;
use Application\Models\Parcelamento;
use Application\Models\Agendamento;
use Carbon\Carbon;

class InsightsService
{
    private int $userId;
    private Carbon $currentStart;
    private Carbon $currentEnd;
    private Carbon $previousStart;
    private Carbon $previousEnd;
    private float $currentReceitas = 0;
    private float $currentDespesas = 0;
    private float $previousReceitas = 0;
    private float $previousDespesas = 0;

    /**
     * Gera todos os insights financeiros para o usuário/período
     *
     * @return InsightItemDTO[]
     */
    public function generate(int $userId, int $year, int $month): array
    {
        $this->userId = $userId;
        $this->initPeriods($year, $month);
        $this->loadBaseData();

        /** @var InsightItemDTO[] $insights */
        $insights = [];

        $this->addDespesasComparison($insights);
        $this->addSaldoAnalysis($insights);
        $this->addTopCategory($insights);
        $this->addCardLimitAlert($insights);
        $this->addReceitasVariation($insights);
        $this->addBudgetAlerts($insights, $year, $month);
        $this->addGoalProgress($insights);
        $this->addLargestExpense($insights);
        $this->addDailyAverageProjection($insights, $year, $month);
        $this->addPaymentMethodPreference($insights);
        $this->addActiveInstallments($insights);
        $this->addScheduledPayments($insights);
        $this->addSpendingConcentration($insights);
        $this->addWeekendSpending($insights);
        $this->addTransactionCountVariation($insights);

        if (empty($insights)) {
            $insights[] = new InsightItemDTO(
                type: InsightType::SUCCESS,
                icon: 'check-circle',
                title: 'Tudo em ordem!',
                message: 'Suas finanças estão equilibradas neste período.',
            );
        }

        return $insights;
    }

    /**
     * Serializa os insights para resposta JSON
     *
     * @param InsightItemDTO[] $insights
     */
    public static function toArrayList(array $insights): array
    {
        return array_map(fn(InsightItemDTO $i) => $i->toArray(), $insights);
    }

    // ─── Inicialização ─────────────────────────────────────

    private function initPeriods(int $year, int $month): void
    {
        $this->currentStart = Carbon::create($year, $month, 1)->startOfMonth();
        $this->currentEnd   = Carbon::create($year, $month, 1)->endOfMonth();
        $this->previousStart = (clone $this->currentStart)->subMonth()->startOfMonth();
        $this->previousEnd   = (clone $this->currentStart)->subMonth()->endOfMonth();
    }

    private function loadBaseData(): void
    {
        $currentData = $this->queryPeriodTotals($this->currentStart, $this->currentEnd);
        $previousData = $this->queryPeriodTotals($this->previousStart, $this->previousEnd);

        $this->currentReceitas  = (float)($currentData->receitas ?? 0);
        $this->currentDespesas  = (float)($currentData->despesas ?? 0);
        $this->previousReceitas = (float)($previousData->receitas ?? 0);
        $this->previousDespesas = (float)($previousData->despesas ?? 0);
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

    // ─── Insights individuais ──────────────────────────────

    /** Insight 1: Comparação de despesas mês a mês */
    private function addDespesasComparison(array &$insights): void
    {
        if ($this->previousDespesas <= 0) return;

        $variation = (($this->currentDespesas - $this->previousDespesas) / $this->previousDespesas) * 100;

        if (abs($variation) > 10) {
            $increased = $variation > 0;
            $insights[] = new InsightItemDTO(
                type: $increased ? InsightType::WARNING : InsightType::SUCCESS,
                icon: $increased ? 'arrow-trend-up' : 'arrow-trend-down',
                title: $increased ? 'Despesas aumentaram' : 'Despesas reduziram',
                message: sprintf(
                    'Suas despesas %s %.1f%% em relação ao mês anterior',
                    $increased ? 'aumentaram' : 'reduziram',
                    abs($variation)
                ),
                percentage: abs($variation),
            );
        }
    }

    /** Insight 2: Análise de saldo */
    private function addSaldoAnalysis(array &$insights): void
    {
        $saldo = $this->currentReceitas - $this->currentDespesas;

        if ($saldo < 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::DANGER,
                icon: 'exclamation-triangle',
                title: 'Saldo negativo',
                message: 'Suas despesas estão maiores que suas receitas este mês. Considere revisar seus gastos.',
                value: abs($saldo),
            );
        } elseif ($this->currentReceitas > 0) {
            $taxaEconomia = ($saldo / $this->currentReceitas) * 100;
            if ($taxaEconomia > 20) {
                $insights[] = new InsightItemDTO(
                    type: InsightType::SUCCESS,
                    icon: 'piggy-bank',
                    title: 'Ótima economia!',
                    message: sprintf('Você está economizando %.1f%% de sua renda este mês!', $taxaEconomia),
                    percentage: $taxaEconomia,
                );
            }
        }
    }

    /** Insight 3: Categoria com maior gasto */
    private function addTopCategory(array &$insights): void
    {
        $topCategoria = Lancamento::where('lancamentos.user_id', $this->userId)
            ->where('lancamentos.tipo', 'despesa')
            ->whereBetween('lancamentos.data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->join('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nome, SUM(lancamentos.valor) as total')
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('total')
            ->first();

        if (!$topCategoria || $topCategoria->total <= 0 || $this->currentDespesas <= 0) return;

        $percentual = ($topCategoria->total / $this->currentDespesas) * 100;
        if ($percentual > 30) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'chart-pie',
                title: 'Categoria em destaque',
                message: sprintf(
                    '%s representa %.1f%% dos seus gastos (R$ %.2f)',
                    $topCategoria->nome,
                    $percentual,
                    $topCategoria->total
                ),
                value: (float) $topCategoria->total,
                percentage: $percentual,
            );
        }
    }

    /** Insight 4: Cartão próximo do limite */
    private function addCardLimitAlert(array &$insights): void
    {
        $cartoes = CartaoCredito::where('user_id', $this->userId)
            ->whereNotNull('limite_total')
            ->where('limite_total', '>', 0)
            ->get();

        $alertCount = 0;
        foreach ($cartoes as $cartao) {
            $gasto = Lancamento::where('user_id', $this->userId)
                ->where('cartao_credito_id', $cartao->id)
                ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
                ->sum('valor');

            if ($cartao->limite_total > 0 && ($gasto / $cartao->limite_total) > 0.8) {
                $alertCount++;
            }
        }

        if ($alertCount > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::WARNING,
                icon: 'credit-card',
                title: 'Atenção ao limite do cartão',
                message: sprintf(
                    '%s %s próximo%s do limite',
                    $alertCount,
                    $alertCount > 1 ? 'cartões estão' : 'cartão está',
                    $alertCount > 1 ? 's' : ''
                ),
            );
        }
    }

    /** Insight 5: Variação de receitas */
    private function addReceitasVariation(array &$insights): void
    {
        if ($this->previousReceitas <= 0) return;

        $variation = (($this->currentReceitas - $this->previousReceitas) / $this->previousReceitas) * 100;

        if (abs($variation) > 10) {
            $increased = $variation > 0;
            $insights[] = new InsightItemDTO(
                type: $increased ? InsightType::SUCCESS : InsightType::WARNING,
                icon: $increased ? 'trending-up' : 'trending-down',
                title: $increased ? 'Receitas cresceram' : 'Receitas diminuíram',
                message: sprintf(
                    'Suas receitas %s %.1f%% em relação ao mês anterior (R$ %.2f → R$ %.2f)',
                    $increased ? 'aumentaram' : 'diminuíram',
                    abs($variation),
                    $this->previousReceitas,
                    $this->currentReceitas
                ),
                percentage: abs($variation),
            );
        }
    }

    /** Insight 6: Orçamentos estourados ou próximos */
    private function addBudgetAlerts(array &$insights, int $year, int $month): void
    {
        $orcamentos = OrcamentoCategoria::where('user_id', $this->userId)
            ->where('mes', $month)
            ->where('ano', $year)
            ->where('valor_limite', '>', 0)
            ->with('categoria')
            ->get();

        $estourados = 0;
        $proximos = 0;
        $nomeEstourado = '';

        foreach ($orcamentos as $orc) {
            $gasto = Lancamento::where('user_id', $this->userId)
                ->where('tipo', 'despesa')
                ->where('categoria_id', $orc->categoria_id)
                ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
                ->sum('valor');

            $pct = ($orc->valor_limite > 0) ? ($gasto / $orc->valor_limite) * 100 : 0;

            if ($pct >= 100) {
                $estourados++;
                if (empty($nomeEstourado) && $orc->categoria) {
                    $nomeEstourado = $orc->categoria->nome;
                }
            } elseif ($pct >= 80) {
                $proximos++;
            }
        }

        if ($estourados > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::DANGER,
                icon: 'shield-alert',
                title: 'Orçamento estourado',
                message: $estourados === 1
                    ? sprintf('O orçamento de "%s" foi ultrapassado este mês', $nomeEstourado)
                    : sprintf('%d categorias ultrapassaram o orçamento definido', $estourados),
            );
        } elseif ($proximos > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::WARNING,
                icon: 'gauge',
                title: 'Orçamento quase no limite',
                message: sprintf(
                    '%d %s acima de 80%% do orçamento mensal',
                    $proximos,
                    $proximos === 1 ? 'categoria está' : 'categorias estão'
                ),
            );
        }
    }

    /** Insight 7: Progresso de metas */
    private function addGoalProgress(array &$insights): void
    {
        $metas = Meta::where('user_id', $this->userId)
            ->where('status', 'ativa')
            ->get();

        foreach ($metas as $meta) {
            $progresso = $meta->valor_alvo > 0
                ? ($meta->valor_atual / $meta->valor_alvo) * 100
                : 0;

            if ($progresso >= 90 && $progresso < 100) {
                $insights[] = new InsightItemDTO(
                    type: InsightType::SUCCESS,
                    icon: 'target',
                    title: 'Meta quase alcançada!',
                    message: sprintf(
                        '"%s" está em %.0f%% — faltam apenas R$ %.2f!',
                        $meta->titulo,
                        $progresso,
                        $meta->valor_alvo - $meta->valor_atual
                    ),
                    value: (float) ($meta->valor_alvo - $meta->valor_atual),
                    percentage: $progresso,
                );
                return;
            }

            if ($meta->data_prazo) {
                $diasRestantes = Carbon::now()->diffInDays(Carbon::parse($meta->data_prazo), false);
                if ($diasRestantes > 0 && $diasRestantes <= 30 && $progresso < 70) {
                    $insights[] = new InsightItemDTO(
                        type: InsightType::WARNING,
                        icon: 'clock',
                        title: 'Meta com prazo próximo',
                        message: sprintf(
                            '"%s" vence em %d dias e está em %.0f%%. Considere aumentar os aportes.',
                            $meta->titulo,
                            $diasRestantes,
                            $progresso
                        ),
                        percentage: $progresso,
                    );
                    return;
                }
            }
        }
    }

    /** Insight 8: Maior gasto do mês */
    private function addLargestExpense(array &$insights): void
    {
        $maiorGasto = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->orderByDesc('valor')
            ->first();

        if (!$maiorGasto || $maiorGasto->valor <= 0 || $this->currentDespesas <= 0) return;

        $pct = ($maiorGasto->valor / $this->currentDespesas) * 100;
        if ($pct > 15) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'receipt',
                title: 'Maior gasto do mês',
                message: sprintf(
                    '"%s" de R$ %.2f representa %.1f%% de todas as despesas',
                    $maiorGasto->descricao ?: 'Sem descrição',
                    $maiorGasto->valor,
                    $pct
                ),
                value: (float) $maiorGasto->valor,
                percentage: $pct,
            );
        }
    }

    /** Insight 9: Média diária de gastos + projeção */
    private function addDailyAverageProjection(array &$insights, int $year, int $month): void
    {
        $hoje = Carbon::now();
        $diaAtual = ($hoje->year == $year && $hoje->month == $month)
            ? $hoje->day
            : $this->currentEnd->day;

        if ($diaAtual <= 0 || $this->currentDespesas <= 0) return;

        $mediaDiaria = $this->currentDespesas / $diaAtual;
        $diasNoMes   = $this->currentEnd->day;
        $projecao    = $mediaDiaria * $diasNoMes;

        if ($diaAtual < $diasNoMes && $hoje->year == $year && $hoje->month == $month) {
            $insights[] = new InsightItemDTO(
                type: $projecao > $this->currentReceitas ? InsightType::WARNING : InsightType::INFO,
                icon: 'calculator',
                title: 'Projeção mensal de gastos',
                message: sprintf(
                    'Média diária de R$ %.2f. Projeção para o mês: R$ %.2f%s',
                    $mediaDiaria,
                    $projecao,
                    $projecao > $this->currentReceitas ? ' — pode ultrapassar sua receita!' : ''
                ),
                value: $projecao,
            );
        }
    }

    /** Insight 10: Forma de pagamento mais usada */
    private function addPaymentMethodPreference(array &$insights): void
    {
        $formaPagamento = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->whereNotNull('forma_pagamento')
            ->where('forma_pagamento', '!=', '')
            ->selectRaw('forma_pagamento, COUNT(*) as qtd, SUM(valor) as total')
            ->groupBy('forma_pagamento')
            ->orderByDesc('total')
            ->first();

        if (!$formaPagamento || $this->currentDespesas <= 0) return;

        $nomeForma = $this->getPaymentMethodName($formaPagamento->forma_pagamento);
        $pct = ($formaPagamento->total / $this->currentDespesas) * 100;

        if ($pct > 40) {
            $icon = match ($formaPagamento->forma_pagamento) {
                'pix' => 'zap',
                'cartao_credito', 'cartao_debito' => 'credit-card',
                'dinheiro' => 'banknote',
                'boleto' => 'file-text',
                default => 'wallet',
            };

            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: $icon,
                title: 'Forma de pagamento preferida',
                message: sprintf(
                    '%.0f%% dos seus gastos foram via %s (R$ %.2f em %d transações)',
                    $pct,
                    $nomeForma,
                    $formaPagamento->total,
                    $formaPagamento->qtd
                ),
                value: (float) $formaPagamento->total,
                percentage: $pct,
            );
        }
    }

    /** Insight 11: Parcelas ativas */
    private function addActiveInstallments(array &$insights): void
    {
        $parcelamentos = Parcelamento::where('user_id', $this->userId)
            ->where('status', 'ativo')
            ->get();

        if ($parcelamentos->count() === 0) return;

        $totalMensal = 0;
        foreach ($parcelamentos as $parc) {
            if ($parc->numero_parcelas > 0) {
                $totalMensal += ($parc->valor_total / $parc->numero_parcelas);
            }
        }

        if ($totalMensal > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'layers',
                title: 'Parcelas comprometidas',
                message: sprintf(
                    'Você tem %d parcelamento%s ativo%s consumindo ~R$ %.2f/mês',
                    $parcelamentos->count(),
                    $parcelamentos->count() > 1 ? 's' : '',
                    $parcelamentos->count() > 1 ? 's' : '',
                    $totalMensal
                ),
                value: $totalMensal,
            );
        }
    }

    /** Insight 12: Agendamentos próximos */
    private function addScheduledPayments(array &$insights): void
    {
        $count = Agendamento::where('user_id', $this->userId)
            ->where('status', 'ativo')
            ->where('proxima_execucao', '>=', Carbon::now()->toDateString())
            ->where('proxima_execucao', '<=', Carbon::now()->addDays(7)->toDateString())
            ->count();

        if ($count > 0) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'calendar-clock',
                title: 'Pagamentos agendados',
                message: sprintf(
                    '%d pagamento%s agendado%s para os próximos 7 dias',
                    $count,
                    $count > 1 ? 's' : '',
                    $count > 1 ? 's' : ''
                ),
            );
        }
    }

    /** Insight 13: Concentração de gastos em poucas categorias */
    private function addSpendingConcentration(array &$insights): void
    {
        $categorias = Lancamento::where('lancamentos.user_id', $this->userId)
            ->where('lancamentos.tipo', 'despesa')
            ->where('lancamentos.eh_transferencia', 0)
            ->whereBetween('lancamentos.data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->whereNotNull('lancamentos.categoria_id')
            ->join('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nome, SUM(lancamentos.valor) as total')
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('total')
            ->get();

        if ($categorias->count() < 3) return;

        $topTwo = $categorias->take(2)->sum('total');
        $total  = $categorias->sum('total');

        if ($total <= 0) return;

        $pct = ($topTwo / $total) * 100;
        if ($pct > 70) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'pie-chart',
                title: 'Gastos concentrados',
                message: sprintf(
                    '%.0f%% dos gastos estão em apenas 2 categorias: %s e %s. Diversificar pode melhorar o controle.',
                    $pct,
                    $categorias[0]->nome,
                    $categorias[1]->nome
                ),
                percentage: $pct,
            );
        }
    }

    /** Insight 14: Gastos no fim de semana */
    private function addWeekendSpending(array &$insights): void
    {
        if ($this->currentDespesas <= 0) return;

        $gastosWeekend = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->whereRaw('DAYOFWEEK(data) IN (1, 7)')
            ->sum('valor');

        if ($gastosWeekend <= 0) return;

        $pct = ($gastosWeekend / $this->currentDespesas) * 100;
        if ($pct > 35) {
            $insights[] = new InsightItemDTO(
                type: InsightType::WARNING,
                icon: 'calendar-range',
                title: 'Gastos altos no fim de semana',
                message: sprintf(
                    '%.0f%% dos seus gastos (R$ %.2f) aconteceram nos fins de semana. Fique atento aos gastos de lazer!',
                    $pct,
                    $gastosWeekend
                ),
                value: $gastosWeekend,
                percentage: $pct,
            );
        }
    }

    /** Insight 15: Número de transações vs mês anterior */
    private function addTransactionCountVariation(array &$insights): void
    {
        $qtdAtual = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->count();

        $qtdAnterior = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->whereBetween('data', [$this->previousStart->toDateString(), $this->previousEnd->toDateString()])
            ->count();

        if ($qtdAnterior <= 5 || $qtdAtual <= 0) return;

        $variacao = (($qtdAtual - $qtdAnterior) / $qtdAnterior) * 100;

        if ($variacao > 30) {
            $insights[] = new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'list-plus',
                title: 'Mais transações este mês',
                message: sprintf(
                    'Você fez %d transações de despesa, %.0f%% a mais que no mês anterior (%d). Gastos frequentes podem se acumular.',
                    $qtdAtual,
                    $variacao,
                    $qtdAnterior
                ),
                percentage: $variacao,
            );
        } elseif ($variacao < -30) {
            $insights[] = new InsightItemDTO(
                type: InsightType::SUCCESS,
                icon: 'list-minus',
                title: 'Menos transações de gasto',
                message: sprintf(
                    'Você fez %d transações vs %d no mês anterior — %.0f%% menos gastos avulsos!',
                    $qtdAtual,
                    $qtdAnterior,
                    abs($variacao)
                ),
                percentage: abs($variacao),
            );
        }
    }

    // ─── Helpers ────────────────────────────────────────────

    private function getPaymentMethodName(string $key): string
    {
        return [
            'pix'             => 'Pix',
            'cartao_credito'  => 'Cartão de Crédito',
            'cartao_debito'   => 'Cartão de Débito',
            'dinheiro'        => 'Dinheiro',
            'boleto'          => 'Boleto',
            'deposito'        => 'Depósito',
            'transferencia'   => 'Transferência',
        ][$key] ?? ucfirst($key);
    }
}
