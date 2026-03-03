<?php

namespace Application\Controllers\Api\Lancamentos;

/**
 * Helpers compartilhados entre os controllers de lançamentos.
 */
trait LancamentoHelpersTrait
{
    /**
     * Interpreta o parâmetro de categoria da query string.
     *
     * @return array{id: int|null, isNull: bool}
     */
    private function parseCategoriaParam(string $param): array
    {
        if (in_array(strtolower($param), ['none', 'null', '0'], true)) {
            return ['id' => null, 'isNull' => true];
        }

        if (is_numeric($param) && (int) $param > 0) {
            return ['id' => (int) $param, 'isNull' => false];
        }

        return ['id' => null, 'isNull' => false];
    }
}
