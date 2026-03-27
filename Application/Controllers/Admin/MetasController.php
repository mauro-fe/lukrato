<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class MetasController extends BaseController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/metas/index',
            [
                'pageTitle' => 'Metas',
                'subTitle' => 'Planeje seus objetivos financeiros',
                'menu' => 'metas',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
