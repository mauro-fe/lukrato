<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\LogService;

class SecurityController
{
    public function refreshCsrf(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $tokenId = $this->resolveTokenId();
        $oldToken = $_SESSION['csrf_tokens'][$tokenId]['value'] ?? null;

        $token = CsrfMiddleware::generateToken($tokenId);

        LogService::info('CSRF token renovado', [
            'token_id' => $tokenId,
            'session_id' => session_id(),
            'old_token_prefix' => $oldToken ? substr($oldToken, 0, 8) : 'none',
            'new_token_prefix' => substr($token, 0, 8),
            'user_id' => $_SESSION['user_id'] ?? null,
        ]);

        // Forçar escrita da sessão para garantir que o novo token esteja persistido
        // antes de enviar a resposta ao cliente
        session_write_close();

        Response::json([
            'status'   => 'ok',
            'token'    => $token,
            'token_id' => $tokenId,
            'ttl'      => CsrfMiddleware::ttl(),
        ]);
    }

    private function resolveTokenId(): string
    {
        $tokenId = 'default';

        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $payload = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($payload)) {
                $tokenId = $payload['token_id'] ?? $payload['tokenId'] ?? $tokenId;
            }
        }

        if (isset($_REQUEST['token_id']) && is_string($_REQUEST['token_id']) && $_REQUEST['token_id'] !== '') {
            $tokenId = $_REQUEST['token_id'];
        }

        $tokenId = is_string($tokenId) ? trim($tokenId) : 'default';
        $tokenId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tokenId) ?: 'default';

        return $tokenId;
    }
}
