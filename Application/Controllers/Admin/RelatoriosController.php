<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class RelatoriosController extends WebController
{
    public function view(): Response
    {
        $user = $this->requireUser();
        $plan = $user->plan();

        return $this->renderAdminResponse(
            'admin/relatorios/index',
            [
                'pageTitle' => 'Relatórios',
                'subTitle' => 'Análise detalhada das suas finanças',
                'isPro' => $plan->isPro(),
                'showMonthSelector' => true,
            ]
        );
    }
}
