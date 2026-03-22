<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Plano;

class LandingController extends BaseController
{
    public function index(): Response
    {
        $planos = Plano::where('ativo', true)
            ->orderBy('preco_centavos', 'asc')
            ->get();

        $planoGratuito = $planos->firstWhere('preco_centavos', 0);
        $planosPagos = $planos->where('preco_centavos', '>', 0);

        return $this->renderResponse(
            'site/landing/index',
            [
                'pageTitle' => 'Lukrato - App de Controle Financeiro Pessoal Gratuito | Organize suas Finanças',
                'pageDescription' => 'O Lukrato é o melhor app de controle financeiro pessoal gratuito brasileiro. Organize suas finanças pessoais, controle gastos do cartão de crédito, crie orçamentos e acompanhe despesas. Comece grátis!',
                'pageKeywords' => 'controle financeiro pessoal, app de controle financeiro gratuito brasileiro, como organizar finanças pessoais 2026, planilha de gastos mensais gratuita, controle de gastos, orçamento pessoal, finanças pessoais, como economizar dinheiro, app financeiro gratuito, gerenciador financeiro, como controlar gastos do cartão de crédito, sistema financeiro pessoal, controle de despesas, planejamento financeiro pessoal',
                'canonicalUrl' => rtrim(BASE_URL, '/') . '/',
                'isLandingPage' => true,
                'extraCss' => [],
                'extraJs' => [],
                'planoGratuito' => $planoGratuito,
                'planosPagos' => $planosPagos,
                'temPlanoGratuito' => !is_null($planoGratuito),
                'temPlanosPagos' => $planosPagos->isNotEmpty(),
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
