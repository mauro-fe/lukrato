<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class ScheduledOperationsCompositionGuardTest extends TestCase
{
    public function testScheduledOperationServicesDoNotInstantiateDependenciesInline(): void
    {
        $files = [
            'Application/Services/Infrastructure/SchedulerTaskRunner.php',
            'Application/Services/Communication/ScheduledCampaignHeartbeatService.php',
            'Application/Services/Communication/NotificationService.php',
            'Application/Services/Communication/MailService.php',
            'Application/Services/Billing/SubscriptionExpirationService.php',
            'Application/Services/Communication/LancamentoReminderDispatchService.php',
            'Application/Services/Communication/FaturaReminderDispatchService.php',
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
                "Serviço não deve montar dependência inline com new: {$filePath}"
            );
        }

        $schedulerTaskRunner = (string) file_get_contents('Application/Services/Infrastructure/SchedulerTaskRunner.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+MailService\s*\(/',
            $schedulerTaskRunner,
            'SchedulerTaskRunner não deve instanciar MailService diretamente.'
        );

        $notificationService = (string) file_get_contents('Application/Services/Communication/NotificationService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+SchedulerExecutionLock\s*\(/',
            $notificationService,
            'NotificationService não deve instanciar SchedulerExecutionLock diretamente.'
        );

        $mailService = (string) file_get_contents('Application/Services/Communication/MailService.php');

        $this->assertStringNotContainsString(
            'static fn(): LoggerInterface => new NullLogger()',
            $mailService,
            'MailService não deve montar NullLogger inline por closure.'
        );

        $this->assertStringNotContainsString(
            '(new CommunicationServiceProvider())->register(',
            $mailService,
            'MailService não deve registrar CommunicationServiceProvider manualmente.'
        );

        $this->assertStringNotContainsString(
            'static fn(): LoggerInterface => new NullLogger()',
            $notificationService,
            'NotificationService não deve montar NullLogger inline por closure.'
        );

        $this->assertStringNotContainsString(
            '(new CommunicationServiceProvider())->register(',
            $notificationService,
            'NotificationService não deve registrar CommunicationServiceProvider manualmente.'
        );
    }
}
