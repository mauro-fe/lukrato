<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Billing;

use Application\Controllers\ApiController;
use Application\Core\Response;

/**
 * Legacy stub.
 *
 * As rotas HTTP de scheduler foram removidas por seguranca.
 * Use `php cli/run_scheduler.php` como entrada operacional.
 */
class SchedulerController extends ApiController
{
    public function health(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function tasks(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function debug(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function dispatchReminders(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function dispatchBirthdays(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function dispatchFaturaReminders(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function processExpiredSubscriptions(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function generateRecurringLancamentos(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function dispatchScheduledCampaigns(): Response
    {
        return $this->legacyDisabledResponse();
    }

    public function runAll(): Response
    {
        return $this->legacyDisabledResponse();
    }

    private function legacyDisabledResponse(): Response
    {
        return Response::errorResponse(
            'Scheduler HTTP desativado. Use o runner CLI interno: php cli/run_scheduler.php',
            410
        );
    }
}
