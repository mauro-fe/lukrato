<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class LancamentoController extends BaseController
{

    public function index(): void
    {
        $this->requireAuth();

        $this->render(
            'admin/lancamentos/index',
            ['pageTitle' => 'Lançamentos', 'subTitle' => 'Gerencie seus lançamentos'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}