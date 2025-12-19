<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Enums\LancamentoTipo;

/**
 * Validador para lançamentos.
 */
class LancamentoValidator
{
    /**
     * Valida dados para criação de lançamento.
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Validar tipo
        $tipo = strtolower(trim($data['tipo'] ?? ''));
        if (empty($tipo)) {
            $errors['tipo'] = 'O tipo é obrigatório.';
        } else {
            try {
                LancamentoTipo::from($tipo);
            } catch (\ValueError) {
                $errors['tipo'] = 'Tipo inválido. Use "receita" ou "despesa".';
            }
        }

        // Validar data
        $data_value = $data['data'] ?? '';
        if (empty($data_value)) {
            $errors['data'] = 'A data é obrigatória.';
        } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data_value)) {
            $errors['data'] = 'Data inválida. Use o formato YYYY-MM-DD.';
        }

        // Validar valor
        $valor = $data['valor'] ?? null;
        if ($valor === null || $valor === '') {
            $errors['valor'] = 'O valor é obrigatório.';
        } else {
            // Sanitizar valor
            if (is_string($valor)) {
                $valor = str_replace(['R$', ' ', '.'], '', $valor);
                $valor = str_replace(',', '.', $valor);
            }
            
            if (!is_numeric($valor) || !is_finite((float)$valor)) {
                $errors['valor'] = 'Valor inválido.';
            } elseif ((float)$valor <= 0) {
                $errors['valor'] = 'O valor deve ser maior que zero.';
            }
        }

        // Validar descrição
        $descricao = trim($data['descricao'] ?? '');
        if (empty($descricao)) {
            $errors['descricao'] = 'A descrição é obrigatória.';
        } elseif (mb_strlen($descricao) > 190) {
            $errors['descricao'] = 'A descrição não pode ter mais de 190 caracteres.';
        }

        // Validar observação (opcional)
        $observacao = trim($data['observacao'] ?? '');
        if (!empty($observacao) && mb_strlen($observacao) > 500) {
            $errors['observacao'] = 'A observação não pode ter mais de 500 caracteres.';
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de lançamento.
     */
    public static function validateUpdate(array $data): array
    {
        return self::validateCreate($data);
    }

    /**
     * Sanitiza o valor do lançamento.
     */
    public static function sanitizeValor(mixed $valor): float
    {
        if (is_string($valor)) {
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return round(abs((float)$valor), 2);
    }
}
