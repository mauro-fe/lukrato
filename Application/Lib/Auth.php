<?php

namespace Application\Lib;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Models\Usuario;

class Auth
{
    public const SESSION_TIMEOUT = 3600; // 1 hora padrao
    public const REMEMBER_TIMEOUT = 2592000; // 30 dias se marcou 'lembrar de mim'

    private static ?Usuario $resolvedUser = null;
    private static ?int $resolvedUserId = null;
    private static ?string $resolvedSessionId = null;
    private static $userResolver = null;
    private static $defaultUserResolver = null;

    /**
     * Retorna o timeout apropriado baseado no remember_me
     */
    public static function getSessionTimeout(): int
    {
        return !empty($_SESSION['remember_me']) ? self::REMEMBER_TIMEOUT : self::SESSION_TIMEOUT;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    public static function isAuthorized(string $username): bool
    {
        $currentUsername = self::username();

        return $currentUsername !== null && hash_equals($currentUsername, $username);
    }

    public static function user(): ?Usuario
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        $userId = self::id();
        if (!$userId) {
            return null;
        }

        if (
            self::$resolvedUser !== null
            && self::$resolvedUserId === $userId
            && self::$resolvedSessionId === session_id()
        ) {
            return self::$resolvedUser;
        }

        $resolver = self::$userResolver ?? self::$defaultUserResolver;
        $user = $resolver !== null ? $resolver($userId) : Usuario::find($userId);

        if (!$user) {
            self::logout();
            return null;
        }

        self::$resolvedUser = $user;
        self::$resolvedUserId = (int) $user->id;
        self::$resolvedSessionId = session_id();

        return self::$resolvedUser;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function username(): ?string
    {
        $user = self::user();

        return $user ? self::buildUsername($user) : null;
    }

    public static function login(Usuario $usuario): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        self::forgetResolvedUser();

        $_SESSION['user_id'] = (int) $usuario->id;
        $_SESSION['last_activity'] = time();
        unset(
            $_SESSION['remember_me'],
            $_SESSION['usuario_logged_in'],
            $_SESSION['usuario_nome'],
            $_SESSION['login_time'],
            $_SESSION['admin_logged_in'],
            $_SESSION['admin_id'],
            $_SESSION['admin_username'],
            $_SESSION['usuario_cache'],
            $_SESSION['admin_cache']
        );

        $secure = self::isSecureConnection();
        setcookie('lukrato_known_user', '1', [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function logout(): void
    {
        self::forgetResolvedUser();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function checkActivity(?int $timeout = null): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $effectiveTimeout = $timeout ?? self::getSessionTimeout();

        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ((time() - $lastActivity) > $effectiveTimeout) {
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

    public static function resolveUserUsing(?callable $resolver): void
    {
        self::$userResolver = $resolver;
        self::forgetResolvedUser();
    }

    public static function setDefaultUserResolver(?callable $resolver): void
    {
        self::$defaultUserResolver = $resolver;
        self::forgetResolvedUser();
    }

    private static function buildUsername(Usuario $usuario): string
    {
        $name = (string) ($usuario->nome ?? 'usuario');
        $username = strtolower(trim((string) preg_replace('/\s+/', '-', $name)));

        return preg_replace('/[^a-z0-9\\-_.]/', '', $username) ?: 'usuario';
    }

    private static function forgetResolvedUser(): void
    {
        self::$resolvedUser = null;
        self::$resolvedUserId = null;
        self::$resolvedSessionId = null;
    }

    private static function isSecureConnection(): bool
    {
        if (self::isDirectSecureConnection()) {
            return true;
        }

        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!self::isTrustedProxy($remoteAddr)) {
            return false;
        }

        $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        if ($forwardedProto !== '') {
            return in_array($forwardedProto, ['https', 'wss'], true);
        }

        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
            return true;
        }

        if (strtolower((string) ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '')) === 'on') {
            return true;
        }

        $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');

        return $cfVisitor !== '' && str_contains(strtolower($cfVisitor), '"scheme":"https"');
    }

    private static function isDirectSecureConnection(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    private static function isTrustedProxy(string $remoteAddr): bool
    {
        $trustedProxies = self::runtimeConfig()->trustedProxies();

        return $remoteAddr !== '' && in_array($remoteAddr, $trustedProxies, true);
    }

    private static function runtimeConfig(): InfrastructureRuntimeConfig
    {
        return ApplicationContainer::tryMake(InfrastructureRuntimeConfig::class)
            ?? new InfrastructureRuntimeConfig();
    }
}
