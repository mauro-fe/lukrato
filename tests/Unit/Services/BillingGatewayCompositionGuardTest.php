<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class BillingGatewayCompositionGuardTest extends TestCase
{
    public function testBillingGatewayServicesDoNotInstantiateDependenciesInline(): void
    {
        $premiumWorkflowService = (string) file_get_contents('Application/Services/Billing/PremiumWorkflowService.php');
        $asaasService = (string) file_get_contents('Application/Services/Billing/AsaasService.php');
        $webhookQueueService = (string) file_get_contents('Application/Services/Billing/WebhookQueueService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
            $premiumWorkflowService,
            'PremiumWorkflowService não deve usar default inline com new no construtor.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AchievementService\s*\(/',
            $premiumWorkflowService,
            'PremiumWorkflowService não deve instanciar AchievementService diretamente.'
        );

        $this->assertStringNotContainsString(
            'PerfilControllerFactory::createService()',
            $premiumWorkflowService,
            'PremiumWorkflowService não deve recorrer à PerfilControllerFactory.'
        );

        $this->assertStringNotContainsString(
            '(new PerfilServiceProvider())->register(',
            $premiumWorkflowService,
            'PremiumWorkflowService não deve registrar PerfilServiceProvider manualmente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+PerfilService\s*\(/',
            $premiumWorkflowService,
            'PremiumWorkflowService não deve instanciar PerfilService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->client\s*=\s*new\s+Client\s*\(/',
            $asaasService,
            'AsaasService não deve atribuir Client diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->circuitBreaker\s*=\s*new\s+CircuitBreakerService\s*\(/',
            $asaasService,
            'AsaasService não deve atribuir CircuitBreakerService diretamente.'
        );

        $this->assertStringNotContainsString(
            "static fn(): CircuitBreakerService => new CircuitBreakerService('asaas')",
            $asaasService,
            'AsaasService não deve montar CircuitBreakerService inline por closure.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+Client\s*\(/',
            $asaasService,
            'AsaasService não deve instanciar Guzzle Client diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$this->redis\s*=\s*new\s+RedisClient\s*\(/',
            $webhookQueueService,
            'WebhookQueueService não deve atribuir RedisClient diretamente.'
        );
    }
}
