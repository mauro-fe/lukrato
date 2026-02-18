<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Models\Conta;
use Application\Models\Usuario;
use Application\Models\InstituicaoFinanceira;

class OnboardingController extends BaseController
{
   public function index()
{
    $this->requireAuth();

    $user = Usuario::find($this->userId);

    if ($user && $user->onboarding_completed_at) {
        $this->redirect('dashboard');
        return;
    }

    $temConta = Conta::where('user_id', $this->userId)->exists();

    if (!$temConta) {
        $instituicoes = InstituicaoFinanceira::orderBy('nome')->get();

        return $this->renderAdmin('admin.onboarding.index', [
            'instituicoes' => $instituicoes
        ]);
    }

    return $this->renderAdmin('admin.onboarding.lancamento');
}

}
