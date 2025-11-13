<?php

declare(strict_types=1);

namespace Application\Builders;

use Application\Controllers\Api\ReportType;
use Application\DTO\ReportData;
use Application\DTO\ReportParameters;
use InvalidArgumentException;

class ReportExportBuilder
{
    public function build(ReportType $type, ReportParameters $params, array $payload): ReportData
    {
        [$headers, $rows] = $this->processData($type, $payload);

        if (empty($rows)) {
            $rows[] = ['Sem dados disponiveis', ...array_fill(0, count($headers) - 1, '-')];
        }

        return new ReportData(
            title: $this->resolveTitle($type, $params, $payload),
            headers: $headers,
            rows: $rows,
            subtitle: $this->buildSubtitle($params)
        );
    }

    private function processData(ReportType $type, array $payload): array
    {
        return match ($type) {
            ReportType::DESPESAS_POR_CATEGORIA,
            ReportType::RECEITAS_POR_CATEGORIA,
            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA,
            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA => [
                ['Categoria', 'Valor'],
                $this->mapSingleColumn($payload)
            ],

            ReportType::SALDO_MENSAL,
            ReportType::EVOLUCAO_12M => [
                ['Periodo', 'Saldo acumulado'],
                $this->mapSingleColumn($payload)
            ],

            ReportType::RECEITAS_DESPESAS_DIARIO,
            ReportType::RESUMO_ANUAL => [
                ['Periodo', 'Receitas', 'Despesas'],
                $this->mapMultiColumn($payload, ['receitas', 'despesas'])
            ],

            ReportType::RECEITAS_DESPESAS_POR_CONTA => [
                ['Conta', 'Receitas', 'Despesas'],
                $this->mapMultiColumn($payload, ['receitas', 'despesas'])
            ],

            default => throw new InvalidArgumentException("Relatorio '{$type->value}' nao implementado no exportador."),
        };
    }

    private function mapSingleColumn(array $payload): array
    {
        $labels = $payload['labels'] ?? [];
        $values = $payload['values'] ?? [];

        return array_map(fn($index, $label) => [
            (string) $label,
            $this->formatMoney((float) ($values[$index] ?? 0)),
        ], array_keys($labels), $labels);
    }

    private function mapMultiColumn(array $payload, array $keys): array
    {
        $labels = $payload['labels'] ?? [];

        return array_map(function ($index, $label) use ($payload, $keys) {
            $row = [(string) $label];
            foreach ($keys as $key) {
                $value = (float) ($payload[$key][$index] ?? 0);
                $row[] = $this->formatMoney($value);
            }
            return $row;
        }, array_keys($labels), $labels);
    }

    private function resolveTitle(ReportType $type, ReportParameters $params, array $payload): string
    {
        $year = $payload['year'] ?? $params->start->format('Y');

        return match ($type) {
            ReportType::DESPESAS_POR_CATEGORIA => 'Despesas por categoria',
            ReportType::RECEITAS_POR_CATEGORIA => 'Receitas por categoria',
            ReportType::DESPESAS_ANUAIS_POR_CATEGORIA => "Despesas anuais ({$year})",
            ReportType::RECEITAS_ANUAIS_POR_CATEGORIA => "Receitas anuais ({$year})",
            ReportType::SALDO_MENSAL => 'Saldo diario acumulado',
            ReportType::RECEITAS_DESPESAS_DIARIO => 'Receitas vs despesas (diario)',
            ReportType::EVOLUCAO_12M => 'Evolucao do saldo (12 meses)',
            ReportType::RECEITAS_DESPESAS_POR_CONTA => 'Receitas vs despesas por conta',
            ReportType::RESUMO_ANUAL => "Resumo anual {$year}",
        };
    }

    private function buildSubtitle(ReportParameters $params): string
    {
        $period = $params->isSingleMonth()
            ? $params->start->format('m/Y')
            : sprintf('%s a %s', $params->start->format('d/m/Y'), $params->end->format('d/m/Y'));

        $parts = ["Periodo: {$period}"];

        if ($params->accountId) {
            $parts[] = 'Filtro: Conta especifica';
        }

        if ($params->includeTransfers) {
            $parts[] = '(Inclui transferencias)';
        }

        return implode(' | ', $parts);
    }

    private function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}

