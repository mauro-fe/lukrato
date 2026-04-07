<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Container\ApplicationContainer;
use Application\Models\Notificacao;
use Application\Models\Notification;
use Application\Models\Usuario;
use Application\Services\Billing\SubscriptionExpirationService;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Infrastructure\LogService;
use InvalidArgumentException;
use Throwable;

class NotificationInboxService
{
    private const ALERT_TTL_SECONDS = 86400;

    /**
     * @var array<string, array{0:string,1:string}>
     */
    private const CAMPAIGN_ICON_MAP = [
        'info' => ['📢', '#6b7280'],
        'promo' => ['🏷', '#f59e0b'],
        'update' => ['🚀', '#8b5cf6'],
        'alert' => ['⚠️', '#ef4444'],
        'success' => ['✅', '#10b981'],
        'reminder' => ['🔔', '#3b82f6'],
    ];

    private readonly CartaoCreditoService $cartaoService;
    private readonly CartaoFaturaService $faturaService;

    public function __construct(
        ?CartaoCreditoService $cartaoService = null,
        ?CartaoFaturaService $faturaService = null
    ) {
        $this->cartaoService = ApplicationContainer::resolveOrNew($cartaoService, CartaoCreditoService::class);
        $this->faturaService = ApplicationContainer::resolveOrNew($faturaService, CartaoFaturaService::class);
    }

    /**
     * @return array{itens:array<int, array<string, mixed>>, unread:int, ignored_alerts:array<string, int>}
     */
    public function getInbox(int $userId, array $ignoredAlerts): array
    {
        $ignoredAlerts = $this->cleanExpiredIgnoredAlerts($ignoredAlerts);
        $items = $this->loadLegacyNotifications($userId);

        $dynamicAlerts = $this->buildDynamicCardAlerts($userId, $ignoredAlerts);
        $items = array_merge($items, $dynamicAlerts['items']);
        $ignoredAlerts = $this->removeMissingIgnoredAlerts($ignoredAlerts, $dynamicAlerts['current_ids']);

        $subscriptionAlert = $this->buildSubscriptionGraceAlert($userId, $items, $ignoredAlerts);
        if ($subscriptionAlert !== null) {
            $items[] = $subscriptionAlert;
        }

        $items = array_merge($items, $this->loadCampaignNotifications($userId));
        $items = $this->sortItemsByDateDesc($items);

        return [
            'itens' => $items,
            'unread' => $this->countUnreadItems($items),
            'ignored_alerts' => $ignoredAlerts,
        ];
    }

    /**
     * @return array{unread:int, ignored_alerts:array<string, int>}
     */
    public function getUnreadCount(int $userId, array $ignoredAlerts): array
    {
        $ignoredAlerts = $this->cleanExpiredIgnoredAlerts($ignoredAlerts);

        $unread = (int) Notificacao::where('user_id', $userId)
            ->where('lida', false)
            ->count();

        $dynamicAlerts = $this->loadCardAlerts($userId);

        foreach ($dynamicAlerts['due'] as $alert) {
            $alertId = 'cartao_venc_' . $alert['cartao_id'];
            if (!isset($ignoredAlerts[$alertId])) {
                $unread++;
            }
        }

        foreach ($dynamicAlerts['limit'] as $alert) {
            $alertId = 'cartao_lim_' . $alert['cartao_id'];
            if (!isset($ignoredAlerts[$alertId])) {
                $unread++;
            }
        }

        try {
            $unread += (int) Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->count();
        } catch (Throwable $e) {
            $this->logNonBlockingError('Erro ao contar notificações de campanhas.', $e, $userId);
        }

        return [
            'unread' => $unread,
            'ignored_alerts' => $ignoredAlerts,
        ];
    }

    /**
     * @param array<int, mixed> $rawIds
     * @return array{ignored_alerts:array<string, int>}
     */
    public function markAsRead(int $userId, array $rawIds, array $ignoredAlerts): array
    {
        $parsedIds = $this->partitionNotificationIds($rawIds);

        if (empty($parsedIds['numeric_ids']) && empty($parsedIds['dynamic_ids']) && empty($parsedIds['campaign_ids'])) {
            throw new InvalidArgumentException('Nenhum ID de notificação válido fornecido.');
        }

        if (!empty($parsedIds['numeric_ids'])) {
            Notificacao::where('user_id', $userId)
                ->whereIn('id', $parsedIds['numeric_ids'])
                ->update(['lida' => true]);
        }

        if (!empty($parsedIds['campaign_ids'])) {
            Notification::where('user_id', $userId)
                ->whereIn('id', $parsedIds['campaign_ids'])
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        if (!empty($parsedIds['dynamic_ids'])) {
            $timestamp = time();
            foreach ($parsedIds['dynamic_ids'] as $alertId) {
                $ignoredAlerts[$alertId] = $timestamp;
            }
        }

        return ['ignored_alerts' => $ignoredAlerts];
    }

    /**
     * @return array{ignored_alerts:array<string, int>}
     */
    public function markAllAsRead(int $userId, array $ignoredAlerts): array
    {
        Notificacao::where('user_id', $userId)
            ->where('lida', false)
            ->update(['lida' => true]);

        try {
            Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        } catch (Throwable $e) {
            $this->logNonBlockingError('Erro ao marcar notificações de campanhas como lidas.', $e, $userId);
        }

        $timestamp = time();
        $dynamicAlerts = $this->loadCardAlerts($userId);

        foreach ($dynamicAlerts['due'] as $alert) {
            $ignoredAlerts['cartao_venc_' . $alert['cartao_id']] = $timestamp;
        }

        foreach ($dynamicAlerts['limit'] as $alert) {
            $ignoredAlerts['cartao_lim_' . $alert['cartao_id']] = $timestamp;
        }

        return ['ignored_alerts' => $ignoredAlerts];
    }

    /**
     * @return array{rewards:array<int, array<string, mixed>>, count:int}
     */
    public function getReferralRewards(int $userId): array
    {
        $rewards = Notificacao::where('user_id', $userId)
            ->where('lida', 0)
            ->where(function ($query): void {
                $query->where('tipo', 'referral_referred')
                    ->orWhere('tipo', 'referral_referrer');
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(static function ($notification): array {
                return [
                    'id' => $notification->id,
                    'tipo' => $notification->tipo,
                    'titulo' => $notification->titulo,
                    'mensagem' => $notification->mensagem,
                    'created_at' => $notification->created_at?->toDateTimeString(),
                ];
            })
            ->toArray();

        return [
            'rewards' => $rewards,
            'count' => count($rewards),
        ];
    }

    /**
     * @param array<int, mixed> $ids
     */
    public function markReferralRewardsSeen(int $userId, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        Notificacao::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update(['lida' => 1]);
    }

    /**
     * @return array<string, int>
     */
    public function cleanExpiredIgnoredAlerts(array $ignoredAlerts): array
    {
        $now = time();

        foreach ($ignoredAlerts as $alertId => $timestamp) {
            if (($now - (int) $timestamp) > self::ALERT_TTL_SECONDS) {
                unset($ignoredAlerts[$alertId]);
            }
        }

        return $ignoredAlerts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadLegacyNotifications(int $userId): array
    {
        return Notificacao::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(static function ($notification): array {
                $data = $notification->toArray();
                $data['lida'] = (int) $notification->lida;

                return $data;
            })
            ->toArray();
    }

    /**
     * @return array{due:array<int, array<string, mixed>>, limit:array<int, array<string, mixed>>}
     */
    private function loadCardAlerts(int $userId): array
    {
        try {
            return [
                'due' => $this->faturaService->verificarVencimentosProximos($userId),
                'limit' => $this->cartaoService->verificarLimitesBaixos($userId),
            ];
        } catch (Throwable $e) {
            $this->logNonBlockingError('Erro ao buscar alertas de cartões.', $e, $userId);

            return [
                'due' => [],
                'limit' => [],
            ];
        }
    }

    /**
     * @param array<string, int> $ignoredAlerts
     * @return array{items:array<int, array<string, mixed>>, current_ids:array<int, string>}
     */
    private function buildDynamicCardAlerts(int $userId, array $ignoredAlerts): array
    {
        $cardAlerts = $this->loadCardAlerts($userId);
        $items = [];
        $currentIds = [];

        foreach ($cardAlerts['due'] as $alert) {
            $alertId = 'cartao_venc_' . $alert['cartao_id'];
            $currentIds[] = $alertId;

            if (isset($ignoredAlerts[$alertId])) {
                continue;
            }

            $items[] = [
                'id' => $alertId,
                'tipo' => 'alerta',
                'titulo' => 'Fatura vencendo',
                'mensagem' => 'Fatura de ' . $alert['nome_cartao'] . ' vence em ' . $alert['dias_faltando'] . ' dia(s) - R$ ' . number_format((float) $alert['valor_fatura'], 2, ',', '.'),
                'lida' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'link' => '/cartoes',
                'icone' => '📆',
                'cor' => '#f39c12',
                'dinamico' => true,
            ];
        }

        foreach ($cardAlerts['limit'] as $alert) {
            $alertId = 'cartao_lim_' . $alert['cartao_id'];
            $currentIds[] = $alertId;

            if (isset($ignoredAlerts[$alertId])) {
                continue;
            }

            $isCritical = (float) $alert['percentual_disponivel'] < 10;

            $items[] = [
                'id' => $alertId,
                'tipo' => 'alerta',
                'titulo' => 'Limite baixo',
                'mensagem' => $alert['nome_cartao'] . ': apenas ' . $alert['percentual_disponivel'] . '% disponível (R$ ' . number_format((float) $alert['limite_disponivel'], 2, ',', '.') . ')',
                'lida' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'link' => '/cartoes',
                'icone' => $isCritical ? '🔴' : '🟠',
                'cor' => $isCritical ? '#e74c3c' : '#e67e22',
                'dinamico' => true,
            ];
        }

        return [
            'items' => $items,
            'current_ids' => $currentIds,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, int> $ignoredAlerts
     * @return array<string, mixed>|null
     */
    private function buildSubscriptionGraceAlert(int $userId, array $items, array $ignoredAlerts): ?array
    {
        try {
            $user = Usuario::find($userId);
            if (!$user) {
                return null;
            }

            $subscription = $user->assinaturaAtiva()->with('plano')->first();
            if (!$subscription || $subscription->plano?->code !== 'pro') {
                return null;
            }

            $graceDaysLeft = SubscriptionExpirationService::getGraceDaysRemaining($subscription);
            $isInGrace = SubscriptionExpirationService::isInGracePeriod($subscription);
            $alertId = 'subscription_grace_' . $subscription->id;

            if (!$isInGrace || $graceDaysLeft <= 0 || isset($ignoredAlerts[$alertId])) {
                return null;
            }

            if ($this->hasStoredSubscriptionNotification($items, $subscription->id)) {
                return null;
            }

            return [
                'id' => $alertId,
                'tipo' => 'alerta',
                'titulo' => '⏰ Plano PRO vencendo!',
                'mensagem' => 'Seu plano venceu! Restam ' . $graceDaysLeft . ' dia(s) para renovar antes de perder o acesso PRO.',
                'lida' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'link' => '/billing',
                'icone' => '⏰',
                'cor' => '#e74c3c',
                'dinamico' => true,
                'priority' => 'high',
            ];
        } catch (Throwable $e) {
            $this->logNonBlockingError('Erro ao verificar período de carência da assinatura.', $e, $userId);

            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCampaignNotifications(int $userId): array
    {
        try {
            return Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(static function ($notification): array {
                    $iconData = self::CAMPAIGN_ICON_MAP[$notification->type] ?? self::CAMPAIGN_ICON_MAP['info'];

                    return [
                        'id' => 'campaign_' . $notification->id,
                        'tipo' => $notification->type,
                        'titulo' => $notification->title,
                        'mensagem' => $notification->message,
                        'lida' => (int) $notification->is_read,
                        'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                        'link' => $notification->link,
                        'icone' => $iconData[0],
                        'cor' => $iconData[1],
                        'dinamico' => false,
                        'campaign_id' => $notification->campaign_id,
                    ];
                })
                ->toArray();
        } catch (Throwable $e) {
            $this->logNonBlockingError('Erro ao buscar notificações de campanhas.', $e, $userId);

            return [];
        }
    }

    /**
     * @param array<string, int> $ignoredAlerts
     * @param array<int, string> $currentIds
     * @return array<string, int>
     */
    private function removeMissingIgnoredAlerts(array $ignoredAlerts, array $currentIds): array
    {
        foreach (array_keys($ignoredAlerts) as $alertId) {
            if (!in_array($alertId, $currentIds, true)) {
                unset($ignoredAlerts[$alertId]);
            }
        }

        return $ignoredAlerts;
    }

    /**
     * @param array<int, mixed> $rawIds
     * @return array{numeric_ids:array<int, int>, dynamic_ids:array<int, string>, campaign_ids:array<int, int>}
     */
    private function partitionNotificationIds(array $rawIds): array
    {
        $numericIds = [];
        $dynamicIds = [];
        $campaignIds = [];

        foreach ($rawIds as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $numericIds[] = (int) $id;
                continue;
            }

            if (is_string($id) && str_starts_with($id, 'campaign_')) {
                $campaignId = (int) str_replace('campaign_', '', $id);
                if ($campaignId > 0) {
                    $campaignIds[] = $campaignId;
                }
                continue;
            }

            if (is_string($id) && $id !== '') {
                $dynamicIds[] = $id;
            }
        }

        return [
            'numeric_ids' => $numericIds,
            'dynamic_ids' => $dynamicIds,
            'campaign_ids' => $campaignIds,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function hasStoredSubscriptionNotification(array $items, int $subscriptionId): bool
    {
        foreach ($items as $item) {
            if (
                isset($item['tipo'], $item['link'])
                && in_array($item['tipo'], ['subscription_expired', 'subscription_blocked'], true)
                && str_contains((string) $item['link'], 'subscription_id=' . $subscriptionId)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sortItemsByDateDesc(array $items): array
    {
        usort($items, static function (array $left, array $right): int {
            $leftDate = strtotime((string) ($left['created_at'] ?? '1970-01-01'));
            $rightDate = strtotime((string) ($right['created_at'] ?? '1970-01-01'));

            return $rightDate <=> $leftDate;
        });

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function countUnreadItems(array $items): int
    {
        return count(array_filter($items, static fn(array $item): bool => (int) ($item['lida'] ?? 1) === 0));
    }

    private function logNonBlockingError(string $message, Throwable $e, int $userId): void
    {
        LogService::warning($message, [
            'user_id' => $userId,
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]);
    }
}
