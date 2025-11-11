<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

/**
 * DashboardController administra a rota e a view da página inicial (Dashboard) 
 * da área administrativa.
 */
class DashboardController extends BaseController
{
    /**
     * Exibe a página principal (Home) do painel de administração.
     * * Este método chama o renderizador de view com os títulos e partials necessários.
     * * @return void
     */
    public function dashboard(): void
    {
        // Define o payload de dados para a view
        $data = [
            'pageTitle' => 'Dashboard', 
            'subTitle' => 'Controle de Financeiro'
        ];
        
        $this->render(
            'admin/dashboard/index',    // View principal a ser carregada
            $data,                      // Dados passados para a view
            'admin/partials/header',    // Partial do cabeçalho
            'admin/partials/footer'     // Partial do rodapé
        );
    }
}