<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;
use Application\Models\Plano;

class LandingController extends BaseController
{
    public function index(): void
    {
        // Busca os planos ativos do banco de dados
        $planos = Plano::where('ativo', true)
                       ->orderBy('preco_centavos', 'asc')
                       ->get();

        // Organiza os planos por tipo (gratuito e pagos)
        $planoGratuito = $planos->firstWhere('preco_centavos', 0);
        $planosPagos = $planos->where('preco_centavos', '>', 0);

        $this->render(
            'site/landing/index',
            [
                'pageTitle' => 'Lukrato - Organize suas finanças',
                'extraCss'  => [],
                'extraJs'   => [],
                'planoGratuito' => $planoGratuito,
                'planosPagos' => $planosPagos,
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
