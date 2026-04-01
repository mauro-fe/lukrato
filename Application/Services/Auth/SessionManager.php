<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;

class SessionManager
{
    private const REMEMBER_LIFETIME = 60 * 60 * 24 * 30; // 30 dias

    public function createSession(Usuario $user, bool $remember = false): void
    {
        session_regenerate_id(true);
        Auth::login($user);

        if ($remember) {
            $_SESSION['remember_me'] = true;
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                time() + self::REMEMBER_LIFETIME,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        } else {
            unset($_SESSION['remember_me']);
        }

        if (class_exists(LogService::class)) {
            LogService::info('Sessao criada apos login', [
                'user_id' => (int) $user->id,
                'remember' => $remember,
            ]);
        }
    }

    public function destroySession(): void
    {
        Auth::logout();
    }

    public function isValid(): bool
    {
        return Auth::isLoggedIn() && Auth::checkActivity();
    }
}
