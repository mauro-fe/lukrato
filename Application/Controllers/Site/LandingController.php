<?php

declare(strict_types=1);

namespace Application\Controllers\Site;

use Application\Controllers\WebController;
use Application\Core\Response;

class LandingController extends WebController
{
    public function index(): Response
    {
        return $this->renderResponse(
            'site/landing/index',
            [
                'pageTitle' => 'Lukrato — Pare de Perder Dinheiro Sem Perceber | Controle Financeiro com IA',
                'pageDescription' => 'Descubra para onde seu dinheiro vai. O Lukrato mostra em segundos se você gasta mais do que ganha, com IA, dashboard e gamificação. Grátis para começar.',
                'pageKeywords' => 'controle financeiro pessoal, app de controle financeiro gratuito brasileiro, como organizar finanças pessoais 2026, planilha de gastos mensais gratuita, controle de gastos, orçamento pessoal, finanças pessoais, como economizar dinheiro, app financeiro gratuito, gerenciador financeiro, como controlar gastos do cartão de crédito, sistema financeiro pessoal, controle de despesas, planejamento financeiro pessoal',
                'canonicalUrl' => rtrim(BASE_URL, '/') . '/',
                'isLandingPage' => true,
                'extraCss' => ['landing'],
                'extraJs' => [],
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
