<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class CartoesController extends BaseController
{
    /**
     * Página principal de cartões de crédito
     */
    public function index(): void
    {
        $this->requireAuth();

        $this->render(
            'admin/cartoes/index',
            [
                'pageTitle' => 'Cartões de Crédito',
                'subTitle' => 'Gerencie seus cartões e controle seus gastos'
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }

    /**
     * Página de cartões arquivados
     */
    public function archived(): void
    {
        $this->requireAuth();

        $this->render(
            'admin/cartoes/arquivadas',
            [
                'pageTitle' => 'Cartões Arquivados',
                'subTitle' => 'Gerencie seus cartões arquivados'
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
