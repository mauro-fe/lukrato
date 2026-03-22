<?php

declare(strict_types=1);

namespace Application\Controllers;

use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Billing\PremiumWorkflowService;

class PremiumController extends BaseController
{
    public function __construct(
        private readonly PremiumWorkflowService $workflowService = new PremiumWorkflowService()
    ) {
        parent::__construct();
    }

    public function checkout(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->checkout($userId, $this->getRequestPayload())
        );
    }

    public function getPendingPayment(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->getPendingPayment($userId)
        );
    }

    public function cancelPendingPayment(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->cancelPendingPayment($userId)
        );
    }

    public function getPendingPix(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->getPendingPix($userId)
        );
    }

    public function checkPayment(string $paymentId): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->checkPayment($userId, $paymentId)
        );
    }

    public function cancel(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondWorkflowResult(
            $this->workflowService->cancelSubscription($userId)
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function respondWorkflowResult(array $result): Response
    {
        if (!$result['success']) {
            $errors = $result['errors'] ?? null;
            if ($errors === []) {
                $errors = null;
            }

            return $this->workflowFailureResponse(
                array_merge($result, ['errors' => $errors]),
                'Erro ao processar assinatura.',
                LogCategory::PAYMENT,
                ['controller' => 'premium']
            );
        }

        return Response::successResponse(
            $result['data'] ?? null,
            $result['message'] ?? 'Success',
            $result['status'] ?? 200
        );
    }
}
