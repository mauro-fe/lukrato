<?php

namespace Application\Middlewares;

use Application\Lib\Auth;
use Application\Core\Request;
use Application\Models\Usuario;
use Application\Models\Conta;
use Application\Models\Lancamento;

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

        // Verificar se o usuário possui contas e lançamentos
        $temConta = Conta::where('user_id', $user->id)->exists();
        $temLancamento = Lancamento::where('user_id', $user->id)->exists();

        // Se não tem contas OU não tem lançamentos, redirecionar para onboarding
        if (!$temConta || !$temLancamento) {
            header('Location: ' . BASE_URL . 'onboarding');
            exit;
        }
    }
}
