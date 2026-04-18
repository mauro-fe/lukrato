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

    public function templates(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/metas/templates',
            [
                'pageTitle' => 'Templates de Metas',
                'subTitle' => 'Escolha um modelo e crie sua meta rapidamente',
                'menu' => 'metas',
                'hideLaunchFab' => true,
                'backUrl' => rtrim(BASE_URL, '/') . '/metas',
                'backLabel' => 'Voltar para metas',
                'currentPageJsViewId' => 'admin-metas-index',
            ]
        );
    }
}
