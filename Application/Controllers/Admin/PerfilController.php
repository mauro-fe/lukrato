<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class PerfilController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $this->render(
            'admin/perfil/index',
            ['pageTitle' => 'Perfil', 'subTitle' => 'Mantenha seu perfil sempre atualizado'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
