<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Middlewares\CsrfMiddleware;

/**
 * Controller para gerenciamento de sessão do usuário.
 *
 * Fornece endpoints para verificar status da sessão,
 * renovar sessão e validar autenticação.
 */
class SessionController extends ApiController
{
    private const WARNING_THRESHOLD = 300;

    private function getSessionLifetime(): int
    {
        return Auth::getSessionTimeout();
    }

    /**
     * Verifica o status atual da sessão.
     *
     * Retorna informações sobre:
     * - Se o usuário está autenticado
     * - Tempo restante da sessão
     * - Se deve exibir aviso de expiração
     * - Se a sessão está expirada (permite renovação)
     *
     * NOTA: Esta rota não usa middleware auth para permitir
     * verificação mesmo quando a sessão está próxima de expirar.
     */
    public function status(): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $isRemembered = !empty($_SESSION['remember_me']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->releaseSession();
        }

        if (!$userId) {
            return Response::errorResponse('Usuário não autenticado', 401, [
                'authenticated' => false,
                'expired' => true,
                'remainingTime' => 0,
                'showWarning' => false,
                'canRenew' => false,
            ]);
        }

        $sessionLifetime = $this->getSessionLifetime();
        $sessionAge = time() - $lastActivity;
        $remainingTime = max(0, $sessionLifetime - $sessionAge);
        $isExpired = $remainingTime <= 0;

        $gracePeriod = 1800;
        $canRenew = !$isExpired || ($sessionAge <= $sessionLifetime + $gracePeriod);

        $showWarning = ($remainingTime > 0 && $remainingTime <= self::WARNING_THRESHOLD)
            || ($isExpired && $canRenew);

        $userName = 'Usuário';
        if ($canRenew) {
            $user = Auth::user();
            if ($user) {
                $userName = $user->nome ?? $user->email ?? 'Usuário';
            }
        }

        return Response::successResponse([
            'authenticated' => !$isExpired,
            'expired' => $isExpired,
            'remainingTime' => $remainingTime,
            'showWarning' => $showWarning,
            'canRenew' => $canRenew,
            'warningThreshold' => self::WARNING_THRESHOLD,
            'sessionLifetime' => $sessionLifetime,
            'userName' => $userName,
            'isRemembered' => $isRemembered,
        ]);
    }

    /**
     * Renova a sessão do usuário.
     *
     * Atualiza o timestamp de última atividade,
     * regenera o ID da sessão por segurança e
     * retorna um novo token CSRF.
     *
     * NOTA: Esta rota não usa middleware auth para permitir
     * renovação durante o grace period (30 minutos após expirar).
     */
    public function renew(): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return Response::errorResponse('Sessão inválida. Por favor, faça login novamente.', 401);
        }

        $sessionLifetime = $this->getSessionLifetime();
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $sessionAge = time() - $lastActivity;
        $gracePeriod = 1800;

        if ($sessionAge > $sessionLifetime + $gracePeriod) {
            return Response::errorResponse('Sessão expirada há muito tempo. Por favor, faça login novamente.', 401);
        }

        $_SESSION['last_activity'] = time();

        if (!headers_sent()) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }

        $newToken = CsrfMiddleware::generateToken('default');

        return Response::successResponse([
            'newToken' => $newToken,
            'remainingTime' => $sessionLifetime,
            'expiresAt' => date('Y-m-d H:i:s', time() + $sessionLifetime),
        ], 'Sessão renovada com sucesso');
    }

    /**
     * Realiza heartbeat para manter sessão ativa.
     *
     * Endpoint leve para verificar se sessão ainda é válida
     * e renovar automaticamente se o usuário estiver ativo.
     */
    public function heartbeat(): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $user = Auth::user();

        if (!$user) {
            return Response::errorResponse('Não autenticado', 401);
        }

        $sessionLifetime = $this->getSessionLifetime();
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $sessionAge = time() - $lastActivity;

        if ($sessionAge < $sessionLifetime) {
            $_SESSION['last_activity'] = time();
        }

        return Response::successResponse([
            'alive' => true,
            'remainingTime' => max(0, $sessionLifetime - $sessionAge),
        ]);
    }
}
