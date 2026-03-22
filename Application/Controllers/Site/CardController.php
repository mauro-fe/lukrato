<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;
use Application\Core\Response;

class CardController extends BaseController
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
