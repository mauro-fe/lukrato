<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\Infrastructure\LogService;

class SecurityController extends ApiController
{
    public function refreshCsrf(): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $tokenId = $this->resolveTokenId();
        $oldToken = $_SESSION['csrf_tokens'][$tokenId]['value'] ?? null;

        $token = CsrfMiddleware::generateToken($tokenId);

        LogService::info('CSRF token renovado', [
            'token_id' => $tokenId,
            'user_id' => $_SESSION['user_id'] ?? null,
            'rotated' => $oldToken !== null,
        ]);

        // Forçar escrita da sessão para garantir que o novo token esteja persistido
        // antes de enviar a resposta ao cliente
        $this->releaseSession();

        return Response::successResponse([
            'token'    => $token,
            'token_id' => $tokenId,
            'ttl'      => CsrfMiddleware::ttl(),
        ]);
    }

    private function resolveTokenId(): string
    {
        $tokenId = 'default';

        $payload = $this->getJson();
        if (is_array($payload) && $payload !== []) {
            $tokenId = $payload['token_id'] ?? $payload['tokenId'] ?? $tokenId;
        }

        if (isset($_REQUEST['token_id']) && is_string($_REQUEST['token_id']) && $_REQUEST['token_id'] !== '') {
            $tokenId = $_REQUEST['token_id'];
        }

        $tokenId = is_string($tokenId) ? trim($tokenId) : 'default';
        $tokenId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tokenId) ?: 'default';

        return $tokenId;
    }
}
