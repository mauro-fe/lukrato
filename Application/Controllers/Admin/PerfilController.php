<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class PerfilController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();

        $this->render(
            'admin/perfil/index',
            ['user' => $user], 
            'admin/partials/header',
            null
        );
    }
}
