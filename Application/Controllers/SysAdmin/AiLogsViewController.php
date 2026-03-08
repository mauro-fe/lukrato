<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class AiLogsViewController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();

        if (!$user || $user->is_admin != 1) {
            $this->redirect('login');
            return;
        }

        $this->render(
            'admin/sysadmin/ai-logs',
            [
                'pageTitle'      => 'Logs da IA - SysAdmin',
                'subTitle'       => 'Histórico de interações e métricas de uso da IA',
                'skipPlanLimits' => true,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
