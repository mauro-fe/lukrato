<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

/**
 * ContasController administra as rotas e views da área administrativa 
 * relacionadas a contas financeiras (apenas interface).
 */
class ContasController extends BaseController
{
    /**
     * Exibe a página principal de listagem de contas ativas.
     */
    public function index(): void
    {
        // Garante autenticação (se necessário no BaseController ou na rota)
        // $this->requireAuth(); 

        $this->render(
            'admin/contas/index', 
            [
                'pageTitle' => 'Contas', 
                'subTitle' => 'Crie e gerencie suas contas'
            ], 
            'admin/partials/header', 
            'admin/partials/footer'
        );
    }

    /**
     * Exibe a página de contas arquivadas.
     */
    public function archived(): void
    {
        // Garante autenticação (se necessário no BaseController ou na rota)
        // $this->requireAuth(); 

        $this->render(
            'admin/contas/arquivadas', 
            [
                'pageTitle' => 'Contas Arquivadas',
                'subTitle' => 'Contas que foram arquivadas'
            ], 
            'admin/partials/header', 
            'admin/partials/footer'
        );
    }
}