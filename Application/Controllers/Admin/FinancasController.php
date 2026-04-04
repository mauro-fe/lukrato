<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class FinancasController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/financas/index',
            [
                'pageTitle' => 'Finanças',
                'subTitle' => 'Gerencie seus orçamentos e metas financeiras',
                'showMonthSelector' => true,
            ]
        );
    }
}
