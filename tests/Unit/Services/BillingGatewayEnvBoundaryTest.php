<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class BillingGatewayEnvBoundaryTest extends TestCase
{
    public function testBillingGatewayAndMonitoringFilesDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Services/Billing/AsaasService.php',
            'Application/Services/Billing/AsaasHttpClient.php',
            'Application/Services/Billing/BillingAuditService.php',
            'Application/Services/Billing/DuplicateChargeMonitor.php',
            'Application/Services/Billing/SubscriptionExpirationService.php',
            'Application/Services/Billing/WebhookQueueService.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents, sprintf('Nao foi possivel ler %s', $file));
            $this->assertDoesNotMatchRegularExpression(
                '/\$_ENV|getenv\s*\(/i',
                $contents,
                sprintf('%s nao deve ler ambiente diretamente.', $file)
            );
        }
    }
}