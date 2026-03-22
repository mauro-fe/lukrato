<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class AiLogsViewController extends BaseController
{
    public function index(): Response
    {
        $this->requireAdminUser();

        return $this->renderResponse(
            'admin/sysadmin/ai-logs',
            [
                'pageTitle' => 'Logs da IA - SysAdmin',
                'subTitle' => 'Histórico de interações e métricas de uso da IA',
                'skipPlanLimits' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
