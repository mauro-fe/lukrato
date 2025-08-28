<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class LancamentoController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'lancamentos/index',
            ['pageTitle' => 'Lançamentos', 'menu' => 'lancamentos'],
            'admin/home/header',
            null
        );
    }
}
