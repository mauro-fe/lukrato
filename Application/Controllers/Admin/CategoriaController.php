<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class CategoriaController extends WebController
{
    public function index(?string $username = null): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/categorias/index',
            [
                'pageTitle' => 'Categorias',
                'subTitle' => 'Crie e gerencie suas categorias de Receitas e Despesas',
                'showMonthSelector' => true,
            ]
        );
    }
}
