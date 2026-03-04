<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class BlogViewController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();

        if (!$user || $user->is_admin != 1) {
            $this->redirect('login');
            return;
        }

        $this->render(
            'admin/sysadmin/blog',
            [
                'pageTitle'      => 'Blog - Gerenciar Artigos',
                'menu'           => 'super_admin',
                'skipPlanLimits' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
