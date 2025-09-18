<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Usuario;

class ProfileController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        // força buscar do DB para garantir que venham cpf/telefone/data_nascimento/sexo
        $user = Usuario::find(Auth::id()) ?? Auth::user();

        // se quiser, atualize a sessão com o user completo:


        $this->render(
            'admin/profile/index',
            ['user' => $user],
            'admin/partials/header',
            null
        );
    }
}
