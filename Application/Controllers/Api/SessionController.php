<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Middlewares\CsrfMiddleware;

/**
 * Controller para gerenciamento de sessão do usuário
 * 
 * Fornece endpoints para verificar status da sessão,
 * renovar sessão e validar autenticação.
 */
class SessionController
{
    /**
     * Tempo máximo de sessão em segundos (usa o mesmo do Auth)
     */
    private const SESSION_LIFETIME = Auth::SESSION_TIMEOUT;

    /**
     * Tempo para exibir aviso de expiração (5 minutos antes)
     */
    private const WARNING_THRESHOLD = 300;

    /**
     * Verifica o status atual da sessão
     * 
     * Retorna informações sobre:
     * - Se o usuário está autenticado
     * - Tempo restante da sessão
     * - Se deve exibir aviso de expiração
     * - Se a sessão está expirada (permite renovação)
     * 
     * NOTA: Esta rota NÃO usa middleware auth para permitir
     * verificação mesmo quando a sessão está próxima de expirar
     */
    public function status(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Verifica se usuário está logado (apenas pela sessão, sem validar timeout)
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::json([
                'authenticated' => false,
                'expired' => true,
                'message' => 'Usuário não autenticado',
                'remainingTime' => 0,
                'showWarning' => false,
                'canRenew' => false,
            ], 401);
            return;
        }

        // Calcula tempo restante da sessão (usa last_activity como no Auth)
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $sessionAge = time() - $lastActivity;
        $remainingTime = max(0, self::SESSION_LIFETIME - $sessionAge);

        // Verifica se a sessão está expirada
        $isExpired = $remainingTime <= 0;

        // Permite renovação se expirou há menos de 30 minutos (grace period)
        $gracePeriod = 1800; // 30 minutos
        $canRenew = !$isExpired || ($sessionAge <= self::SESSION_LIFETIME + $gracePeriod);

        // Mostra aviso se está nos últimos 5 minutos OU se acabou de expirar mas pode renovar
        $showWarning = ($remainingTime > 0 && $remainingTime <= self::WARNING_THRESHOLD)
            || ($isExpired && $canRenew);

        // Busca dados do usuário apenas se a sessão ainda é válida ou pode renovar
        $userName = 'Usuário';
        if ($canRenew) {
            $user = Auth::user();
            if ($user) {
                $userName = $user->nome ?? $user->email ?? 'Usuário';
            }
        }

        Response::json([
            'authenticated' => !$isExpired,
            'expired' => $isExpired,
            'remainingTime' => $remainingTime,
            'showWarning' => $showWarning,
            'canRenew' => $canRenew,
            'warningThreshold' => self::WARNING_THRESHOLD,
            'sessionLifetime' => self::SESSION_LIFETIME,
            'userName' => $userName,
        ]);
    }

    /**
     * Renova a sessão do usuário
     * 
     * Atualiza o timestamp de última atividade,
     * regenera o ID da sessão por segurança e
     * retorna um novo token CSRF.
     * 
     * NOTA: Esta rota NÃO usa middleware auth para permitir
     * renovação durante o grace period (30 minutos após expirar)
     */
    public function renew(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Verifica se há um user_id na sessão (mesmo que expirada)
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            Response::json([
                'success' => false,
                'message' => 'Sessão inválida. Por favor, faça login novamente.',
            ], 401);
            return;
        }

        // Verifica se ainda está dentro do grace period
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $sessionAge = time() - $lastActivity;
        $gracePeriod = 1800; // 30 minutos após expirar

        if ($sessionAge > self::SESSION_LIFETIME + $gracePeriod) {
            // Passou do grace period, não pode mais renovar
            Response::json([
                'success' => false,
                'message' => 'Sessão expirada há muito tempo. Por favor, faça login novamente.',
            ], 401);
            return;
        }

        // Atualiza timestamp de última atividade (mesma chave do Auth)
        $_SESSION['last_activity'] = time();

        // Regenera ID da sessão por segurança
        if (!headers_sent()) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }

        // Gera novo token CSRF
        $newToken = CsrfMiddleware::generateToken('default');

        Response::json([
            'success' => true,
            'message' => 'Sessão renovada com sucesso',
            'newToken' => $newToken,
            'remainingTime' => self::SESSION_LIFETIME,
            'expiresAt' => date('Y-m-d H:i:s', time() + self::SESSION_LIFETIME),
        ]);
    }

    /**
     * Realiza heartbeat para manter sessão ativa
     * 
     * Endpoint leve para verificar se sessão ainda é válida
     * sem renovar completamente.
     */
    public function heartbeat(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $user = Auth::user();

        if (!$user) {
            Response::json(['alive' => false], 401);
            return;
        }

        // Atualiza timestamp apenas se estiver dentro do período válido
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $sessionAge = time() - $lastActivity;

        if ($sessionAge < self::SESSION_LIFETIME) {
            $_SESSION['last_activity'] = time();
        }

        Response::json(['alive' => true]);
    }
}
