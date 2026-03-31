<?php

namespace Application\Controllers\Admin;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Models\Usuario;

class LancamentoController extends WebController
{
    public function index(): Response
    {
        $userId = $this->requireUserId();
        $user = Usuario::find($userId);
        $isPro = $user ? $user->isPro() : false;

        return $this->renderResponse(
            'admin/lancamentos/index',
            [
                'pageTitle' => 'Transações',
                'subTitle' => 'Gerencie suas transações financeiras',
                'isPro' => $isPro,
                'showMonthSelector' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
