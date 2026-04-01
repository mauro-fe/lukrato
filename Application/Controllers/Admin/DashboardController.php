<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class DashboardController extends WebController
{
    public function dashboard(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/dashboard/index',
            [
                'pageTitle' => 'Dashboard',
                'showMonthSelector' => true,
            ]
        );
    }
}
