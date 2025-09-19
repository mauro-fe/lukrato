<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Usuario;

class ProfileController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $user = Usuario::find(Auth::id()) ?? Auth::user();

        $this->render(
            'admin/profile/index',
            ['user' => $user],
            'admin/partials/header',
            null
        );
    }
}
