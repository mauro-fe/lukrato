<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class FaturaController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/faturas/index',
            [
                'pageTitle' => 'Faturas de Cartão',
                'subTitle' => 'Gerencie suas Faturas',
            ]
        );
    }

    public function show(int $id): Response
    {
        $this->requireUserId();

        if ($id <= 0) {
            return $this->buildRedirectResponse('faturas');
        }

        return $this->renderAdminResponse(
            'admin/faturas/show',
            [
                'pageTitle' => 'Detalhes da Fatura',
                'subTitle' => 'Acompanhe itens, pagamentos e ajustes',
                'faturaId' => $id,
            ]
        );
    }
}
