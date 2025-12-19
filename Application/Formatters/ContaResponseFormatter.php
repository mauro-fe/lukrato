<?php

declare(strict_types=1);

namespace Application\Formatters;

use Application\Models\Conta;

/**
 * Formatador para respostas de contas.
 */
class ContaResponseFormatter
{
    /**
     * Formata uma conta para resposta JSON.
     */
    public static function format(
        Conta $conta,
        array $extras = [],
        array $saldoIniciais = []
    ): array {
        $contaId = (int) $conta->id;
        $x = $extras[$contaId] ?? null;
        $initial = (float) ($saldoIniciais[$contaId] ?? 0);

        return [
            'id' => $contaId,
            'nome' => (string) $conta->nome,
            'instituicao' => (string) ($conta->instituicao ?? ''),
            'moeda' => (string) ($conta->moeda ?? 'BRL'),
            'saldoInicial' => $initial,
            'saldoAtual' => $x['saldoAtual'] ?? null,
            'entradasTotal' => $x['entradasTotal'] ?? 0.0,
            'saidasTotal' => $x['saidasTotal'] ?? 0.0,
            'ativo' => (bool) $conta->ativo,
            'arquivada' => !(bool) $conta->ativo,
        ];
    }

    /**
     * Formata uma coleção de contas.
     */
    public static function formatCollection(
        iterable $contas,
        array $extras = [],
        array $saldoIniciais = []
    ): array {
        $formatted = [];
        
        foreach ($contas as $conta) {
            $formatted[] = self::format($conta, $extras, $saldoIniciais);
        }
        
        return $formatted;
    }
}
