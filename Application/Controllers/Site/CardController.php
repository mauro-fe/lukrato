<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;

class CardController extends BaseController
{
    public function index(): void
    {
        $this->render(
            'site/card/index',
            [
                'pageTitle' => 'Lukrato - Links',
                'extraCss'  => [],
                'extraJs'   => [],
            ],
            null, // Sem header
            null  // Sem footer
        );
    }
}
