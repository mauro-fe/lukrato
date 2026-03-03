<?php

declare(strict_types=1);

namespace Application\Support;

/**
 * Helpers para operações com faturas de cartão de crédito.
 */
class FaturaHelper
{
    /**
     * Extrai mês e ano de uma string de observação de pagamento de fatura.
     *
     * Procura padrão "Fatura MM/YYYY" na string.
     *
     * @param string|null $observacao
     * @return array{mes: int, ano: int}|null Retorna null se não encontrar o padrão
     */
    public static function parseMonthYearFromObservacao(?string $observacao): ?array
    {
        if (!$observacao) {
            return null;
        }

        if (preg_match('/Fatura (\d{1,2})\/(\d{4})/', $observacao, $matches)) {
            $mes = (int) $matches[1];
            $ano = (int) $matches[2];

            if ($mes >= 1 && $mes <= 12 && $ano >= 2000) {
                return ['mes' => $mes, 'ano' => $ano];
            }
        }

        return null;
    }
}
