<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Container\ApplicationContainer;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\SchedulerExecutionLock;
use Throwable;

class ScheduledCampaignHeartbeatService
{
    private const CACHE_KEY = 'communications:scheduled_campaigns:last_tick';
    private const LOCK_NAME = 'scheduled-campaigns-heartbeat';

    private NotificationService $notificationService;
    private CacheService $cache;
    private SchedulerExecutionLock $lock;

    public function __construct(
        ?NotificationService $notificationService = null,
        ?CacheService $cache = null,
        ?SchedulerExecutionLock $lock = null
    ) {
        $this->notificationService = ApplicationContainer::resolveOrNew($notificationService, NotificationService::class);
        $this->cache = ApplicationContainer::resolveOrNew($cache, CacheService::class);
        $this->lock = ApplicationContainer::resolveOrNew($lock, SchedulerExecutionLock::class);
    }

    public function tick(int $intervalSeconds = 60): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $intervalSeconds = max(15, $intervalSeconds);

        try {
            if (!$this->shouldRun($intervalSeconds)) {
                return;
            }

            try {
                $this->lock->acquire(self::LOCK_NAME);
            } catch (Throwable) {
                return;
            }

            $result = $this->notificationService->processScheduledCampaigns();
            $this->rememberRun($intervalSeconds);

            if (($result['processed'] ?? 0) > 0 || ($result['stuck_recovered'] ?? 0) > 0) {
                LogService::info('[ScheduledCampaignHeartbeat] Campanhas sincronizadas via web heartbeat', $result);
            }
        } catch (Throwable $e) {
            $this->rememberRun($intervalSeconds);

            LogService::warning('[ScheduledCampaignHeartbeat] Falha ao sincronizar campanhas agendadas', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->lock->release();
        }
    }

    private function shouldRun(int $intervalSeconds): bool
    {
        $lastRun = (int) ($this->cache->get(self::CACHE_KEY, 0) ?? 0);

        return $lastRun <= 0 || (time() - $lastRun) >= $intervalSeconds;
    }

    private function rememberRun(int $intervalSeconds): void
    {
        $this->cache->set(self::CACHE_KEY, time(), max($intervalSeconds * 5, 300));
    }
}
