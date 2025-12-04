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

        $_SESSION['usuario_id'] = (int) $user->id;
        $_SESSION['usuario_nome'] = (string) ($user->nome ?? '');
        $_SESSION['admin_id'] ??= $_SESSION['usuario_id'];
        $_SESSION['admin_username'] ??= ($_SESSION['usuario_nome'] ?: 'usuario');
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
