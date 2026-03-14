<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Infrastructure\LogService;

/**
 * Executa pagamento de fatura de cartão de crédito.
 * Delega para CartaoFaturaService::pagarFatura().
 */
class PayFaturaAction implements ActionInterface
{
    public function execute(int $userId, array $payload): ActionResult
    {
        $service = new CartaoFaturaService();

        $result = $service->pagarFatura(
            cartaoId: (int) $payload['cartao_id'],
            mes: (int) $payload['mes'],
            ano: (int) $payload['ano'],
            userId: $userId,
            contaIdOverride: $payload['conta_id'] ?? null,
        );

        $fmtValor = 'R$ ' . number_format((float) $result['valor_pago'], 2, ',', '.');
        $cartaoNome = $payload['cartao_nome'] ?? 'cartão';

        // Gamification — mesma chamada que o controller faz
        try {
            $achievement = new AchievementService();
            $achievement->checkAndUnlockAchievements($userId, 'invoice_paid');
        } catch (\Throwable $e) {
            LogService::warning('PayFaturaAction.gamification', ['error' => $e->getMessage()]);
        }

        return ActionResult::ok(
            "💳 Fatura do **{$cartaoNome}** paga! **{$fmtValor}** debitado — {$result['itens_pagos']} item(s) marcado(s) como pago(s).",
            $result
        );
    }
}
