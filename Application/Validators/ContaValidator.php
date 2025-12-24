<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Enums\Moeda;

/**
 * Validador para contas.
 */
class ContaValidator
{
    /**
     * Valida dados para criação de conta.
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Validar nome
        $nome = trim($data['nome'] ?? '');
        if (empty($nome)) {
            $errors['nome'] = 'O nome é obrigatório.';
        } elseif (mb_strlen($nome) > 100) {
            $errors['nome'] = 'O nome não pode ter mais de 100 caracteres.';
        }

        // Validar moeda (opcional, padrão é BRL)
        $moeda = strtoupper(trim($data['moeda'] ?? ''));
        if (!empty($moeda)) {
            try {
                Moeda::from($moeda);
            } catch (\ValueError) {
                $errors['moeda'] = 'Moeda inválida. Use BRL, USD ou EUR.';
            }
        }

        // Validar instituição (opcional)
        $instituicao = trim($data['instituicao'] ?? '');
        if (!empty($instituicao) && mb_strlen($instituicao) > 100) {
            $errors['instituicao'] = 'A instituição não pode ter mais de 100 caracteres.';
        }

        // Validar saldo inicial (opcional)
        $saldoInicial = $data['saldo_inicial'] ?? null;
        if ($saldoInicial !== null && $saldoInicial !== '') {
            if (!is_numeric($saldoInicial) || !is_finite((float)$saldoInicial)) {
                $errors['saldo_inicial'] = 'Saldo inicial inválido.';
            }
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de conta.
     */
    public static function validateUpdate(array $data): array
    {
        return self::validateCreate($data);
    }
}
