<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class DashboardController extends BaseController
{

    public function dashboard(): void
    {
        $data = [
            'pageTitle' => 'Dashboard'
        ];

        $this->render(
            'admin/dashboard/index',
            $data,
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
