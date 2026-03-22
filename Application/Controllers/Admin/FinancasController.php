<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class FinancasController extends BaseController
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
