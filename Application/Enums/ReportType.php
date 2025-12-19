<?php

namespace Application\Enums;

use ValueError;
use InvalidArgumentException;

enum ReportType: string
{
    case DESPESAS_POR_CATEGORIA = 'despesas_por_categoria';
    case DESPESAS_ANUAIS_POR_CATEGORIA = 'despesas_anuais_por_categoria';
    case RECEITAS_ANUAIS_POR_CATEGORIA = 'receitas_anuais_por_categoria';
    case RECEITAS_POR_CATEGORIA = 'receitas_por_categoria';
    case SALDO_MENSAL = 'saldo_mensal';
    case RECEITAS_DESPESAS_DIARIO = 'receitas_despesas_diario';
    case EVOLUCAO_12M = 'evolucao_12m';
    case RECEITAS_DESPESAS_POR_CONTA = 'receitas_despesas_por_conta';
    case RESUMO_ANUAL = 'resumo_anual';

    public static function fromShorthand(string $shorthand): self
    {
        $map = [
            'rec'       => self::RECEITAS_POR_CATEGORIA,
            'des'       => self::DESPESAS_POR_CATEGORIA,
            'des_cat'   => self::DESPESAS_POR_CATEGORIA,
            'des_anual' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'cat_anual' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'anual_cat' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'categorias_anuais' => self::DESPESAS_ANUAIS_POR_CATEGORIA,
            'rec_anual' => self::RECEITAS_ANUAIS_POR_CATEGORIA,
            'receitas_anuais' => self::RECEITAS_ANUAIS_POR_CATEGORIA,
            'receitas_anuais_categorias' => self::RECEITAS_ANUAIS_POR_CATEGORIA,
            'saldo'     => self::SALDO_MENSAL,
            'rd'        => self::RECEITAS_DESPESAS_DIARIO,
            'recdes'    => self::RECEITAS_DESPESAS_DIARIO,
            'evo'       => self::EVOLUCAO_12M,
            'conta'     => self::RECEITAS_DESPESAS_POR_CONTA,
            'por_conta' => self::RECEITAS_DESPESAS_POR_CONTA,
            'resumo'    => self::RESUMO_ANUAL,
            'anual'     => self::RESUMO_ANUAL,
        ];

        $normalized = strtolower(trim($shorthand));
        
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        try {
            return self::from($normalized);
        } catch (ValueError) {
            throw new InvalidArgumentException("Tipo de relatório '{$shorthand}' inválido.");
        }
    }

    public static function listValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function listValuesString(): string
    {
        return implode(', ', self::listValues());
    }
}
