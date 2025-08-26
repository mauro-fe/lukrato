<?php

namespace Application\Controllers;

use Application\Controllers\BaseController;

class RelatoriosController extends BaseController
{
    public function __construct()
    {
        parent::__construct(); // inicializa auth/request/response
    }

    public function view(): void
    {
        $this->requireAuth();
        // Usa o helper do BaseController que injeta header/footer do admin
        $this->renderAdmin('admin/relatorios', [
            'username' => $this->adminUsername ?? 'usuário',
            'menu'     => 'relatorios', // deixa o item ativo no menu
        ]);
    }
}
