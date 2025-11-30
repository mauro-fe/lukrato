<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;


class ContasController extends BaseController
{

    public function index(): void
    {
        $this->render(
            'admin/contas/index',
            [
                'pageTitle' => 'Contas',
                'subTitle' => 'Crie e gerencie suas contas'
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }

    public function archived(): void
    {

        $this->render(
            'admin/contas/arquivadas',
            [
                'pageTitle' => 'Contas Arquivadas',
                'subTitle' => 'Contas que foram arquivadas'
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}