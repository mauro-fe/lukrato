<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class AgendamentoController extends BaseController
{

    public function index(): void
    {
        $data = [
            'pageTitle' => 'Agendamentos', 
            'subTitle' => 'Gerencie seus agendamentos de contas a pagar/receber',
        ];
        
      
        $this->render(
            'admin/agendamentos/index', 
            $data,                      
            'admin/partials/header',  
            'admin/partials/footer'   
        );
    }
    

    public function create(): void
    {
        $this->render(
            'admin/agendamentos/create',
            ['pageTitle' => 'Novo Agendamento', 'subTitle' => 'Cadastrar novo agendamento'],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }

  
    public function edit(int $id): void
    {

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