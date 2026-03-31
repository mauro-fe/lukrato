<?php

declare(strict_types=1);

namespace Application\Controllers\Concerns;

use Application\Core\Exceptions\AuthException;
use Application\Lib\Auth;
use Application\Models\Usuario;

trait HandlesAuthGuards
{
    protected function requireAuth(): void
    {
        if (!Auth::isLoggedIn()) {
            $this->throwRedirectResponse('login');
        }

        $this->userId = Auth::id();
        $user = Auth::user();
        $this->adminUsername = $user?->nome ?: ($user?->email ?: 'usuario');

        if (empty($this->userId)) {
            $this->auth->logout();
            $this->throwRedirectResponse('login');
        }
    }

    protected function requireAuthApiOrFail(): void
    {
        if (!Auth::isLoggedIn()) {
            throw new AuthException('Nao autenticado', 401);
        }

        $this->userId = Auth::id();
        $user = Auth::user();
        $this->adminUsername = $user?->nome ?: ($user?->email ?: 'usuario');

        if (empty($this->userId)) {
            $this->auth->logout();
            throw new AuthException('Sessao invalida', 401);
        }
    }

    protected function requireUserId(): int
    {
        $this->requireAuth();

        return (int) $this->userId;
    }

    protected function requireUser(): Usuario
    {
        $this->requireAuth();
        $user = Auth::user();

        if (!$user) {
            $this->auth->logout();
            $this->throwRedirectResponse('login');
        }

        return $user;
    }

    protected function requireAdminUser(): Usuario
    {
        $user = $this->requireUser();

        if ((int) ($user->is_admin ?? 0) !== 1) {
            $this->throwRedirectResponse('login');
        }

        return $user;
    }

    protected function requireApiUserIdOrFail(): int
    {
        $this->requireAuthApiOrFail();

        return (int) $this->userId;
    }

    protected function requireApiUserOrFail(): Usuario
    {
        $this->requireAuthApiOrFail();
        $user = Auth::user();

        if (!$user) {
            $this->auth->logout();
            throw new AuthException('Sessao invalida', 401);
        }

        return $user;
    }

    protected function requireApiUserIdAndReleaseSessionOrFail(): int
    {
        $userId = $this->requireApiUserIdOrFail();
        $this->releaseSession();

        return $userId;
    }

    protected function requireApiUserAndReleaseSessionOrFail(): Usuario
    {
        $user = $this->requireApiUserOrFail();
        $this->releaseSession();

        return $user;
    }

    protected function requireApiAdminUserOrFail(string $message = 'Acesso negado'): Usuario
    {
        $user = $this->requireApiUserOrFail();

        if ((int) ($user->is_admin ?? 0) !== 1) {
            throw new AuthException($message, 403);
        }

        return $user;
    }

    protected function requireApiAdminUserAndReleaseSessionOrFail(string $message = 'Acesso negado'): Usuario
    {
        $user = $this->requireApiAdminUserOrFail($message);
        $this->releaseSession();

        return $user;
    }

    protected function isAuthenticated(): bool
    {
        return Auth::isLoggedIn();
    }

    protected function releaseSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
