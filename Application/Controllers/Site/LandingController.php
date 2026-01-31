<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;
use Application\Models\Plano;

class LandingController extends BaseController
{
    public function index(): void
    {
        $planos = Plano::where('ativo', true)
            ->orderBy('preco_centavos', 'asc')
            ->get();

        $planoGratuito = $planos->firstWhere('preco_centavos', 0);
        $planosPagos   = $planos->where('preco_centavos', '>', 0);

        $this->render(
            'site/landing/index',
            [
                'pageTitle'       => 'Lukrato - Controle Financeiro Pessoal Grátis e Profissional',
                'pageDescription' => 'Controle suas finanças pessoais de forma simples. Plano gratuito disponível e planos pagos para quem quer ir além.',
                'extraCss'        => [],
                'extraJs'         => [],
                'planoGratuito'   => $planoGratuito,
                'planosPagos'     => $planosPagos,
                'temPlanoGratuito' => !is_null($planoGratuito),
                'temPlanosPagos'  => $planosPagos->isNotEmpty(),
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
