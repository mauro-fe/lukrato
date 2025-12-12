<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;

class LandingController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'site/landing/index', // uma única página contendo todas as seções
            [
                'pageTitle' => 'Lukrato - Organize suas finanças',
                'extraCss'  => [], // css adicionais específicos da landing
                'extraJs'   => [], // js adicionais específicos da landing
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
