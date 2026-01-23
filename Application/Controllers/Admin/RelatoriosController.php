<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Usuario;


class RelatoriosController extends BaseController
{

    public function view(): void
    {
        $this->requireAuth();

        $user = Usuario::find(Auth::id());
        $isPro = $user ? $user->isPro() : false;

        $this->render(
            'admin/relatorios/index',
            [
                'pageTitle' => 'Relatórios',
                'subTitle' => 'Análise detalhada das suas finanças',
                'isPro' => $isPro,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
