<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class ContasController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/contas/index',
            [
                'pageTitle' => 'Contas',
                'subTitle' => 'Crie e gerencie suas contas',
            ]
        );
    }

    public function archived(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/contas/arquivadas',
            [
                'pageTitle' => 'Contas Arquivadas',
                'subTitle' => 'Contas que foram arquivadas',
            ]
        );
    }
}
