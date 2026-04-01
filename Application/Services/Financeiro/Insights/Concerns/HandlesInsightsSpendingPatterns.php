<?php

declare(strict_types=1);

namespace Application\Services\Financeiro\Insights\Concerns;

use Application\DTO\InsightItemDTO;
use Application\Enums\InsightType;
use Application\Models\Lancamento;

trait HandlesInsightsSpendingPatterns
{
    private function addTopCategory(array &$insights): void
    {
        $topCategoria = Lancamento::where('lancamentos.user_id', $this->userId)
            ->where('lancamentos.tipo', 'despesa')
            ->where('lancamentos.pago', 1)
            ->where('lancamentos.afeta_caixa', 1)
            ->whereBetween('lancamentos.data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->where(function ($q) {
                $q->whereNull('lancamentos.origem_tipo')
                    ->orWhere('lancamentos.origem_tipo', '!=', 'pagamento_fatura');
            })
            ->join('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nome, SUM(lancamentos.valor) as total')
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('total')
            ->first();

        if (!$topCategoria || $topCategoria->total <= 0 || $this->currentDespesas <= 0) {
            return;
        }

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

    private function addLargestExpense(array &$insights): void
    {
        $maiorGasto = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->orderByDesc('valor')
            ->first();

        if (!$maiorGasto || $maiorGasto->valor <= 0 || $this->currentDespesas <= 0) {
            return;
        }

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

    private function addPaymentMethodPreference(array &$insights): void
    {
        $formaPagamento = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->whereNotNull('forma_pagamento')
            ->where('forma_pagamento', '!=', '')
            ->selectRaw('forma_pagamento, COUNT(*) as qtd, SUM(valor) as total')
            ->groupBy('forma_pagamento')
            ->orderByDesc('total')
            ->first();

        if (!$formaPagamento || $this->currentDespesas <= 0) {
            return;
        }

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

    private function addSpendingConcentration(array &$insights): void
    {
        $categorias = Lancamento::where('lancamentos.user_id', $this->userId)
            ->where('lancamentos.tipo', 'despesa')
            ->where('lancamentos.eh_transferencia', 0)
            ->where('lancamentos.pago', 1)
            ->where('lancamentos.afeta_caixa', 1)
            ->whereBetween('lancamentos.data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->whereNotNull('lancamentos.categoria_id')
            ->where(function ($q) {
                $q->whereNull('lancamentos.origem_tipo')
                    ->orWhere('lancamentos.origem_tipo', '!=', 'pagamento_fatura');
            })
            ->join('categorias', 'lancamentos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nome, SUM(lancamentos.valor) as total')
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('total')
            ->get();

        if ($categorias->count() < 3) {
            return;
        }

        $topTwo = $categorias->take(2)->sum('total');
        $total = $categorias->sum('total');

        if ($total <= 0) {
            return;
        }

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

    private function addWeekendSpending(array &$insights): void
    {
        if ($this->currentDespesas <= 0) {
            return;
        }

        $gastosWeekend = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->whereRaw('DAYOFWEEK(data) IN (1, 7)')
            ->sum('valor');

        if ($gastosWeekend <= 0) {
            return;
        }

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

    private function addTransactionCountVariation(array &$insights): void
    {
        $qtdAtual = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$this->currentStart->toDateString(), $this->currentEnd->toDateString()])
            ->count();

        $qtdAnterior = Lancamento::where('user_id', $this->userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$this->previousStart->toDateString(), $this->previousEnd->toDateString()])
            ->count();

        if ($qtdAnterior <= 5 || $qtdAtual <= 0) {
            return;
        }

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

    private function getPaymentMethodName(string $key): string
    {
        return [
            'pix' => 'Pix',
            'cartao_credito' => 'Cartão de Crédito',
            'cartao_debito' => 'Cartão de Débito',
            'dinheiro' => 'Dinheiro',
            'boleto' => 'Boleto',
            'deposito' => 'Depósito',
            'transferencia' => 'Transferência',
        ][$key] ?? ucfirst($key);
    }
}
