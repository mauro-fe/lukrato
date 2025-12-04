<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Plano;

class BillingController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $plans = Plano::query()
            ->where('ativo', true)
            ->orderBy('preco_centavos')
            ->orderBy('id')
            ->get();
        $currentPlan = $user?->planoAtual();

        $this->render(
            'admin/billing/index',
            [
                'user' => $user,
                'plans' => $plans,
                'currentPlanCode' => $currentPlan?->code,
                'pageTitle' => 'Assinar Pro',
                'subTitle' => 'Assine o pro e tenha acesso a todas as funcionalidades'
            ],
            'admin/partials/header',
            'admin/partials/footer',
        );
    }
}
