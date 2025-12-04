<?php

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;

class CsrfMiddleware
{
    // Token time to live in seconds (20 minutes)
    public const TOKEN_TTL = 1200;

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

        if (time() - $stored['time'] > self::TOKEN_TTL) {
            unset($_SESSION['csrf_tokens'][$tokenId]);
            return false;
        }

        return hash_equals((string) $stored['value'], (string) $token);
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
            return;
        }

        $expected = $_SESSION['csrf_tokens'][$tokenId]['value'] ?? null;

        LogService::warning('CSRF bloqueado', [
            'tokenId'        => $tokenId,
            'method'         => $method,
            'path'           => $_SERVER['REQUEST_URI'] ?? null,
            'content_type'   => $_SERVER['CONTENT_TYPE'] ?? null,
            'has_token'      => $token ? true : false,
            'token_source'   => $source,
            'expected_len'   => is_string($expected) ? strlen($expected) : null,
            'provided_len'   => is_string($token) ? strlen($token) : null,
            'reason'         => !isset($_SESSION['csrf_tokens'][$tokenId]) ? 'missing_expected'
                : ($token ? 'mismatch_or_expired' : 'missing_client_token'),
            'session_id'     => session_id(),
            'user_id'        => $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
        ]);

        throw new ValidationException([
            'csrf_token' => "Token CSRF invalido ou expirado (tokenId: {$tokenId}). Recarregue a pagina."
        ], 403);
    }

    /**
     * Tries to extract the token from form/query, headers or JSON body.
     *
     * @return array{0: string, 1: string}
     */
    private static function extractToken(Request $request): array
    {
        $token = (string) ($request->get('csrf_token') ?? $request->get('_token') ?? '');
        if ($token !== '') {
            return [$token, 'body'];
        }

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

        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $jToken = (string) ($json['csrf_token'] ?? $json['_token'] ?? '');
                if ($jToken !== '') {
                    return [$jToken, 'json'];
                }
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
