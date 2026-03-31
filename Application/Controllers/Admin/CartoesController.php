<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class CartoesController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/cartoes/index',
            [
                'pageTitle' => 'Cartões de Crédito',
                'subTitle' => 'Gerencie seus cartões e controle seus gastos',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }

    public function archived(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/cartoes/arquivadas',
            [
                'pageTitle' => 'Cartões Arquivados',
                'subTitle' => 'Gerencie seus cartões arquivados',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
