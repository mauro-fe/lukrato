<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class AgendamentoController extends BaseController
{

    public function index(): void
    {
        $data = [
            'pageTitle' => 'Agendamentos — Em Manutenção',
            'subTitle' => 'Esta funcionalidade está sendo aprimorada',
        ];

        $this->render(
            'admin/agendamentos/manutencao',
            $data,
            'admin/partials/header',
            'admin/partials/footer'
        );
    }


    public function create(): void
    {
        header('Location: ' . BASE_URL . 'agendamentos');
        exit;
    }


    public function edit(int $id): void
    {
        header('Location: ' . BASE_URL . 'agendamentos');
        exit;
    }
}
