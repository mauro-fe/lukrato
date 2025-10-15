<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;


class RelatoriosController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function view(): void
    {
        $this->requireAuth();
        $this->render(
            'admin/relatorios/relatorios',
            [],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
