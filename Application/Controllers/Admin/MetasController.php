<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class MetasController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/metas/index',
            [
                'pageTitle' => 'Metas',
                'subTitle' => 'Planeje seus objetivos financeiros',
            ]
        );
    }
}
