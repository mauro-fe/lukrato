<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Usuario;

class LancamentoController extends BaseController
{

    public function index(): void
    {
        $this->requireAuth();

        $user = Usuario::find(Auth::id());
        $isPro = $user ? $user->isPro() : false;

        $this->render(
            'admin/lancamentos/index',
            [
                'pageTitle' => 'Lançamentos',
                'subTitle' => 'Gerencie seus lançamentos',
                'isPro' => $isPro,
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
