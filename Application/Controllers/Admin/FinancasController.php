<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class FinancasController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->render(
            'admin/financas/index',
            [
                'pageTitle' => 'Finanças',
                'subTitle'  => 'Gerencie seus orçamentos e metas financeiras',
                'menu'      => 'financas',
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
