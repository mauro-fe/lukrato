<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class OrcamentoController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/orcamento/index',
            [
                'pageTitle' => 'Orçamento',
                'subTitle' => 'Controle seus gastos mensais',
                'menu' => 'orcamento',
                'showMonthSelector' => true,
            ]
        );
    }
}
