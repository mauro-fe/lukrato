<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class FaturaController extends BaseController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/faturas/index',
            ['pageTitle' => 'Faturas de Cartão', 'subTitle' => 'Gerencie suas Faturas'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
