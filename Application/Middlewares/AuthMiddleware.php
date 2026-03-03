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
     * Se não estiver → responde com 404 idêntico ao de rota inexistente (stealth).
     */
    public static function handle(Request $request): void
    {
        // Usuário não está logado → stealth 404
        if (!Auth::isLoggedIn()) {
            Auth::logout();
            Router::handleNotFound($request);
            return; // handleNotFound chama exit, mas por segurança
        }

        // Sessão expirada por inatividade → stealth 404
        if (!Auth::checkActivity()) {
            Auth::logout();
            Router::handleNotFound($request);
            return;
        }

        // Usuário logado e sessão ativa → requisição continua normalmente
    }
}
