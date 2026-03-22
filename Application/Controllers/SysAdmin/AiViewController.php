<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;

class AiViewController extends BaseController
{
    public function index(): Response
    {
        $this->requireAdminUser();

        $provider = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');
        $model = $provider === 'ollama'
            ? ($_ENV['OLLAMA_MODEL'] ?? 'gemma3:1b')
            : ($_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini');

        return $this->renderResponse(
            'admin/sysadmin/ai',
            [
                'pageTitle' => 'Assistente IA - SysAdmin',
                'subTitle' => 'Chat e ferramentas de inteligência artificial',
                'skipPlanLimits' => true,
                'aiProvider' => strtoupper($provider),
                'aiModel' => $model,
            ],
            'admin/partials/header',
            'admin/partials/footer'
        );
    }
}
