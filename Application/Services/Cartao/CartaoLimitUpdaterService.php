<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Models\CartaoCredito;
use Application\Services\Infrastructure\LogService;

class CartaoLimitUpdaterService
{
    public function atualizarLimiteCartao(
        int $cartaoId,
        float|int|string $valor,
        int $userId,
        string $operacao
    ): void {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);
        if (!$cartao) {
            return;
        }

        $limiteAnterior = $cartao->limite_disponivel;

        $cartao->atualizarLimiteDisponivel();
        $cartao->refresh();

        LogService::info('💳 [LIMITE] Limite atualizado', [
            'cartao_id' => $cartaoId,
            'operacao' => $operacao,
            'valor' => (float) $valor,
            'limite_anterior' => $limiteAnterior,
            'limite_novo' => $cartao->limite_disponivel,
        ]);
    }
}
