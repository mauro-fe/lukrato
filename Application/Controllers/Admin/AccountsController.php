<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class AccountsController extends BaseController
{
    public function index(): void
    {
        $this->render('admin/contas/index', [
            'pageTitle' => 'Contas',
            'menu' => 'contas'
        ], 'admin/partials/header', null);
    }

    public function archived(): void
    {
        $this->render('admin/contas/arquivadas', [
            'pageTitle' => 'Contas',
            'menu' => 'contas'
        ], 'admin/partials/header', null);
    }
}
