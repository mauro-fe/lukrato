<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class DashboardController extends BaseController
{
    public function dashboard(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/dashboard/index',
            [
                'pageTitle' => 'Dashboard',
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
