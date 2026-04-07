<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Notification;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Communication\NotificationApiWorkflowService;
use Application\Services\Communication\NotificationInboxService;
use Application\Services\Infrastructure\LogService;
use Throwable;

class NotificacaoController extends ApiController
{
    private NotificationApiWorkflowService $workflowService;

    public function __construct(
        ?CartaoCreditoService $cartaoService = null,
        ?CartaoFaturaService $faturaService = null,
        ?NotificationInboxService $notificationInboxService = null,
        ?NotificationApiWorkflowService $workflowService = null
    ) {
        parent::__construct();

        $resolvedCartaoService = $this->resolveOrCreate($cartaoService, CartaoCreditoService::class);
        $resolvedFaturaService = $this->resolveOrCreate($faturaService, CartaoFaturaService::class);
        $resolvedInboxService = $this->resolveOrCreate(
            $notificationInboxService,
            NotificationInboxService::class,
            fn(): NotificationInboxService => new NotificationInboxService($resolvedCartaoService, $resolvedFaturaService)
        );

        $this->workflowService = $this->resolveOrCreate(
            $workflowService,
            NotificationApiWorkflowService::class,
            fn(): NotificationApiWorkflowService => new NotificationApiWorkflowService($resolvedInboxService)
        );
    }

    public function index(): Response
    {
        try {
            $userId = $this->requireApiUserIdOrFail();
            $this->ensureSessionStarted();

            $result = $this->workflowService->inbox($userId, $this->getIgnoredAlerts());
            $this->persistIgnoredAlerts($result);

            return $this->respondApiWorkflowResult(
                $result,
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false,
                mapValidationFailedTo422: true
            );
        } catch (Throwable $e) {
            $this->logNotificationError('Erro ao buscar notificacoes.', $e);

            return Response::successResponse([
                'itens' => [],
                'unread' => 0,
            ]);
        } finally {
            $this->releaseSession();
        }
    }

    public function unreadCount(): Response
    {
        try {
            $userId = $this->requireApiUserIdOrFail();
            $this->ensureSessionStarted();

            $result = $this->workflowService->unreadCount($userId, $this->getIgnoredAlerts());
            $this->persistIgnoredAlerts($result);

            return $this->respondApiWorkflowResult(
                $result,
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false,
                mapValidationFailedTo422: true
            );
        } catch (Throwable $e) {
            $this->logNotificationError('Erro ao buscar contagem de notificacoes nao lidas.', $e);

            return Response::successResponse(['unread' => 0]);
        } finally {
            $this->releaseSession();
        }
    }

    public function marcarLida(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $this->ensureSessionStarted();

        try {
            $result = $this->workflowService->markAsRead(
                $userId,
                $this->getRequestPayload(),
                $this->getIgnoredAlerts()
            );

            $this->persistIgnoredAlerts($result);

            return $this->respondApiWorkflowResult(
                $result,
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false,
                mapValidationFailedTo422: true
            );
        } finally {
            $this->releaseSession();
        }
    }

    public function marcarTodasLidas(): Response
    {
        $userId = $this->requireApiUserIdOrFail();
        $this->ensureSessionStarted();

        try {
            $result = $this->workflowService->markAllAsRead($userId, $this->getIgnoredAlerts());
            $this->persistIgnoredAlerts($result);

            return $this->respondApiWorkflowResult(
                $result,
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false,
                mapValidationFailedTo422: true
            );
        } finally {
            $this->releaseSession();
        }
    }

    public function getReferralRewards(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->getReferralRewards($userId),
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false,
                mapValidationFailedTo422: true
            );
        } catch (Throwable $e) {
            $this->logNotificationError('Erro ao buscar recompensas de referral.', $e, [
                'user_id' => $userId,
            ]);

            return Response::errorResponse('Erro ao buscar recompensas', 500);
        }
    }

    public function markReferralRewardsSeen(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            return $this->respondApiWorkflowResult(
                $this->workflowService->markReferralRewardsSeen($userId, $this->getRequestPayload()),
                preserveSuccessMeta: true,
                useWorkflowFailureOnFailure: false,
                mapValidationFailedTo422: true
            );
        } catch (Throwable $e) {
            $this->logNotificationError('Erro ao marcar recompensas como vistas.', $e, [
                'user_id' => $userId,
            ]);

            return Response::errorResponse('Erro ao marcar recompensas', 500);
        }
    }

    /**
     * @return array<string, int>
     */
    private function getIgnoredAlerts(): array
    {
        return (array) ($_SESSION['alertas_ignorados'] ?? []);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function persistIgnoredAlerts(array $result): void
    {
        if (isset($result['ignored_alerts']) && is_array($result['ignored_alerts'])) {
            $_SESSION['alertas_ignorados'] = $result['ignored_alerts'];
        }
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function logNotificationError(string $message, Throwable $e, array $context = []): void
    {
        LogService::error($message, array_merge($context, [
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]));
    }
}
