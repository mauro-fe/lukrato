<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\WebController;
use Application\Core\Response;

class BlogViewController extends WebController
{
    public function index(): Response
    {
        $this->requireAdminUser();

        return $this->renderResponse(
            'admin/sysadmin/blog',
            [
                'pageTitle' => 'Blog - Gerenciar Artigos',
                'menu' => 'super_admin',
                'skipPlanLimits' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
