<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class ContasController extends BaseController
{
    public function index(): void
    {
        $this->render('admin/contas/index', [], 'admin/partials/header', null);
    }

    public function archived(): void
    {
        $this->render('admin/contas/arquivadas', [], 'admin/partials/header', null);
    }
}
