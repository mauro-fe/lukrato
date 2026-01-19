<?php

// Application/Services/Auth/SessionManager.php
namespace Application\Services\Auth;

use Application\Contracts\Auth\SessionManagerInterface;
use Application\Models\Usuario;
use Application\Lib\Auth;
use Application\Lib\Helpers;

class SessionManager implements SessionManagerInterface
{
    public function createSession(Usuario $user): void
    {
        Auth::login($user);
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user->id;
        $_SESSION['usuario_nome'] = (string) ($user->nome ?? '');
        $_SESSION['admin_id'] ??= $_SESSION['user_id'];
        $_SESSION['admin_username'] ??= ($_SESSION['usuario_nome'] ?: 'usuario');

        // Log de depuração da sessão
        if (class_exists('Application\\Services\\LogService')) {
            \Application\Services\LogService::info('Sessão após login', [
                'session' => $_SESSION
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
