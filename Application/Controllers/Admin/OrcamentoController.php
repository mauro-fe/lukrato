<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class OrcamentoController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/orcamento/index',
            [
                'pageTitle' => 'Orçamento',
                'subTitle' => 'Controle seus gastos mensais',
                'menu' => 'orcamento',
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
