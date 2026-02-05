<?php

// Application/Services/Auth/SessionManager.php
namespace Application\Services\Auth;

use Application\Contracts\Auth\SessionManagerInterface;
use Application\Models\Usuario;
use Application\Lib\Auth;
use Application\Lib\Helpers;

class SessionManager implements SessionManagerInterface
{
    // Tempo de sessão: 30 dias para "lembrar de mim", 2 horas padrão
    private const REMEMBER_LIFETIME = 60 * 60 * 24 * 30; // 30 dias
    private const DEFAULT_LIFETIME = 60 * 60 * 2; // 2 horas

    public function createSession(Usuario $user, bool $remember = false): void
    {
        Auth::login($user);
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user->id;
        $_SESSION['usuario_nome'] = (string) ($user->nome ?? '');
        $_SESSION['admin_id'] ??= $_SESSION['user_id'];
        $_SESSION['admin_username'] ??= ($_SESSION['usuario_nome'] ?: 'usuario');

        // Configurar tempo de sessão baseado no "lembrar de mim"
        if ($remember) {
            $_SESSION['remember_me'] = true;
            $lifetime = self::REMEMBER_LIFETIME;

            // Configurar cookie com vida longa
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                time() + $lifetime,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Log de depuração da sessão
        if (class_exists('Application\\Services\\LogService')) {
            \Application\Services\LogService::info('Sessão após login', [
                'session' => $_SESSION,
                'remember' => $remember
            ]);
        } else {
            error_log('Sessão após login: ' . print_r($_SESSION, true));
        }
    }

    public function destroySession(): void
    {
        Auth::logout();
    }

    public function isValid(): bool
    {
        return Auth::isLoggedIn() && Auth::checkActivity(Auth::SESSION_TIMEOUT);
    }
}
