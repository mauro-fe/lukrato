<?php

namespace Application\Middlewares;

use Application\Lib\Auth;
use Application\Core\Request;
use Application\Models\Usuario;
use Application\Models\Conta;

class OnboardingMiddleware
{
    public static function handle(Request $request): void
    {
        if (!Auth::isLoggedIn()) {
            return;
        }

        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Admins do sistema não precisam de onboarding
        if ($user->is_admin) {
            return;
        }

        // Se o onboarding já foi concluído (inclusive via skip), liberar acesso
        if ($user->onboarding_completed_at !== null) {
            return;
        }

        // Verificar se o usuário possui pelo menos uma conta
        $temConta = Conta::where('user_id', $user->id)->exists();

        // Sem conta = precisa passar pelo onboarding
        if (!$temConta) {
            header('Location: ' . BASE_URL . 'onboarding');
            exit;
        }
    }
}
