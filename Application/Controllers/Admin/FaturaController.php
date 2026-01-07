<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\View;
use Application\Lib\Auth;

/**
 * Controller para a página de faturas de cartão (view)
 */
class FaturaController extends BaseController
{
    /**
     * Exibe a página de gerenciamento de faturas de cartão
     */
    public function index(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            redirectToLogin();
            return;
        }


        $this->render(
            'admin/faturas/index',
            ['pageTitle' => 'Faturas de Cartão', 'subTitle' => 'Gerencie suas Faturas'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
