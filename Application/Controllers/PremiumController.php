<?php

declare(strict_types=1);

namespace Application\Controllers;

use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Billing\PremiumWorkflowService;

class PremiumController extends ApiController
{
    private readonly PremiumWorkflowService $workflowService;

    public function __construct(
        ?PremiumWorkflowService $workflowService = null
    ) {
        parent::__construct();
        $this->workflowService = $this->resolveOrCreate($workflowService, PremiumWorkflowService::class);
    }

    public function checkout(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->checkout($userId, $this->getRequestPayload()),
            'Erro ao processar assinatura.',
            LogCategory::PAYMENT,
            ['controller' => 'premium'],
            true
        );
    }

    public function getPendingPayment(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->getPendingPayment($userId),
            'Erro ao processar assinatura.',
            LogCategory::PAYMENT,
            ['controller' => 'premium'],
            true
        );
    }

    public function cancelPendingPayment(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->cancelPendingPayment($userId),
            'Erro ao processar assinatura.',
            LogCategory::PAYMENT,
            ['controller' => 'premium'],
            true
        );
    }

    public function getPendingPix(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->getPendingPix($userId),
            'Erro ao processar assinatura.',
            LogCategory::PAYMENT,
            ['controller' => 'premium'],
            true
        );
    }

    public function checkPayment(string $paymentId): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->checkPayment($userId, $paymentId),
            'Erro ao processar assinatura.',
            LogCategory::PAYMENT,
            ['controller' => 'premium'],
            true
        );
    }

    public function cancel(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondApiWorkflowResult(
            $this->workflowService->cancelSubscription($userId),
            'Erro ao processar assinatura.',
            LogCategory::PAYMENT,
            ['controller' => 'premium'],
            true
        );
    }
}
