<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class PerfilController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/perfil/index',
            [
                'menu' => 'perfil',
                'perfilViewMode' => 'perfil',
                'pageTitle' => 'Perfil',
                'subTitle' => 'Mantenha seus dados pessoais sempre atualizados',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
