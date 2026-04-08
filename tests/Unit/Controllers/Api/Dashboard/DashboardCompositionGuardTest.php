<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Dashboard;

use PHPUnit\Framework\TestCase;

class DashboardCompositionGuardTest extends TestCase
{
    public function testDashboardTraitDoesNotAssembleDashboardServicesInline(): void
    {
        $content = (string) file_get_contents('Application/Controllers/Api/Dashboard/Concerns/HandlesDashboardRead.php');

        $this->assertStringNotContainsString(
            'fn(): HealthScoreService => new HealthScoreService(',
            $content,
            'Trait de dashboard não deve montar HealthScoreService inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): DashboardInsightService => new DashboardInsightService(',
            $content,
            'Trait de dashboard não deve montar DashboardInsightService inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): HealthScoreInsightService => new HealthScoreInsightService(',
            $content,
            'Trait de dashboard não deve montar HealthScoreInsightService inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): DashboardHealthSummaryService => new DashboardHealthSummaryService(',
            $content,
            'Trait de dashboard não deve montar DashboardHealthSummaryService inline.'
        );
    }
}
