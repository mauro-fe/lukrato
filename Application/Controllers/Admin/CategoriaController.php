<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class CategoriaController extends BaseController
{
    public function index(?string $username = null): void
    {
        $this->requireAuth();

        $this->render(
            'admin/categorias/index',
            ['pageTitle' => 'Categorias', 'subTitle' => 'Crie e gerencie suas categorias'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
