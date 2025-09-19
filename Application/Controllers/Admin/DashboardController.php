<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class DashboardController extends BaseController
{
    public function dashboard()
    {
        $this->render(
            'admin/dashboard/index',
            [],
            'admin/partials/header',
            null
        );
    }
}
