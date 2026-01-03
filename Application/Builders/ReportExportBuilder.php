<?php

declare(strict_types=1);

namespace Application\Builders;

use Application\Enums\ReportType;
use Application\DTO\ReportData;
use Application\DTO\ReportParameters;
use InvalidArgumentException;

class ReportExportBuilder
{
    private const EMPTY_DATA_MESSAGE = 'Nenhum dado disponível para o período selecionado';
    private const CURRENCY_SYMBOL = 'R$';

    public function build(ReportType $type, ReportParameters $params, array $payload): ReportData
    {
        [$headers, $rows] = $this->processData($type, $payload);

        if (empty($rows)) {
            $rows = $this->createEmptyRow($headers);
        }

        return new ReportData(
            title: $this->resolveTitle($type, $params, $payload),
            headers: $headers,
            rows: $rows,
            subtitle: $this->buildSubtitle($params),
            totals: $this->buildTotals($type, $payload)
        );
    }

    private function processData(ReportType $type, array $payload): array
    {
        return match ($type) {
            ReportType::DESPESAS_POR_CATEGORIA,
            ReportType::RECEITAS_POR_CATEGORIA =>
            $this->processCategoryReport($payload, 'Categoria'),

            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA,
            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA =>
            $this->processCategoryReport($payload, 'Categoria'),

            ReportType::SALDO_MENSAL =>
            $this->processBalanceReport($payload),

            ReportType::EVOLUCAO_12M =>
            $this->processEvolutionReport($payload),

            ReportType::RECEITAS_DESPESAS_DIARIO =>
            $this->processIncomeExpenseReport($payload, 'Período'),

            ReportType::RESUMO_ANUAL =>
            $this->processAnnualSummary($payload),

            ReportType::RECEITAS_DESPESAS_POR_CONTA =>
            $this->processAccountReport($payload),

            default => throw new InvalidArgumentException(
                "Tipo de relatório '{$type->value}' não implementado no exportador."
            ),
        };
    }

    private function processCategoryReport(array $payload, string $labelHeader): array
    {
        return [
            [$labelHeader, 'Valor Total'],
            $this->mapSingleColumn($payload)
        ];
    }

    private function processBalanceReport(array $payload): array
    {
        return [
            ['Período', 'Saldo Acumulado'],
            $this->mapSingleColumn($payload)
        ];
    }

    private function processEvolutionReport(array $payload): array
    {
        return [
            ['Mês', 'Saldo Acumulado'],
            $this->mapSingleColumn($payload)
        ];
    }

    private function processIncomeExpenseReport(array $payload, string $periodLabel): array
    {
        return [
            [$periodLabel, 'Receitas', 'Despesas', 'Saldo'],
            $this->mapIncomeExpenseWithBalance($payload)
        ];
    }

    private function processAnnualSummary(array $payload): array
    {
        return [
            ['Mês', 'Receitas', 'Despesas', 'Saldo'],
            $this->mapIncomeExpenseWithBalance($payload)
        ];
    }

    private function processAccountReport(array $payload): array
    {
        return [
            ['Conta', 'Receitas', 'Despesas', 'Saldo'],
            $this->mapIncomeExpenseWithBalance($payload)
        ];
    }

    private function mapSingleColumn(array $payload): array
    {
        $labels = $payload['labels'] ?? [];
        $values = $payload['values'] ?? [];

        return array_map(
            fn($index, $label) => [
                $this->sanitizeLabel($label),
                $this->formatMoney((float) ($values[$index] ?? 0)),
            ],
            array_keys($labels),
            $labels
        );
    }

    private function mapIncomeExpenseWithBalance(array $payload): array
    {
        $labels = $payload['labels'] ?? [];
        $receitas = $payload['receitas'] ?? [];
        $despesas = $payload['despesas'] ?? [];

        return array_map(
            function ($index, $label) use ($receitas, $despesas) {
                $receitaValue = (float) ($receitas[$index] ?? 0);
                $despesaValue = (float) ($despesas[$index] ?? 0);
                $saldo = $receitaValue - $despesaValue;

                return [
                    $this->sanitizeLabel($label),
                    $this->formatMoney($receitaValue),
                    $this->formatMoney($despesaValue),
                    $this->formatMoney($saldo),
                ];
            },
            array_keys($labels),
            $labels
        );
    }

    private function resolveTitle(ReportType $type, ReportParameters $params, array $payload): string
    {
        $year = $payload['year'] ?? $params->start->format('Y');
        $monthYear = $params->start->format('m/Y');

        return match ($type) {
            ReportType::DESPESAS_POR_CATEGORIA => "Despesas por Categoria - {$monthYear}",
            ReportType::RECEITAS_POR_CATEGORIA => "Receitas por Categoria - {$monthYear}",
            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA => "Despesas Anuais por Categoria ({$year})",
            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA => "Receitas Anuais por Categoria ({$year})",
            ReportType::SALDO_MENSAL => "Evolução do Saldo Diário - {$monthYear}",
            ReportType::RECEITAS_DESPESAS_DIARIO => "Receitas vs Despesas (Diário) - {$monthYear}",
            ReportType::EVOLUCAO_12M => 'Evolução do Saldo (Últimos 12 Meses)',
            ReportType::RECEITAS_DESPESAS_POR_CONTA => "Receitas vs Despesas por Conta - {$monthYear}",
            ReportType::RESUMO_ANUAL => "Resumo Financeiro Anual - {$year}",
        };
    }

    private function buildSubtitle(ReportParameters $params): string
    {
        $parts = [];

        // Period
        $parts[] = 'Periodo: ' . $params->getPeriodLabel();

        // Filtros aplicados
        $filters = [];

        if ($params->accountId) {
            $filters[] = 'Conta especifica';
        }

        if ($params->includeTransfers) {
            $filters[] = 'Inclui transferencias';
        }

        if (!empty($filters)) {
            $parts[] = 'Filtros: ' . implode(', ', $filters);
        }

        return implode(' | ', $parts);
    }

    private function buildTotals(ReportType $type, array $payload): array
    {
        return match ($type) {
            ReportType::DESPESAS_POR_CATEGORIA,
            ReportType::RECEITAS_POR_CATEGORIA,
            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA,
            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA =>
            $this->buildSingleValueTotals($payload),

            ReportType::RECEITAS_DESPESAS_DIARIO,
            ReportType::RESUMO_ANUAL,
            ReportType::RECEITAS_DESPESAS_POR_CONTA =>
            $this->buildIncomeExpenseTotals($payload),

            ReportType::SALDO_MENSAL,
            ReportType::EVOLUCAO_12M =>
            $this->buildBalanceTotals($payload),

            default => [],
        };
    }

    private function buildSingleValueTotals(array $payload): array
    {
        $total = $this->sumValues($payload['values'] ?? []);

        return [
            'Total Geral' => $this->formatMoney($total),
        ];
    }

    private function buildIncomeExpenseTotals(array $payload): array
    {
        $receitas = $this->sumValues($payload['receitas'] ?? []);
        $despesas = $this->sumValues($payload['despesas'] ?? []);
        $saldo = $receitas - $despesas;

        return [
            'Total de Receitas' => $this->formatMoney($receitas),
            'Total de Despesas' => $this->formatMoney($despesas),
            'Saldo do Período' => $this->formatMoney($saldo),
        ];
    }

    private function buildBalanceTotals(array $payload): array
    {
        $values = $payload['values'] ?? [];

        if (empty($values)) {
            return [];
        }

        $saldoFinal = end($values);
        $saldoInicial = reset($values);

        return [
            'Saldo Inicial' => $this->formatMoney((float) $saldoInicial),
            'Saldo Final' => $this->formatMoney((float) $saldoFinal),
        ];
    }

    private function createEmptyRow(array $headers): array
    {
        $emptyRow = [self::EMPTY_DATA_MESSAGE];

        for ($i = 1; $i < count($headers); $i++) {
            $emptyRow[] = '-';
        }

        return [$emptyRow];
    }

    private function sanitizeLabel($label): string
    {
        return trim((string) $label) ?: 'Não especificado';
    }

    private function formatMoney(float $value): string
    {
        return self::CURRENCY_SYMBOL . ' ' . number_format($value, 2, ',', '.');
    }

    private function sumValues(array $values): float
    {
        return array_reduce(
            $values,
            fn(float $carry, $value) => $carry + (float) $value,
            0.0
        );
    }
}
