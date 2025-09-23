<?php

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;
use Application\Lib\Auth;


class CsrfMiddleware
{
    // Tempo de expiração de tokens em segundos (20 minutos por padrão)
    private const TOKEN_TTL = 1200;

    /**
     * Gera um novo token CSRF para um identificador específico (formulário).
     *
     * @param string $tokenId
     * @return string
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
            'time' => time()
        ];

        return $token;
    }

    /**
     * Valida o token CSRF para um identificador de formulário.
     *
     * @param string $token
     * @param string $tokenId
     * @return bool
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

        return hash_equals($stored['value'], $token);
    }

    /**
     * Middleware de validação global (usa 'default' como tokenId)
     *
     * @param Request $request
     * @throws ValidationException
     */
    public static function handle(Request $request, string $tokenId = 'default'): void
    {

        // file_put_contents('debug_session_id.txt', session_id());


        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            return;
        }

        // file_put_contents('debug_csrf.txt', print_r([
        //     'tokenId' => $tokenId,
        //     'session_tokens' => $_SESSION['csrf_tokens'] ?? null,
        //     'post_token' => $request->get('csrf_token'),
        //     'header_token' => $request->header('X-CSRF-TOKEN'),
        // ], true));

        $token = $request->get('csrf_token') ?: $request->header('X-CSRF-TOKEN');
        if (!self::validateToken((string)$token, $tokenId)) {
            // log detalhado


            LogService::warning('CSRF inválido', [
                'user_id'   => Auth::id(),
                'token_id'  => $tokenId,
                'expected'  => isset($_SESSION['csrf_tokens'][$tokenId]['value'])
                    ? substr($_SESSION['csrf_tokens'][$tokenId]['value'], 0, 8) . '…'
                    : null,
                'provided'  => isset($token)
                    ? substr($token, 0, 8) . '…'
                    : null,
                'url'       => ($_SERVER['REQUEST_METHOD'] ?? '-') . ' ' . ($_SERVER['REQUEST_URI'] ?? '-'),
                'has_cookie' => isset($_COOKIE[session_name()]),
            ]);

            throw new ValidationException([
                'csrf_token' => "Token CSRF inválido ou expirado (tokenId: $tokenId). Recarregue a página."
            ], 403);
        }
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
