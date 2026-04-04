<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\WebController;
use Application\Core\Response;

class BlogViewController extends WebController
{
    public function index(): Response
    {
        $this->requireAdminUser();

        return $this->renderAdminResponse(
            'admin/sysadmin/blog',
            [
                'pageTitle' => 'Blog - Gerenciar Artigos',
                'skipPlanLimits' => true,
            ]
        );
    }
}
