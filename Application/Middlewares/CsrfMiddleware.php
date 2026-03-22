<?php

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\Infrastructure\LogService;

class CsrfMiddleware
{
    // Token time to live in seconds (60 minutes - renovação automática a cada 10 min)
    public const TOKEN_TTL = 3600;

    /**
     * Exposes the configured TTL so other layers know when tokens expire.
     */
    public static function ttl(): int
    {
        return self::TOKEN_TTL;
    }

    /**
     * Generates a new CSRF token for a specific identifier (form).
     */
    public static function generateToken(string $tokenId = 'default'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        self::cleanupExpiredTokens();

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$tokenId] = [
            'value' => $token,
            'time'  => time(),
        ];

        return $token;
    }

    /**
     * Validates a token for the given identifier.
     */
    public static function validateToken(string $token, string $tokenId = 'default'): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_tokens'][$tokenId])) {
            return false;
        }

        $stored = $_SESSION['csrf_tokens'][$tokenId];

        if (!is_array($stored) || !isset($stored['value'], $stored['time'])) {
            return false;
        }

        $age = time() - $stored['time'];
        if ($age > self::TOKEN_TTL) {
            unset($_SESSION['csrf_tokens'][$tokenId]);
            return false;
        }

        return hash_equals((string) $stored['value'], (string) $token);
    }

    /**
     * Retorna o tempo restante (em segundos) para o token expirar.
     * Retorna 0 se não existe ou já expirou.
     */
    public static function getTokenRemainingTtl(string $tokenId = 'default'): int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_tokens'][$tokenId])) {
            return 0;
        }

        $stored = $_SESSION['csrf_tokens'][$tokenId];
        if (!is_array($stored) || !isset($stored['time'])) {
            return 0;
        }

        $age = time() - $stored['time'];
        $remaining = self::TOKEN_TTL - $age;

        return max(0, $remaining);
    }

    /**
     * Middleware entry point (defaults to tokenId "default").
     *
     * @throws ValidationException
     */
    public static function handle(Request $request, string $tokenId = 'default'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        [$token, $source] = self::extractToken($request);
        $valid = is_string($token) && self::validateToken($token, $tokenId);

        if ($valid) {
            // ✅ Token válido - NÃO regenerar, manter o mesmo token
            return;
        }

        $storedTime = $_SESSION['csrf_tokens'][$tokenId]['time'] ?? null;
        $age = $storedTime ? (time() - $storedTime) : null;

        LogService::warning('CSRF bloqueado', [
            'tokenId'        => $tokenId,
            'method'         => $method,
            'path'           => $_SERVER['REQUEST_URI'] ?? null,
            'content_type'   => $_SERVER['CONTENT_TYPE'] ?? null,
            'has_token'      => $token ? true : false,
            'token_source'   => $source,
            'token_age_seconds' => $age,
            'ttl_remaining' => $age !== null ? max(0, self::TOKEN_TTL - $age) : null,
            'reason'         => !isset($_SESSION['csrf_tokens'][$tokenId]) ? 'missing_expected'
                : ($token ? 'mismatch_or_expired' : 'missing_client_token'),
            'user_id'        => $_SESSION['user_id'] ?? null,
        ]);

        $remainingTtl = self::getTokenRemainingTtl($tokenId);

        throw new ValidationException(
            errors: [
                'csrf_token' => "Token CSRF invalido ou expirado (tokenId: {$tokenId}). Recarregue a pagina.",
                'csrf_expired' => true,
                'remaining_ttl' => $remainingTtl
            ],
            message: 'CSRF token invalid or expired',
            code: 419 // 419 = Session Expired (Laravel convention)
        );
    }

    /**
     * Tries to extract the token from headers or request body.
     * Query string is intentionally ignored to avoid leaks via URL, logs and referer.
     *
     * @return array{0: string, 1: string}
     */
    private static function extractToken(Request $request): array
    {
        $headersToCheck = [
            'X-CSRF-TOKEN',
            'X-CSRF-Token',
            'X-Csrf-Token',
        ];
        foreach ($headersToCheck as $h) {
            $hv = $request->header($h);
            if (is_string($hv) && $hv !== '') {
                return [$hv, 'header'];
            }
        }

        $hv = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_string($hv) && $hv !== '') {
            return [$hv, 'header'];
        }

        $token = (string) ($request->post('csrf_token') ?? $request->post('_token') ?? '');
        if ($token !== '') {
            return [$token, 'body'];
        }

        $json = $request->json();
        if (is_array($json)) {
            $jsonToken = (string) ($json['csrf_token'] ?? $json['_token'] ?? '');
            if ($jsonToken !== '') {
                return [$jsonToken, 'json'];
            }
        }

        return ['', 'none'];
    }

    /**
     * Removes expired tokens from the session.
     */
    private static function cleanupExpiredTokens(): void
    {
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            return;
        }

        foreach ($_SESSION['csrf_tokens'] as $id => $data) {
            if (!isset($data['time']) || time() - $data['time'] > self::TOKEN_TTL) {
                unset($_SESSION['csrf_tokens'][$id]);
            }
        }
    }
}
