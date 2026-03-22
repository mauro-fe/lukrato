<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Services\Admin\AiLogsAdminWorkflowService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AiLogsAdminWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSummaryNormalizesMinimumHoursAndReturnsSuccessPayload(): void
    {
        $service = Mockery::mock('alias:Application\Services\AI\AiLogService');
        $service
            ->shouldReceive('summary')
            ->once()
            ->with(1)
            ->andReturn(['total' => 3]);

        $workflow = new AiLogsAdminWorkflowService();
        $result = $workflow->summary(0);

        $this->assertTrue($result['success']);
        $this->assertSame(['total' => 3], $result['data']);
    }

    public function testCleanupUsesDefaultRetentionWindow(): void
    {
        $service = Mockery::mock('alias:Application\Services\AI\AiLogService');
        $service
            ->shouldReceive('cleanup')
            ->once()
            ->with(90)
            ->andReturn(12);

        $workflow = new AiLogsAdminWorkflowService();
        $result = $workflow->cleanup([]);

        $this->assertTrue($result['success']);
        $this->assertSame([
            'deleted' => 12,
            'message' => 'Removidos 12 registros com mais de 90 dias.',
        ], $result['data']);
    }
}
