<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

class PerfilController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/perfil/index',
            [
                'pageTitle' => 'Perfil',
                'subTitle' => 'Mantenha seus dados pessoais sempre atualizados',
            ]
        );
    }
}
