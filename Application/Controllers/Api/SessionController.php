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
     */
    public function status(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Verifica se usuário está logado
        $user = Auth::user();

        if (!$user) {
            Response::json([
                'authenticated' => false,
                'message' => 'Sessão expirada ou usuário não autenticado',
                'remainingTime' => 0,
                'showWarning' => false,
            ], 401);
            return;
        }

        // Calcula tempo restante da sessão (usa last_activity como no Auth)
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $sessionAge = time() - $lastActivity;
        $remainingTime = max(0, self::SESSION_LIFETIME - $sessionAge);
        $showWarning = $remainingTime > 0 && $remainingTime <= self::WARNING_THRESHOLD;

        Response::json([
            'authenticated' => true,
            'remainingTime' => $remainingTime,
            'showWarning' => $showWarning,
            'warningThreshold' => self::WARNING_THRESHOLD,
            'sessionLifetime' => self::SESSION_LIFETIME,
            'userName' => $user->nome ?? $user->email ?? 'Usuário',
        ]);
    }

    /**
     * Renova a sessão do usuário
     * 
     * Atualiza o timestamp de última atividade,
     * regenera o ID da sessão por segurança e
     * retorna um novo token CSRF.
     */
    public function renew(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Verifica se usuário está logado
        $user = Auth::user();

        if (!$user) {
            Response::json([
                'success' => false,
                'message' => 'Sessão expirada. Por favor, faça login novamente.',
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
