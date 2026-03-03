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
            'hora_lancamento' => $lancamento->hora_lancamento ?? null,
            'tipo' => (string)$lancamento->tipo,
            'valor' => (float)$lancamento->valor,
            'descricao' => (string)($lancamento->descricao ?? ''),
            'observacao' => (string)($lancamento->observacao ?? ''),
            'categoria_id' => (int)$lancamento->categoria_id ?: null,
            'conta_id' => (int)$lancamento->conta_id ?: null,
            'conta_id_destino' => (int)($lancamento->conta_id_destino ?? 0) ?: null,
            'eh_transferencia' => (bool)($lancamento->eh_transferencia ?? false),
            'eh_saldo_inicial' => (bool)($lancamento->eh_saldo_inicial ?? false),
            'pago' => (bool)($lancamento->pago ?? true),
            'data_pagamento' => $lancamento->data_pagamento ?? null,
            'parcelamento_id' => (int)($lancamento->parcelamento_id ?? 0) ?: null,
            'numero_parcela' => $lancamento->numero_parcela ? (int)$lancamento->numero_parcela : null,
            'cartao_credito_id' => (int)($lancamento->cartao_credito_id ?? 0) ?: null,
            'forma_pagamento' => (string)($lancamento->forma_pagamento ?? ''),
            'origem_tipo' => (string)($lancamento->origem_tipo ?? ''),
            // Recorrência
            'recorrente' => (bool)($lancamento->recorrente ?? false),
            'recorrencia_freq' => $lancamento->recorrencia_freq ?? null,
            'recorrencia_fim' => $lancamento->recorrencia_fim ?? null,
            'recorrencia_total' => $lancamento->recorrencia_total ? (int)$lancamento->recorrencia_total : null,
            'recorrencia_pai_id' => $lancamento->recorrencia_pai_id ? (int)$lancamento->recorrencia_pai_id : null,
            'cancelado_em' => $lancamento->cancelado_em ?? null,
            // Lembretes
            'lembrar_antes_segundos' => $lancamento->lembrar_antes_segundos ? (int)$lancamento->lembrar_antes_segundos : null,
            'canal_email' => (bool)($lancamento->canal_email ?? false),
            'canal_inapp' => (bool)($lancamento->canal_inapp ?? false),
            // Relações
            'categoria' => $lancamento->categoria?->nome ?? '',
            'categoria_nome' => $lancamento->categoria?->nome ?? '',
            'subcategoria_id' => $lancamento->subcategoria_id ? (int) $lancamento->subcategoria_id : null,
            'subcategoria_nome' => $lancamento->subcategoria?->nome ?? '',
            'subcategoria_icone' => $lancamento->subcategoria?->icone ?? '',
            'conta' => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
            'conta_nome' => $lancamento->conta?->nome ?? $lancamento->conta?->instituicao ?? '',
            'conta_instituicao' => $lancamento->conta?->instituicao ?? '',
            'cartao_nome' => $lancamento->cartaoCredito?->nome_cartao ?? '',
            'cartao_bandeira' => $lancamento->cartaoCredito?->bandeira ?? '',
            // Totais reais do parcelamento
            'total_parcelas' => $lancamento->parcelamento?->numero_parcelas ? (int)$lancamento->parcelamento->numero_parcelas : ($lancamento->total_parcelas ? (int)$lancamento->total_parcelas : null),
            'parcelas_pagas' => $lancamento->parcelamento?->parcelas_pagas !== null ? (int)$lancamento->parcelamento->parcelas_pagas : null,
            'parcelamento_status' => $lancamento->parcelamento?->status ?? null,
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
