<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\SchedulerExecutionLock;
use Throwable;

class ScheduledCampaignHeartbeatService
{
    private const CACHE_KEY = 'communications:scheduled_campaigns:last_tick';
    private const LOCK_NAME = 'scheduled-campaigns-heartbeat';

    public function __construct(
        private readonly ?NotificationService $notificationService = null,
        private readonly ?CacheService $cache = null,
        private readonly ?SchedulerExecutionLock $lock = null
    ) {
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
                $this->getLock()->acquire(self::LOCK_NAME);
            } catch (Throwable) {
                return;
            }

            if (!$this->shouldRun($intervalSeconds)) {
                return;
            }

            $result = $this->getNotificationService()->processScheduledCampaigns();
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
            $this->getLock()->release();
        }
    }

    private function shouldRun(int $intervalSeconds): bool
    {
        $lastRun = (int) ($this->getCache()->get(self::CACHE_KEY, 0) ?? 0);

        return $lastRun <= 0 || (time() - $lastRun) >= $intervalSeconds;
    }

    private function rememberRun(int $intervalSeconds): void
    {
        $this->getCache()->set(self::CACHE_KEY, time(), max($intervalSeconds * 5, 300));
    }

    private function getNotificationService(): NotificationService
    {
        return $this->notificationService ?? new NotificationService();
    }

    private function getCache(): CacheService
    {
        return $this->cache ?? new CacheService();
    }

    private function getLock(): SchedulerExecutionLock
    {
        return $this->lock ?? new SchedulerExecutionLock();
    }
}
