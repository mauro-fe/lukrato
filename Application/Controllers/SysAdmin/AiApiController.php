<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Admin\AiAdminWorkflowService;

class AiApiController extends ApiController
{
    public function __construct(
        private readonly AiAdminWorkflowService $workflowService = new AiAdminWorkflowService()
    ) {
        parent::__construct();
    }

    public function healthProxy(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->healthProxy(),
            'Erro ao processar operacao de IA.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_ai']
        );
    }

    public function quota(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->quota(),
            'Erro ao processar operacao de IA.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_ai']
        );
    }

    public function chat(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->chat($this->getRequestPayload()),
            'Erro ao processar operacao de IA.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_ai']
        );
    }

    public function suggestCategory(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->suggestCategory($this->userId ?? 0, $this->getRequestPayload()),
            'Erro ao processar operacao de IA.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_ai']
        );
    }

    public function analyzeSpending(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->analyzeSpending($this->userId ?? 0, $this->getRequestPayload()),
            'Erro ao processar operacao de IA.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_ai']
        );
    }
}
