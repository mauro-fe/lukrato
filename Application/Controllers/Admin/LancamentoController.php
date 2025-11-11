<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

/**
 * LancamentoController administra a rota e a view da listagem de lançamentos
 * na área administrativa.
 */
class LancamentoController extends BaseController
{
    /**
     * Exibe a página de gerenciamento de lançamentos.
     * * @return void
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $this->render(
            'admin/lancamentos/index', 
            ['pageTitle' => 'Lançamentos', 'subTitle' => 'Gerencie seus lançamentos'], 
            'admin/partials/header', 
            'admin/partials/footer'
        );
    }
}