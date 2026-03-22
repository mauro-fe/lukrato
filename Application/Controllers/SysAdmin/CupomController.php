<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Admin\CupomAdminWorkflowService;

class CupomController extends BaseController
{
    public function __construct(
        private readonly CupomAdminWorkflowService $workflowService = new CupomAdminWorkflowService()
    ) {
        parent::__construct();
    }

    public function index(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult($this->workflowService->listCoupons());
    }

    public function store(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->createCoupon($this->getRequestPayload())
        );
    }

    public function destroy(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->deleteCoupon($this->getRequestPayload())
        );
    }

    public function validar(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->validateCoupon($user, $this->getStringQuery('codigo'))
        );
    }

    public function update(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->updateCoupon($this->getRequestPayload())
        );
    }

    public function estatisticas(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->getStatistics($this->getQuery('id'))
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
                'Erro ao processar operacao de cupom.',
                LogCategory::GENERAL,
                ['controller' => 'sysadmin_cupom']
            );
        }

        return Response::successResponse($result['data'] ?? null);
    }
}
