<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;
use Application\Core\Response;

class LegalController extends BaseController
{
    public function terms(): Response
    {
        return $this->renderResponse(
            'site/legal/terms',
            [
                'pageTitle' => 'Termos de Uso | Lukrato - Controle Financeiro',
                'pageDescription' => 'Termos de uso da plataforma Lukrato. Leia as condições de uso do nosso serviço gratuito de controle financeiro pessoal.',
                'pageKeywords' => 'termos de uso lukrato, condições de uso, controle financeiro pessoal, política de uso app financeiro',
                'canonicalUrl' => rtrim(BASE_URL, '/') . '/termos',
                'extraCss' => ['legal'],
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    public function privacy(): Response
    {
        return $this->renderResponse(
            'site/legal/privacy',
            [
                'pageTitle' => 'Política de Privacidade | Lukrato - Controle Financeiro',
                'pageDescription' => 'Política de privacidade do Lukrato. Saiba como protegemos seus dados pessoais e financeiros no nosso app de controle financeiro.',
                'pageKeywords' => 'política de privacidade lukrato, proteção de dados, privacidade app financeiro, segurança dados financeiros',
                'canonicalUrl' => rtrim(BASE_URL, '/') . '/privacidade',
                'extraCss' => ['legal'],
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    public function lgpd(): Response
    {
        return $this->renderResponse(
            'site/legal/lgpd',
            [
                'pageTitle' => 'LGPD - Proteção de Dados Pessoais | Lukrato',
                'pageDescription' => 'Conformidade com a Lei Geral de Proteção de Dados (LGPD). Saiba como o Lukrato protege e trata seus dados pessoais e financeiros.',
                'pageKeywords' => 'lgpd lukrato, proteção de dados pessoais, lei geral de proteção de dados, privacidade financeira, segurança de dados',
                'canonicalUrl' => rtrim(BASE_URL, '/') . '/lgpd',
                'extraCss' => ['legal'],
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
