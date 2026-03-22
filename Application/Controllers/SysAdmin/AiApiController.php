<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Admin\AiAdminWorkflowService;

class AiApiController extends BaseController
{
    public function __construct(
        private readonly AiAdminWorkflowService $workflowService = new AiAdminWorkflowService()
    ) {
        parent::__construct();
    }

    public function healthProxy(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult($this->workflowService->healthProxy());
    }

    public function quota(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult($this->workflowService->quota());
    }

    public function chat(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->chat($this->getRequestPayload())
        );
    }

    public function suggestCategory(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->suggestCategory($this->userId ?? 0, $this->getRequestPayload())
        );
    }

    public function analyzeSpending(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->analyzeSpending($this->userId ?? 0, $this->getRequestPayload())
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function respondWorkflowResult(array $result): Response
    {
        if (!$result['success']) {
            return $this->workflowFailureResponse(
                $result,
                'Erro ao processar operacao de IA.',
                LogCategory::GENERAL,
                ['controller' => 'sysadmin_ai']
            );
        }

        return Response::successResponse($result['data'] ?? null);
    }
}
