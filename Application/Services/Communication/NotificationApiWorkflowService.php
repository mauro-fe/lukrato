<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use InvalidArgumentException;

class NotificationApiWorkflowService
{
    public function __construct(
        private readonly NotificationInboxService $inboxService = new NotificationInboxService()
    ) {
    }

    /**
     * @param array<string, int> $ignoredAlerts
     * @return array<string, mixed>
     */
    public function inbox(int $userId, array $ignoredAlerts): array
    {
        $result = $this->inboxService->getInbox($userId, $ignoredAlerts);

        return [
            'success' => true,
            'data' => [
                'itens' => $result['itens'],
                'unread' => $result['unread'],
            ],
            'ignored_alerts' => $result['ignored_alerts'],
        ];
    }

    /**
     * @param array<string, int> $ignoredAlerts
     * @return array<string, mixed>
     */
    public function unreadCount(int $userId, array $ignoredAlerts): array
    {
        $result = $this->inboxService->getUnreadCount($userId, $ignoredAlerts);

        return [
            'success' => true,
            'data' => [
                'unread' => (int) $result['unread'],
            ],
            'ignored_alerts' => $result['ignored_alerts'],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, int> $ignoredAlerts
     * @return array<string, mixed>
     */
    public function markAsRead(int $userId, array $payload, array $ignoredAlerts): array
    {
        try {
            $result = $this->inboxService->markAsRead(
                $userId,
                (array) ($payload['ids'] ?? []),
                $ignoredAlerts
            );

            return [
                'success' => true,
                'data' => ['message' => 'Notificacoes marcadas como lidas'],
                'ignored_alerts' => $result['ignored_alerts'],
            ];
        } catch (InvalidArgumentException) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => [
                    'ids' => 'Nenhum ID de notificacao valido fornecido.',
                ],
            ];
        }
    }

    /**
     * @param array<string, int> $ignoredAlerts
     * @return array<string, mixed>
     */
    public function markAllAsRead(int $userId, array $ignoredAlerts): array
    {
        $result = $this->inboxService->markAllAsRead($userId, $ignoredAlerts);

        return [
            'success' => true,
            'data' => ['message' => 'Todas as notificacoes foram marcadas como lidas'],
            'ignored_alerts' => $result['ignored_alerts'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getReferralRewards(int $userId): array
    {
        return [
            'success' => true,
            'message' => 'Recompensas de indicacao',
            'data' => $this->inboxService->getReferralRewards($userId),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function markReferralRewardsSeen(int $userId, array $payload): array
    {
        $ids = is_array($payload['ids'] ?? null) ? $payload['ids'] : [];
        $this->inboxService->markReferralRewardsSeen($userId, $ids);

        return [
            'success' => true,
            'data' => ['message' => 'Recompensas marcadas como vistas'],
        ];
    }
}
