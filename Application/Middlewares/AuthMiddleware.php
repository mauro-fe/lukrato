<?php

namespace Application\Middlewares;

use Application\Lib\Auth;
use Application\Core\Request;
use Application\Core\Router;

/**
 * Middleware de autenticação com Stealth Mode.
 *
 * Em vez de retornar 401 ou redirecionar para /login (o que revelaria
 * que a rota existe), retorna exatamente a mesma resposta 404 que o
 * Router usa para rotas inexistentes. Para um usuário não autenticado,
 * rotas protegidas são indistinguíveis de rotas que não existem.
 *
 * A detecção de sessão expirada no frontend é feita pelo SessionManager
 * via endpoint público /api/session/status (que NÃO passa por este middleware).
 */
class AuthMiddleware
{
    /**
     * Verifica se o usuário está logado.
     * Se não estiver e já fez login antes → redireciona para login.
     * Se nunca logou → responde com 404 (stealth).
     */
    public static function handle(Request $request): void
    {
        // Usuário não está logado
        if (!Auth::isLoggedIn()) {
            Auth::logout();
            self::redirectOrStealth($request);
            return;
        }

        // Sessão expirada por inatividade → redireciona para login
        if (!Auth::checkActivity()) {
            Auth::logout();
            self::redirectToLogin($request);
            return;
        }

        // Usuário logado e sessão ativa → requisição continua normalmente
    }

    /**
     * Se o usuário já fez login antes (cookie presente), redireciona para login.
     * Caso contrário, mostra 404 stealth.
     */
    private static function redirectOrStealth(Request $request): void
    {
        if (!empty($_COOKIE['lukrato_known_user'])) {
            self::redirectToLogin($request);
            return;
        }

        Router::handleNotFound($request);
    }

    /**
     * Redireciona para a página de login (JSON 401 para API/AJAX).
     */
    private static function redirectToLogin(Request $request): void
    {
        if ($request->wantsJson() || $request->isAjax()) {
            \Application\Core\Response::unauthorized('Sessão expirada');
            return;
        }

        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}
