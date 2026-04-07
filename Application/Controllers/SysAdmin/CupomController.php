<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Admin\CupomAdminWorkflowService;

class CupomController extends ApiController
{
    private readonly CupomAdminWorkflowService $workflowService;

    public function __construct(
        ?CupomAdminWorkflowService $workflowService = null
    ) {
        parent::__construct();
        $this->workflowService = $this->resolveOrCreate($workflowService, CupomAdminWorkflowService::class);
    }

    public function index(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->listCoupons(),
            'Erro ao processar operacao de cupom.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_cupom']
        );
    }

    public function store(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->createCoupon($this->getRequestPayload()),
            'Erro ao processar operacao de cupom.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_cupom']
        );
    }

    public function destroy(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->deleteCoupon($this->getRequestPayload()),
            'Erro ao processar operacao de cupom.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_cupom']
        );
    }

    public function validar(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->validateCoupon($user, $this->getStringQuery('codigo')),
            'Erro ao processar operacao de cupom.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_cupom']
        );
    }

    public function update(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->updateCoupon($this->getRequestPayload()),
            'Erro ao processar operacao de cupom.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_cupom']
        );
    }

    public function estatisticas(): Response
    {
        $this->requireApiAdminUserAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->getStatistics($this->getQuery('id')),
            'Erro ao processar operacao de cupom.',
            LogCategory::GENERAL,
            ['controller' => 'sysadmin_cupom']
        );
    }
}
