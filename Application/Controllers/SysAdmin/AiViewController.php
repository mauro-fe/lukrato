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

        $provider = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');
        $model = $provider === 'ollama'
            ? ($_ENV['OLLAMA_MODEL'] ?? 'gemma3:1b')
            : ($_ENV['AI_MODEL'] ?? 'gpt-4o-mini');

        $this->render(
            'admin/sysadmin/ai',
            [
                'pageTitle'      => 'Assistente IA - SysAdmin',
                'subTitle'       => 'Chat e ferramentas de inteligência artificial',
                'skipPlanLimits' => true,
                'aiProvider'     => strtoupper($provider),
                'aiModel'        => $model,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
