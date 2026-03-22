<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class CategoriaController extends BaseController
{
    public function index(?string $username = null): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/categorias/index',
            [
                'pageTitle' => 'Categorias',
                'subTitle' => 'Crie e gerencie suas categorias de Receitas e Despesas',
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
