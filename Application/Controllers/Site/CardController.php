<?php

namespace Application\Controllers\Site;

use Application\Controllers\WebController;
use Application\Core\Response;

class CardController extends WebController
{
    public function index(): Response
    {
        return $this->renderResponse(
            'site/card/index',
            [
                'pageTitle' => 'Lukrato - Links',
                'extraCss' => [],
                'extraJs' => [],
            ]
        );
    }
}
