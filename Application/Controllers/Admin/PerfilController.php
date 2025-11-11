<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

/**
 * PerfilController administra a rota e a view da página de perfil
 * na área administrativa.
 */
class PerfilController extends BaseController
{
    /**
     * Exibe a página de gerenciamento de perfil.
     * * @return void
     */
    public function index(): void
    {
        $this->requireAuth();

        $this->render(
            'admin/perfil/index',
            ['pageTitle' => 'Perfil', 'subTitle' => 'Mantenha seu perfil sempre atualizado'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}