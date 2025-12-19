<?php

declare(strict_types=1);

namespace Application\Formatters;

use Application\Models\Lancamento;

/**
 * Formatador para respostas de lançamentos.
 */
class LancamentoResponseFormatter
{
    /**
     * Formata um lançamento para resposta JSON.
     */
    public static function format(Lancamento $lancamento): array
    {
        return [
            'id' => (int)$lancamento->id,
            'data' => (string)$lancamento->data,
            'tipo' => (string)$lancamento->tipo,
            'valor' => (float)$lancamento->valor,
            'descricao' => (string)($lancamento->descricao ?? ''),
            'observacao' => (string)($lancamento->observacao ?? ''),
            'categoria_id' => (int)$lancamento->categoria_id ?: null,
            'conta_id' => (int)$lancamento->conta_id ?: null,
            'conta_id_destino' => (int)($lancamento->conta_id_destino ?? 0) ?: null,
            'eh_transferencia' => (bool)($lancamento->eh_transferencia ?? false),
            'eh_saldo_inicial' => (bool)($lancamento->eh_saldo_inicial ?? false),
            'categoria' => $lancamento->categoria?->nome ?? '',
            'categoria_nome' => $lancamento->categoria?->nome ?? '',
            'conta' => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
            'conta_nome' => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
            'conta_instituicao' => $lancamento->conta?->instituicao ?? '',
        ];
    }

    /**
     * Formata uma coleção de lançamentos.
     */
    public static function formatCollection(iterable $lancamentos): array
    {
        $formatted = [];
        
        foreach ($lancamentos as $lancamento) {
            $formatted[] = self::format($lancamento);
        }
        
        return $formatted;
    }
}
