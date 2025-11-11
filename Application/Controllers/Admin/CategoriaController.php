<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

/**
 * CategoriaController administra as rotas e views da área administrativa 
 * relacionadas a categorias (apenas interface).
 */
class CategoriaController extends BaseController
{
    /**
     * Exibe a página principal de listagem e gerenciamento de Categorias.
     * @param string|null $username Parâmetro de rota opcional para context-admin (mantido).
     */
    public function index(?string $username = null): void
    {
        $this->requireAuth(); // Garante que apenas usuários autenticados acessem a área admin

        $this->render(
            'admin/categorias/index', // View principal
            [
                'pageTitle' => 'Categorias', 
                'subTitle' => 'Crie e gerencie suas categorias de Receitas e Despesas'
            ],
            'admin/partials/header', // Partial Header
            'admin/partials/footer'  // Partial Footer
        );
    }
    
    // Poderiam ser adicionados métodos como create() e edit(int $id) aqui, 
    // seguindo o padrão do Admin Controller de Agendamento.
}