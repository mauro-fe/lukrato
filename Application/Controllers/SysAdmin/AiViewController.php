<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class AiViewController extends BaseController
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
            'admin/sysadmin/ai',
            [
                'pageTitle'      => 'Assistente IA - SysAdmin',
                'subTitle'       => 'Chat e ferramentas de inteligência artificial',
                'skipPlanLimits' => true,
                'aiProvider'     => strtoupper($_ENV['AI_PROVIDER'] ?? 'openai'),
                'aiModel'        => $_ENV['AI_MODEL'] ?? 'gpt-4o-mini',
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
