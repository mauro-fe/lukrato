<?php

declare(strict_types=1);

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Exceptions\AuthException;
use Application\Lib\Auth;

/**
 * Middleware para verificar se o usuário é SysAdmin
 * 
 * Deve ser usado após AuthMiddleware para garantir que existe usuário logado.
 */
class SysadminMiddleware
{
    /**
     * Verifica se o usuário logado é administrador do sistema
     * 
     * @param Request $request
     * @throws AuthException Se não for admin
     */
    public function handle(Request $request): void
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new AuthException('Usuário não autenticado', 401);
        }
        
        if ((int)$user->is_admin !== 1) {
            throw new AuthException('Acesso restrito a administradores', 403);
        }
    }
}
