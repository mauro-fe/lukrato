<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class LancamentoController extends WebController
{
    public function index(): Response
    {
        $user = $this->requireUser();

        return $this->renderAdminResponse(
            'admin/lancamentos/index',
            [
                'pageTitle' => 'Transações',
                'subTitle' => 'Gerencie suas transações financeiras',
                'isPro' => $user->isPro(),
                'showMonthSelector' => true,
            ]
        );
    }
}
