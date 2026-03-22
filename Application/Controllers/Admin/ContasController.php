<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class ContasController extends BaseController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/contas/index',
            [
                'pageTitle' => 'Contas',
                'subTitle' => 'Crie e gerencie suas contas',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }

    public function archived(): Response
    {
        return $this->renderResponse(
            'admin/contas/arquivadas',
            [
                'pageTitle' => 'Contas Arquivadas',
                'subTitle' => 'Contas que foram arquivadas',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
