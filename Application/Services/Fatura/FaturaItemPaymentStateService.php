<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use InvalidArgumentException;

class FaturaItemPaymentStateService
{
    /**
     * Marcar item como pago e atualizar lançamento existente.
     */
    public function marcarItemComoPago(FaturaCartaoItem $item, int $usuarioId): void
    {
        if ($item->pago) {
            return;
        }

        if (!$item->cartaoCredito) {
            throw new InvalidArgumentException('Cartão não encontrado');
        }

        if (!$item->cartaoCredito->conta_id) {
            throw new InvalidArgumentException(
                "Cartão '{$item->cartaoCredito->nome}' não tem conta vinculada"
            );
        }

        $dataPagamento = now()->format('Y-m-d');

        if ($item->lancamento_id) {
            $lancamento = Lancamento::forUser($usuarioId)->find($item->lancamento_id);
            if ($lancamento) {
                $lancamento->update([
                    'pago' => true,
                    'data_pagamento' => $dataPagamento,
                    'afeta_caixa' => true,
                    'observacao' => sprintf(
                        'Pagamento de fatura - %s (Parcela %d/%d) - pago em %s',
                        $item->cartaoCredito->nome ?? $item->cartaoCredito->bandeira ?? 'Cartão',
                        $item->parcela_atual ?? 1,
                        $item->total_parcelas ?? 1,
                        date('d/m/Y', strtotime($dataPagamento))
                    ),
                ]);
            }
        } else {
            $valorFormatado = round((float) $item->valor, 2);
            $dataCompra = $item->data_compra ? $item->data_compra->format('Y-m-d') : $dataPagamento;

            $lancamento = Lancamento::create([
                'user_id' => $usuarioId,
                'tipo' => 'despesa',
                'valor' => $valorFormatado,
                'data' => $dataCompra,
                'data_competencia' => $dataCompra,
                'descricao' => $item->descricao ?: 'Pagamento de fatura',
                'categoria_id' => $item->categoria_id,
                'conta_id' => $item->cartaoCredito->conta_id,
                'cartao_credito_id' => $item->cartao_credito_id,
                'pago' => true,
                'data_pagamento' => $dataPagamento,
                'observacao' => sprintf(
                    'Pagamento de fatura - %s (Parcela %d/%d) (migrado)',
                    $item->cartaoCredito->nome ?? $item->cartaoCredito->bandeira ?? 'Cartão',
                    $item->parcela_atual ?? 1,
                    $item->total_parcelas ?? 1
                ),
                'afeta_competencia' => true,
                'afeta_caixa' => true,
                'origem_tipo' => 'cartao_credito',
            ]);

            $item->lancamento_id = $lancamento->id;
        }

        $item->pago = true;
        $item->data_pagamento = now();
        $item->save();

        if ($item->cartaoCredito) {
            $item->cartaoCredito->atualizarLimiteDisponivel();
        }

        if ($item->fatura_id) {
            $fatura = Fatura::forUser($usuarioId)->find($item->fatura_id);
            if ($fatura) {
                $fatura->atualizarStatus();
            }
        }
    }

    /**
     * Desmarcar item como pago e reverter lançamento.
     */
    public function desmarcarItemPago(FaturaCartaoItem $item): void
    {
        if (!$item->pago) {
            return;
        }

        if ($item->lancamento_id) {
            $lancamento = Lancamento::forUser($item->user_id)->find($item->lancamento_id);
            if ($lancamento) {
                $lancamento->update([
                    'pago' => false,
                    'data_pagamento' => null,
                    'afeta_caixa' => false,
                    'observacao' => sprintf(
                        'Pagamento revertido - %s (Parcela %d/%d)',
                        $item->descricao ?? 'Item de fatura',
                        $item->parcela_atual ?? 1,
                        $item->total_parcelas ?? 1
                    ),
                ]);
            }
        }

        $item->pago = false;
        $item->data_pagamento = null;
        $item->save();

        if ($item->cartaoCredito) {
            $item->cartaoCredito->atualizarLimiteDisponivel();
        }

        if ($item->fatura_id) {
            $fatura = Fatura::forUser($item->user_id)->find($item->fatura_id);
            if ($fatura) {
                $fatura->atualizarStatus();
            }
        }
    }
}
