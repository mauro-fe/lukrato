<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Core\View; // Mantido para referência, mas a lógica de renderização deve vir de BaseController

/**
 * AgendamentoController administra as rotas e views da área administrativa 
 * relacionadas a Agendamentos.
 */
class AgendamentoController extends BaseController
{
    /**
     * Exibe a página principal de listagem e gerenciamento de Agendamentos.
     * * @return void
     */
    public function index(): void
    {
        // Define os dados (payload) que serão passados para o template/view.
        $data = [
            'pageTitle' => 'Agendamentos', 
            'subTitle' => 'Gerencie seus agendamentos de contas a pagar/receber',
            // Futuramente, pode incluir a lista de agendamentos: 'agendamentos' => Agendamento::all(),
        ];
        
        // O método 'render' é herdado do BaseController (ou View)
        // e é responsável por carregar a view principal e os partials (header/footer).
        $this->render(
            'admin/agendamentos/index', // View principal
            $data,                      // Dados para a View
            'admin/partials/header',    // Partial Header
            'admin/partials/footer'     // Partial Footer
        );
    }
    
    // --- Novos Métodos Sugeridos para a Área Administrativa ---
    
    /**
     * Exibe o formulário para criar um novo agendamento.
     * * @return void
     */
    public function create(): void
    {
        $this->render(
            'admin/agendamentos/create',
            ['pageTitle' => 'Novo Agendamento', 'subTitle' => 'Cadastrar novo agendamento'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }

    /**
     * Exibe o formulário de edição para um agendamento específico.
     * * @param int $id O ID do agendamento a ser editado (Tipagem de argumento PHP 7.4+).
     * @return void
     */
    public function edit(int $id): void
    {
        // Lógica futura: buscar o agendamento
        // $agendamento = Agendamento::find($id);

        $this->render(
            'admin/agendamentos/edit',
            [
                'pageTitle' => 'Editar Agendamento', 
                'subTitle' => "Editando Agendamento #$id",
                // 'agendamento' => $agendamento,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}