<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class LancamentoController extends BaseController
{
    public function index(): void
    {
        // Nada de query no backend: a listagem será carregada via JS pela API
        $data = ['pageTitle' => 'Lançamentos'];
        $this->render(
            'lancamentos/index',  // view
            $data,                // dados da view
            'admin/home/header',  // inclui o header (onde você pode deixar FAB + modais globais)
            null                  // sem footer extra (igual ao dashboard)
        );
    }
}
