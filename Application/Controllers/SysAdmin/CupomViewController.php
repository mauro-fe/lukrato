<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class CupomViewController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();

        // Verificar se é admin
        if (!$user || $user->is_admin != 1) {
            $this->redirect('login');
            return;
        }

        $this->render(
            'sysAdmin/cupons',
            [
                'pageTitle' => 'Gerenciar Cupons de Desconto',
                'menu' => 'cupons',
                'skipPlanLimits' => true
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
