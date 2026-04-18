<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class CartoesController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/cartoes/index',
            [
                'pageTitle' => 'Cartões de Crédito',
                'subTitle' => 'Gerencie seus cartões e controle seus gastos',
            ]
        );
    }

    public function archived(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/cartoes/arquivadas',
            [
                'pageTitle' => 'Cartões Arquivados',
                'subTitle' => 'Gerencie seus cartões arquivados',
            ]
        );
    }

    public function show(int $id): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/cartoes/show',
            [
                'pageTitle' => 'Detalhes do Cartão',
                'subTitle' => 'Acompanhe fatura, evolução e parcelamentos',
                'cartaoId' => $id,
            ]
        );
    }
}
