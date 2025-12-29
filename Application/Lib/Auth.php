<?php

namespace Application\Lib;

use Application\Models\Usuario;

class Auth
{
    public const SESSION_TIMEOUT = 3600;

    public static function checkAdmin($expectedUsername = null)
    {
        if (!self::isLoggedIn()) {
            self::redirectToLogin();
        }

        if ($expectedUsername && !self::isAuthorized($expectedUsername)) {
            self::redirectToHome();
        }

        return self::id();
    }


    public static function isLoggedIn(): bool
    {
        $new = (isset($_SESSION['usuario_logged_in']) && $_SESSION['usuario_logged_in'] === true && isset($_SESSION['user_id']));
        $old = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['admin_id']));
        return $new || $old;
    }

    public static function isAuthorized(string $username): bool
    {
        return isset($_SESSION['admin_username']) && $_SESSION['admin_username'] === $username;
    }
    public static function user(): ?Usuario
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        $uid = self::id();
        if (!$uid) {
            return null;
        }

        // Cache novo
        if (!isset($_SESSION['usuario_cache']) || ($_SESSION['usuario_cache']['id'] ?? null) !== $uid) {
            $user = Usuario::find($uid);
            if (!$user) {
                self::logout();
                return null;
            }
            $_SESSION['usuario_cache'] = ['id' => $user->id, 'data' => $user];
        }

        return $_SESSION['usuario_cache']['data'] ?? null;
    }
    public static function id(): ?int
    {
        if (isset($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }
        if (isset($_SESSION['admin_id'])) {
            return (int) $_SESSION['admin_id'];
        }
        return null;
    }

    public static function username(): ?string
    {
        if (isset($_SESSION['usuario_nome'])) {
            return (string) $_SESSION['usuario_nome'];
        }
        if (isset($_SESSION['admin_username'])) {
            return (string) $_SESSION['admin_username'];
        }
        return null;
    }

    public static function login(Usuario $usuario): void
    {
        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id']        = $usuario->id;
        $_SESSION['usuario_nome']      = (string) ($usuario->nome ?? '');
        $_SESSION['login_time']        = time();
        $_SESSION['last_activity']     = time();

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $usuario->id;

        $nome = (string) ($usuario->nome ?? 'usuario');
        $adminUsername = strtolower(trim(preg_replace('/\s+/', '-', $nome)));
        $adminUsername = preg_replace('/[^a-z0-9\-_.]/', '', $adminUsername) ?: 'usuario';

        $_SESSION['admin_username'] = $adminUsername;

        unset($_SESSION['usuario_cache'], $_SESSION['admin_cache']);
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function checkActivity(int $timeout = self::SESSION_TIMEOUT): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ((time() - $lastActivity) > $timeout) {
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private static function redirectToLogin(): void
    {
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
    private static function redirectToHome(): void
    {
        header('Location: ' . BASE_URL . 'dashboard');
        exit;
    }
}
