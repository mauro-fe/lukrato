<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Admin\AiLogsAdminWorkflowService;

class AiLogsApiController extends BaseController
{
    public function __construct(
        private readonly AiLogsAdminWorkflowService $workflowService = new AiLogsAdminWorkflowService()
    ) {
        parent::__construct();
    }

    public function index(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult($this->workflowService->index([
            'type' => $this->getQuery('type'),
            'channel' => $this->getQuery('channel'),
            'success' => $this->getQuery('success', ''),
            'search' => $this->getQuery('search'),
            'date_from' => $this->getQuery('date_from'),
            'date_to' => $this->getQuery('date_to'),
            'page' => $this->getIntQuery('page', 1),
            'per_page' => $this->getIntQuery('per_page', 20),
        ]));
    }

    public function summary(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->summary($this->getIntQuery('hours', 24))
        );
    }

    public function cleanup(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->cleanup($this->getRequestPayload())
        );
    }

    public function quality(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->quality($this->getIntQuery('hours', 24))
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function respondWorkflowResult(array $result): Response
    {
        if (!$result['success']) {
            return Response::errorResponse(
                $result['message'],
                $result['status'] ?? 400,
                $result['errors'] ?? null
            );
        }

        return Response::successResponse($result['data'] ?? null);
    }
}
