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
                'showMonthSelector' => true,
            ]
        );
    }

    public function sugestaoInteligente(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/orcamento/sugestao-inteligente',
            [
                'pageTitle' => 'Sugestão Inteligente',
                'subTitle' => 'Revise e aplique limites sugeridos para o mês',
                'showMonthSelector' => true,
                'menu' => 'orcamento',
                'hideLaunchFab' => true,
                'backUrl' => rtrim(BASE_URL, '/') . '/orcamento',
                'backLabel' => 'Voltar para orçamento',
                'currentPageJsViewId' => 'admin-orcamento-index',
            ]
        );
    }
}
