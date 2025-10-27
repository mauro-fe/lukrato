<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class BillingController extends BaseController
{
    // GET /billing
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $this->render(
            'admin/billing/index',
            ['user' => $user, 'pageTitle' => 'Assinar Pro', 'subTitle' => 'Assine o pro e tenha acesso a todas as funcionalidades'],
            'admin/partials/header',
            'admin/partials/footer',
        );
    }
}
