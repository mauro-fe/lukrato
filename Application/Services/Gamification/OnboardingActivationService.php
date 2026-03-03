<?php

declare(strict_types=1);

namespace Application\Services\Gamification;

use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;

class OnboardingActivationService
{
    public function checkAndComplete(int $userId): void
    {
        // 🔒 Verificação rápida: se já concluiu, sai imediatamente
        $user = Usuario::select('id', 'onboarding_completed_at')
            ->find($userId);

        if (!$user || $user->onboarding_completed_at !== null) {
            return;
        }

        // 🧠 Regra de ativação:
        // - Pelo menos 1 conta
        // - Pelo menos 1 lançamento real (não transferência e não saldo inicial)

        $temConta = Conta::where('user_id', $userId)->exists();

        if (!$temConta) {
            return;
        }

        $temLancamentoReal = Lancamento::where('user_id', $userId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0)
            ->exists();

        if (!$temLancamentoReal) {
            return;
        }

        // 🔥 Atualização direta e segura (evita race condition)
        Usuario::where('id', $userId)
            ->whereNull('onboarding_completed_at')
            ->update([
                'onboarding_completed_at' => now(),
            ]);

        LogService::info('Onboarding ativado automaticamente', [
            'user_id' => $userId,
        ]);
    }
}
