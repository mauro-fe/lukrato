<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\LogService;
use Throwable;

class OnboardingController
{
    public function status(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            Response::success([
                'onboarding_completed' => (bool) $user->onboarding_completed_at,
                'tour_completed' => (bool) $user->tour_completed_at,
            ]);

        } catch (Throwable $e) {
            LogService::error('Erro ao verificar status do onboarding', [
                'error' => $e->getMessage()
            ]);

            Response::error('Erro interno', 500);
        }
    }
}
