<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;

/**
 * Tela dedicada de configurações da conta.
 */
class ConfigController extends BaseController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderResponse(
            'admin/perfil/index',
            [
                'menu' => 'configuracoes',
                'perfilViewMode' => 'configuracoes',
                'pageTitle' => 'Configurações',
                'subTitle' => 'Ajuste segurança, integrações e preferências da conta',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
