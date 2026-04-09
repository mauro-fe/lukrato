<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class RequestBoundaryServiceGuardTest extends TestCase
{
    public function testRequestAwareServicesDoNotReadServerSuperglobalDirectly(): void
    {
        $files = [
            'Application/Services/Auth/RegistrationHandler.php',
            'Application/Services/Billing/AsaasService.php',
            'Application/Services/Billing/BillingAuditService.php',
            'Application/Services/Conta/ContaApiWorkflowService.php',
            'Application/Services/Importacao/ImportQueueService.php',
            'Application/Services/Infrastructure/LogService.php',
            'Application/Services/Referral/ReferralAntifraudService.php',
            'Application/Services/Referral/ReferralService.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/\$_SERVER\b/',
                $content,
                'Servico request-aware nao deve ler $_SERVER diretamente: ' . $filePath
            );
        }
    }
}
