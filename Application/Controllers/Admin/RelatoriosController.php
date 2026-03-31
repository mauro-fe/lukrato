<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Models\Usuario;

class RelatoriosController extends WebController
{
    public function view(): Response
    {
        $userId = $this->requireUserId();
        $user = Usuario::find($userId);
        $isPro = $user ? $user->isPro() : false;

        return $this->renderResponse(
            'admin/relatorios/index',
            [
                'pageTitle' => 'Relatórios',
                'subTitle' => 'Análise detalhada das suas finanças',
                'isPro' => $isPro,
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
