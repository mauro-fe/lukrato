<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class FrontendPilotController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/frontend-pilot/index',
            [
                'menu' => 'perfil',
                'pageTitle' => 'Frontend Pilot',
                'subTitle' => 'Bootstrap autenticado consumindo os contratos API v1 já congelados',
            ]
        );
    }
}
