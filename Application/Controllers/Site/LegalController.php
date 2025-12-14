<?php

namespace Application\Controllers\Site;

use Application\Controllers\BaseController;

class LegalController extends BaseController
{
    public function terms()
    {
        return $this->render(
            'site/legal/terms',          // view do conteúdo
            [],
            'site/partials/header',      // header da LANDING / SITE
            'site/partials/footer'       // footer da LANDING / SITE
        );
    }

    public function privacy()
    {
        return $this->render(
            'site/legal/privacy',
            [],
            'site/partials/header',
            'site/partials/footer'
        );
    }

    public function lgpd()
    {
        return $this->render(
            'site/legal/lgpd',
            [],
            'site/partials/header',
            'site/partials/footer'
        );
    }
}
