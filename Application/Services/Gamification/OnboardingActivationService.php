<?php

declare(strict_types=1);

namespace Application\Services\Gamification;

use Application\Models\Lancamento;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\OnboardingProgressService;

class OnboardingActivationService
{
    private OnboardingProgressService $progressService;

    public function __construct(?OnboardingProgressService $progressService = null)
    {
        $this->progressService = $progressService ?? new OnboardingProgressService();
    }

    public function checkAndComplete(int $userId): void
    {
        $user = Usuario::select('id', 'onboarding_completed_at')
            ->find($userId);

        if (!$user || $user->onboarding_completed_at !== null) {
            return;
        }

        $progress = $this->progressService->getProgress($userId);
        if (!$progress->has_conta) {
            return;
        }

        $temLancamentoReal = Lancamento::where('user_id', $userId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0)
            ->exists();

        if (!$temLancamentoReal) {
            return;
        }

        Usuario::where('id', $userId)
            ->whereNull('onboarding_completed_at')
            ->update([
                'onboarding_completed_at' => now(),
            ]);

        $this->progressService->markCompleted($userId);

        LogService::info('Onboarding ativado automaticamente', [
            'user_id' => $userId,
        ]);
    }
}
