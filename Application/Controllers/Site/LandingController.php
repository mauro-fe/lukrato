<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;

class LandingController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'site/landing/index',
            [
                'pageTitle' => 'Lukrato - Organize suas finanças',
                'menu'      => null,
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    public function plans(): void
    {
        $this->render(
            'site/landing/planos',
            [
                'pageTitle' => 'Planos - Lukrato',
                'menu'      => null,
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    public function whyFinance(): void
    {
        $this->render(
            'site/landing/why-finance',
            [
                'pageTitle' => 'Por que controlar suas finanças?',
                'menu'      => null,
            ],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
