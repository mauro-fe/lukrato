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
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Validar nome
        $nome = trim((string) ($data['nome'] ?? ''));
        if (empty($nome)) {
            $errors['nome'] = 'O nome é obrigatório.';
        } elseif (mb_strlen($nome) > 100) {
            $errors['nome'] = 'O nome não pode ter mais de 100 caracteres.';
        }

        // Validar moeda (opcional, padrão é BRL)
        $moeda = strtoupper(trim((string) ($data['moeda'] ?? '')));
        if (!empty($moeda)) {
            try {
                Moeda::from($moeda);
            } catch (\ValueError) {
                $errors['moeda'] = 'Moeda inválida. Use BRL, USD ou EUR.';
            }
        }

        // Validar instituição (opcional)
        $instituicao = trim((string) ($data['instituicao'] ?? ''));
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
     * Campos são opcionais na atualização — valida apenas os que foram enviados.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateUpdate(array $data): array
    {
        $errors = [];

        // Validar nome somente se enviado
        if (array_key_exists('nome', $data)) {
            $nome = trim((string) ($data['nome'] ?? ''));
            if (empty($nome)) {
                $errors['nome'] = 'O nome é obrigatório.';
            } elseif (mb_strlen($nome) > 100) {
                $errors['nome'] = 'O nome não pode ter mais de 100 caracteres.';
            }
        }

        // Validar moeda somente se enviada
        if (array_key_exists('moeda', $data) && !empty($data['moeda'])) {
            $moeda = strtoupper(trim((string) $data['moeda']));
            try {
                Moeda::from($moeda);
            } catch (\ValueError) {
                $errors['moeda'] = 'Moeda inválida. Use BRL, USD ou EUR.';
            }
        }

        // Validar instituição somente se enviada
        if (array_key_exists('instituicao', $data)) {
            $instituicao = trim((string) ($data['instituicao'] ?? ''));
            if (!empty($instituicao) && mb_strlen($instituicao) > 100) {
                $errors['instituicao'] = 'A instituição não pode ter mais de 100 caracteres.';
            }
        }

        // Validar saldo inicial somente se enviado
        if (array_key_exists('saldo_inicial', $data)) {
            $saldoInicial = $data['saldo_inicial'];
            if ($saldoInicial !== null && $saldoInicial !== '') {
                if (!is_numeric($saldoInicial) || !is_finite((float)$saldoInicial)) {
                    $errors['saldo_inicial'] = 'Saldo inicial inválido.';
                }
            }
        }

        return $errors;
    }
}
