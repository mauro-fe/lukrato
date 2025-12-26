<?php

declare(strict_types=1);

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\View;
use Application\Lib\Auth;

/**
 * Controller para a página de parcelamentos (view)
 */
class ParcelamentoController extends BaseController
{
    /**
     * Exibe a página de gerenciamento de parcelamentos
     */
    public function index(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            redirectToLogin();
            return;
        }


        $this->render(
            'admin/parcelamentos/index',
            ['pageTitle' => 'Parcelamentos', 'subTitle' => 'Gerencie seus Parcelamentos'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
