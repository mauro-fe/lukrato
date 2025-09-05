<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class ProfileController extends BaseController
{
    public function index(): void
    {
        if (method_exists($this, 'requireAuth')) {
            $this->requireAuth();
        }

        $user = Auth::user();

        $this->render(
            'admin/profile/index',
            [
                'pageTitle' => 'Meu Perfil',
                'menu'      => 'perfil',
                'user'      => $user,
            ],
            'admin/home/header',
            null
        );
    }
}
