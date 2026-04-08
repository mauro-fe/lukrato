<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class ReportingAndBillingSupportCompositionGuardTest extends TestCase
{
    public function testReportingAndBillingSupportServicesDoNotInstantiateRepositoriesInline(): void
    {
        $files = [
            'Application/Services/Report/ReportService.php',
            'Application/Services/Dashboard/HealthScoreInsightService.php',
            'Application/Services/Billing/CustomerService.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve usar default inline com new: {$filePath}"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/\?\?=?\s*new\s+[\\\w]+/s',
                $content,
                "Serviço não deve montar repositório inline com new: {$filePath}"
            );
        }
    }
}
