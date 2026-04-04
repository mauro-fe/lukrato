<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;

/**
 * Tela dedicada de configurações da conta.
 */
class ConfigController extends WebController
{
    public function index(): Response
    {
        $this->requireUserId();

        return $this->renderAdminResponse(
            'admin/configuracoes/index',
            [
                'pageTitle' => 'Configurações',
                'subTitle' => 'Ajuste segurança, integrações e preferências da conta',
            ]
        );
    }
}
