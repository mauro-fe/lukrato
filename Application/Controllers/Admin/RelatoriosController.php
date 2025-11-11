<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

/**
 * RelatoriosController administra a rota e a view da página de relatórios 
 * na área administrativa.
 */
class RelatoriosController extends BaseController
{
    /**
     * Exibe a página de visualização de relatórios.
     * * @return void
     */
    public function view(): void
    {
        $this->requireAuth();
        
        $this->render(
            'admin/relatorios/relatorios',
            ['pageTitle' => 'Relatórios', 'subTitle' => 'Gerencie seus gastos'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}