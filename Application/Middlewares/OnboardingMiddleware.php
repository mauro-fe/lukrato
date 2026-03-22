<?php

namespace Application\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
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

        if ($user->is_admin) {
            return;
        }

        if ($user->onboarding_completed_at !== null) {
            return;
        }

        $temConta = Conta::where('user_id', $user->id)->exists();

        if (!$temConta) {
            throw new HttpResponseException(Response::redirectResponse(BASE_URL . 'onboarding'));
        }
    }
}
