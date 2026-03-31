<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class DashboardController extends WebController
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
