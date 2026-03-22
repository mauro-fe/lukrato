<?php

declare(strict_types=1);

namespace Application\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Core\Router;
use Application\Lib\Auth;

/**
 * Middleware para verificar se o usuário é SysAdmin (Stealth Mode).
 *
 * Deve ser usado após AuthMiddleware.
 * Retorna 404 em vez de 403 para não revelar que a rota existe
 * para usuários sem permissão de administrador.
 */
class SysadminMiddleware
{
    public function handle(Request $request): void
    {
        $user = Auth::user();

        if (!$user || (int) $user->is_admin !== 1) {
            throw new HttpResponseException(Router::notFoundResponse($request));
        }
    }
}
