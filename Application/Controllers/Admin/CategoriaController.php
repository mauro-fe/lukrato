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
            [],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
