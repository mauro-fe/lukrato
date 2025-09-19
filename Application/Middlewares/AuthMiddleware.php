<?php

namespace Application\Middlewares;

use Application\Lib\Auth;
use Application\Core\Request;
use Application\Core\Exceptions\AuthException;

class AuthMiddleware
{
    /**
     * Verifica se o admin está logado e lança exceção se não estiver.
     */
    public static function handle(Request $request): void // Aceita a instância de Request
    {
        // NOTA: session_start() já deve ter sido chamado em public/index.php

        if (!Auth::isLoggedIn()) {
            throw new AuthException('Acesso não autorizado. Por favor, faça login.', 401);
        }

        // Se o admin está logado, também verifica a inatividade da sessão
        if (!Auth::checkActivity(Auth::SESSION_TIMEOUT)) {
            throw new AuthException('Sessão expirada por inatividade. Faça login novamente.', 419);
        }

        // Se o usuário está logado e ativo, a requisição continua.
        // Aqui você pode adicionar lógica de autorização mais granular, se for necessário em todas as rotas protegidas.
    }
}
