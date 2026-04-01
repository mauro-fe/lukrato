<?php

declare(strict_types=1);

namespace Application\Services\Report\Insights\Concerns;

use Application\DTO\InsightItemDTO;
use Application\Enums\InsightType;
use Carbon\Carbon;

trait HandlesInsightsCashflow
{
    private function addDespesasComparison(array &$insights): void
    {
        if ($this->previousDespesas <= 0) {
            return;
        }

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

    private function addReceitasVariation(array &$insights): void
    {
        if ($this->previousReceitas <= 0) {
            return;
        }

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

    private function addDailyAverageProjection(array &$insights, int $year, int $month): void
    {
        $hoje = Carbon::now();
        $diaAtual = ($hoje->year == $year && $hoje->month == $month)
            ? $hoje->day
            : $this->currentEnd->day;

        if ($diaAtual <= 0 || $this->currentDespesas <= 0) {
            return;
        }

        $mediaDiaria = $this->currentDespesas / $diaAtual;
        $diasNoMes = $this->currentEnd->day;
        $projecao = $mediaDiaria * $diasNoMes;

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
}
