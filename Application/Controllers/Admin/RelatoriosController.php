<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;


class RelatoriosController extends BaseController
{

    public function view(): void
    {
        $this->requireAuth();

        $this->render(
            'admin/relatorios/index',
            ['pageTitle' => 'Relatórios', 'subTitle' => 'Análise detalhada das suas finanças'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}