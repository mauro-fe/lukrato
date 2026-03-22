<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class PerfilController extends BaseController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/perfil/index',
            ['pageTitle' => 'Perfil', 'subTitle' => 'Mantenha seu perfil sempre atualizado'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
