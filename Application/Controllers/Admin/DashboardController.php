<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class DashboardController extends BaseController
{
    public function dashboard()
    {
        $this->render(
            'dashboard/index',
            [
                'pageTitle' => 'Dashboard',
                'username'  => Auth::user()->username ?? '',
                'menu'      => 'dashboard',
            ],
            'admin/home/header',
            null
        );
    }
}
