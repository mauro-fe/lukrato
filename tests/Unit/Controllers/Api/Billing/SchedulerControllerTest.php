<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Billing;

use Application\Controllers\Api\Billing\SchedulerController;
use PHPUnit\Framework\TestCase;

class SchedulerControllerTest extends TestCase
{
    public function testHealthReturnsLegacyDisabledResponse(): void
    {
        $controller = new SchedulerController();

        $response = $controller->health();

        $this->assertSame(410, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($payload['success']);
        $this->assertStringContainsString('cli/run_scheduler.php', $payload['message']);
    }
}
