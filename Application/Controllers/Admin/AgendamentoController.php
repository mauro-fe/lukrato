<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\View;

class AgendamentoController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'admin/agendamentos/index',
            ['pageTitle' => 'Agendamentos', 'subTitle' => 'Gerencie seus agendamentos de contas a pagar'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
