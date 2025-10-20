<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function dashboard()
    {
        $this->render(
            'admin/dashboard/index',
            ['pageTitle' => 'Dashboard', 'subTitle' => 'Controle de Financeiro'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
