<?php

declare(strict_types=1);

namespace Tests\Support;

trait SessionIsolation
{
    private function configureSessionStorage(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionPath = BASE_PATH . '/tests/.runtime/sessions';

        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }

        ini_set('session.save_path', $sessionPath);
    }

    protected function startIsolatedSession(string $prefix): void
    {
        $this->configureSessionStorage();

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_id($prefix . '-' . bin2hex(random_bytes(8)));
        session_start();
    }

    protected function resetSessionState(): void
    {
        $this->configureSessionStorage();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        $_SESSION = [];

        if (session_id() !== '') {
            session_id('');
        }
    }
}
