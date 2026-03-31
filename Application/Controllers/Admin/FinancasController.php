<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class FinancasController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/financas/index',
            [
                'pageTitle' => 'Finanças',
                'subTitle' => 'Gerencie seus orçamentos e metas financeiras',
                'menu' => 'financas',
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
