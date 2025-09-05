<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class LancamentoController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'lancamentos/index',
            [
                'pageTitle' => 'Lançamentos',
                'menu'      => 'lancamentos',
                'username'  => Auth::user()->username ?? '',
            ],
            'admin/home/header',
            null
        );
    }
}
