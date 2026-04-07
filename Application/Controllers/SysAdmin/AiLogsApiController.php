<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Admin\AiLogsAdminWorkflowService;

class AiLogsApiController extends ApiController
{
    private readonly AiLogsAdminWorkflowService $workflowService;

    public function __construct(
        ?AiLogsAdminWorkflowService $workflowService = null
    ) {
        parent::__construct();
        $this->workflowService = $this->resolveOrCreate($workflowService, AiLogsAdminWorkflowService::class);
    }

    public function index(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->index([
                'type' => $this->getQuery('type'),
                'channel' => $this->getQuery('channel'),
                'success' => $this->getQuery('success', ''),
                'search' => $this->getQuery('search'),
                'date_from' => $this->getQuery('date_from'),
                'date_to' => $this->getQuery('date_to'),
                'page' => $this->getIntQuery('page', 1),
                'per_page' => $this->getIntQuery('per_page', 20),
            ]),
            useWorkflowFailureOnFailure: false
        );
    }

    public function summary(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->summary($this->getIntQuery('hours', 24)),
            useWorkflowFailureOnFailure: false
        );
    }

    public function cleanup(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->cleanup($this->getRequestPayload()),
            useWorkflowFailureOnFailure: false
        );
    }

    public function quality(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->quality($this->getIntQuery('hours', 24)),
            useWorkflowFailureOnFailure: false
        );
    }
}
