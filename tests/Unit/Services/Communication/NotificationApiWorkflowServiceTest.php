<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Communication;

use Application\Services\Communication\NotificationApiWorkflowService;
use Application\Services\Communication\NotificationInboxService;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class NotificationApiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUnreadCountMapsInboxServicePayload(): void
    {
        $inboxService = Mockery::mock(NotificationInboxService::class);
        $inboxService
            ->shouldReceive('getUnreadCount')
            ->once()
            ->with(10, ['x' => 1])
            ->andReturn([
                'unread' => 7,
                'ignored_alerts' => ['x' => 2],
            ]);

        $service = new NotificationApiWorkflowService($inboxService);
        $result = $service->unreadCount(10, ['x' => 1]);

        $this->assertTrue($result['success']);
        $this->assertSame(['unread' => 7], $result['data']);
        $this->assertSame(['x' => 2], $result['ignored_alerts']);
    }

    public function testMarkAsReadReturnsValidationPayloadWhenNoValidIdsAreProvided(): void
    {
        $inboxService = Mockery::mock(NotificationInboxService::class);
        $inboxService
            ->shouldReceive('markAsRead')
            ->once()
            ->andThrow(new InvalidArgumentException('Nenhum ID válido'));

        $service = new NotificationApiWorkflowService($inboxService);
        $result = $service->markAsRead(11, [], []);

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['status']);
        $this->assertSame('Validation failed', $result['message']);
        $this->assertSame([
            'ids' => 'Nenhum ID de notificacao valido fornecido.',
        ], $result['errors']);
    }
}
