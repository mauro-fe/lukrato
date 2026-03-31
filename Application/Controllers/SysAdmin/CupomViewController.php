<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\WebController;
use Application\Core\Response;

class CupomViewController extends WebController
{
    public function index(): Response
    {
        $this->requireAdminUser();

        return $this->renderResponse(
            'admin/sysadmin/cupons',
            [
                'pageTitle' => 'Gerenciar Cupons de Desconto',
                'menu' => 'cupons',
                'skipPlanLimits' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
