<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;

class LegalController extends BaseController
{
    public function terms()
    {
        return $this->render(
            'site/legal/terms',
            [
                'pageTitle'       => 'Termos de Uso | Lukrato – Controle Financeiro',
                'pageDescription' => 'Termos de uso da plataforma Lukrato. Leia as condições de uso do nosso serviço gratuito de controle financeiro pessoal.',
                'pageKeywords'    => 'termos de uso lukrato, condições de uso, controle financeiro pessoal, política de uso app financeiro',
                'canonicalUrl'    => rtrim(BASE_URL, '/') . '/termos',
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    public function privacy()
    {
        return $this->render(
            'site/legal/privacy',
            [
                'pageTitle'       => 'Política de Privacidade | Lukrato – Controle Financeiro',
                'pageDescription' => 'Política de privacidade do Lukrato. Saiba como protegemos seus dados pessoais e financeiros no nosso app de controle financeiro.',
                'pageKeywords'    => 'política de privacidade lukrato, proteção de dados, privacidade app financeiro, segurança dados financeiros',
                'canonicalUrl'    => rtrim(BASE_URL, '/') . '/privacidade',
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    public function lgpd()
    {
        return $this->render(
            'site/legal/lgpd',
            [
                'pageTitle'       => 'LGPD – Proteção de Dados Pessoais | Lukrato',
                'pageDescription' => 'Conformidade com a Lei Geral de Proteção de Dados (LGPD). Saiba como o Lukrato protege e trata seus dados pessoais e financeiros.',
                'pageKeywords'    => 'lgpd lukrato, proteção de dados pessoais, lei geral de proteção de dados, privacidade financeira, segurança de dados',
                'canonicalUrl'    => rtrim(BASE_URL, '/') . '/lgpd',
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
