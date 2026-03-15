<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

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
     * Retorna o tempo de sessão apropriado (considera remember_me)
     */
    private function getSessionLifetime(): int
    {
        return Auth::getSessionTimeout();
    }

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

        // Lê todos os dados necessários da sessão de uma vez
        $userId = $_SESSION['user_id'] ?? null;
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $isRemembered = !empty($_SESSION['remember_me']);

        // Liberar lock da sessão para permitir requisições paralelas
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (!$userId) {
            Response::error('Usuário não autenticado', 401, [
                'authenticated' => false,
                'expired' => true,
                'remainingTime' => 0,
                'showWarning' => false,
                'canRenew' => false,
            ]);
            return;
        }

        // Obtém tempo de sessão apropriado (considera remember_me)
        $sessionLifetime = $this->getSessionLifetime();

        // Calcula tempo restante da sessão (usa last_activity como no Auth)
        $sessionAge = time() - $lastActivity;
        $remainingTime = max(0, $sessionLifetime - $sessionAge);

        // Verifica se a sessão está expirada
        $isExpired = $remainingTime <= 0;

        // Permite renovação se expirou há menos de 30 minutos (grace period)
        $gracePeriod = 1800; // 30 minutos
        $canRenew = !$isExpired || ($sessionAge <= $sessionLifetime + $gracePeriod);

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

        Response::success([
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
            Response::error('Sessão inválida. Por favor, faça login novamente.', 401);
            return;
        }

        // Obtém tempo de sessão apropriado (considera remember_me)
        $sessionLifetime = $this->getSessionLifetime();

        // Verifica se ainda está dentro do grace period
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $sessionAge = time() - $lastActivity;
        $gracePeriod = 1800; // 30 minutos após expirar

        if ($sessionAge > $sessionLifetime + $gracePeriod) {
            // Passou do grace period, não pode mais renovar
            Response::error('Sessão expirada há muito tempo. Por favor, faça login novamente.', 401);
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

        Response::success([
            'newToken' => $newToken,
            'remainingTime' => $sessionLifetime,
            'expiresAt' => date('Y-m-d H:i:s', time() + $sessionLifetime),
        ], 'Sessão renovada com sucesso');
    }

    /**
     * Realiza heartbeat para manter sessão ativa
     * 
     * Endpoint leve para verificar se sessão ainda é válida
     * e renovar automaticamente se o usuário estiver ativo.
     */
    public function heartbeat(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $user = Auth::user();

        if (!$user) {
            Response::error('Não autenticado', 401);
            return;
        }

        // Obtém tempo de sessão apropriado (considera remember_me)
        $sessionLifetime = $this->getSessionLifetime();

        // Atualiza timestamp - isso mantém a sessão viva enquanto usuário está ativo
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $sessionAge = time() - $lastActivity;

        if ($sessionAge < $sessionLifetime) {
            $_SESSION['last_activity'] = time();
        }

        Response::success([
            'alive' => true,
            'remainingTime' => max(0, $sessionLifetime - $sessionAge),
        ]);
    }
}
