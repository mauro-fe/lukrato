<?php

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;

class CsrfMiddleware
{
    // Tempo de expiração de tokens em segundos (20 minutos por padrão)
    private const TOKEN_TTL = 1200;

    /**
     * Gera um novo token CSRF para um identificador específico (formulário).
     */
    public static function generateToken(string $tokenId = 'default'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        // Remove tokens expirados
        self::cleanupExpiredTokens();

        // Gera novo token e armazena com timestamp
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$tokenId] = [
            'value' => $token,
            'time'  => time(),
        ];

        return $token;
    }

    /**
     * Valida o token CSRF para um identificador de formulário.
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

        // Verifica validade e expiração
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
     * Middleware de validação global (usa 'default' como tokenId por padrão)
     *
     * @throws ValidationException
     */
    public static function handle(Request $request, string $tokenId = 'default'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Métodos "seguros"
        $method = strtoupper($request->method());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        // Extração tolerante de token (body form, header, json)
        [$token, $source] = self::extractToken($request);

        // Validação
        $valid = is_string($token) && self::validateToken($token, $tokenId);

        if (!$valid) {
            // Dados para log (sem vazar valor do token)
            $expected = $_SESSION['csrf_tokens'][$tokenId]['value'] ?? null;

            LogService::warning('CSRF bloqueado', [
                'tokenId'        => $tokenId,
                'method'         => $method,
                'path'           => $_SERVER['REQUEST_URI'] ?? null,
                'content_type'   => $_SERVER['CONTENT_TYPE'] ?? null,
                'has_token'      => $token ? true : false,
                'token_source'   => $source, // 'body', 'header', 'json', 'none'
                'expected_len'   => is_string($expected) ? strlen($expected) : null,
                'provided_len'   => is_string($token) ? strlen($token) : null,
                'reason'         => !isset($_SESSION['csrf_tokens'][$tokenId]) ? 'missing_expected'
                    : ($token ? 'mismatch_or_expired' : 'missing_client_token'),
                'session_id'     => session_id(),
                'user_id'        => $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
            ]);

            // Mensagem clara para o cliente
            throw new ValidationException([
                'csrf_token' => "Token CSRF inválido ou expirado (tokenId: {$tokenId}). Recarregue a página."
            ], 403);
        }
    }

    /**
     * Tenta extrair o token do request (form, header e json). Retorna [token, source].
     */
    private static function extractToken(Request $request): array
    {
        // 1) Tentativas em parâmetros (form/query)
        //   - aceita 'csrf_token' e '_token'
        $token = (string) ($request->get('csrf_token') ?? $request->get('_token') ?? '');

        if ($token !== '') {
            return [$token, 'body'];
        }

        // 2) Headers (variações comuns)
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
        // fallback direto do PHP (caso framework não normalize)
        $hv = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_string($hv) && $hv !== '') {
            return [$hv, 'header'];
        }

        // 3) JSON body (para fetch com application/json)
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
     * Remove tokens expirados da sessão
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
