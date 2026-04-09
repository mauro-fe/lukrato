<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Config\AiRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Controllers\WebController;
use Application\Core\Response;

class AiViewController extends WebController
{
    public function index(): Response
    {
        $this->requireAdminUser();

        $runtimeConfig = $this->runtimeConfig();
        $provider = $runtimeConfig->provider();
        $model = $provider === 'ollama'
            ? ($runtimeConfig->configuredOllamaModel() ?? 'gemma3:1b')
            : $runtimeConfig->openAiModel();

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

    private function runtimeConfig(): AiRuntimeConfig
    {
        return ApplicationContainer::resolveOrNew(null, AiRuntimeConfig::class);
    }
}
