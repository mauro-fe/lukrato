<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Middlewares\CsrfMiddleware;

class SecurityController
{
    public function refreshCsrf(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $tokenId = $this->resolveTokenId();
        $token = CsrfMiddleware::generateToken($tokenId);

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
