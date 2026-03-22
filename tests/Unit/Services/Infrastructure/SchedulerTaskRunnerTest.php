<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use Application\Services\Billing\SubscriptionExpirationService;
use Application\Services\Cartao\RecorrenciaCartaoService;
use Application\Services\Communication\FaturaReminderDispatchService;
use Application\Services\Communication\LancamentoReminderDispatchService;
use Application\Services\Communication\NotificationService;
use Application\Services\Infrastructure\SchedulerTaskRunner;
use Application\Services\Lancamento\LancamentoCreationService;
use PHPUnit\Framework\TestCase;

class SchedulerTaskRunnerTest extends TestCase
{
    public function testListTasksIncludesExpectedOperationalEntries(): void
    {
        $runner = new SchedulerTaskRunner();

        $tasks = $runner->listTasks();

        $this->assertArrayHasKey(SchedulerTaskRunner::TASK_DISPATCH_REMINDERS, $tasks);
        $this->assertArrayHasKey(SchedulerTaskRunner::TASK_DISPATCH_BIRTHDAYS, $tasks);
        $this->assertArrayHasKey(SchedulerTaskRunner::TASK_DISPATCH_FATURA_REMINDERS, $tasks);
        $this->assertArrayHasKey(SchedulerTaskRunner::TASK_PROCESS_EXPIRED_SUBSCRIPTIONS, $tasks);
        $this->assertArrayHasKey(SchedulerTaskRunner::TASK_GENERATE_RECURRING_LANCAMENTOS, $tasks);
        $this->assertArrayHasKey(SchedulerTaskRunner::TASK_PROCESS_RECURRING_CARD_ITEMS, $tasks);
        $this->assertArrayHasKey(SchedulerTaskRunner::TASK_DISPATCH_SCHEDULED_CAMPAIGNS, $tasks);
    }

    public function testRunTaskUsesInjectedReminderService(): void
    {
        $reminderService = $this->createMock(LancamentoReminderDispatchService::class);
        $reminderService->expects($this->once())
            ->method('dispatch')
            ->willReturn(['processed' => 3, 'updated' => 2]);

        $runner = new SchedulerTaskRunner($reminderService);

        $result = $runner->runTask(SchedulerTaskRunner::TASK_DISPATCH_REMINDERS);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['result']['processed']);
        $this->assertSame(2, $result['result']['updated']);
    }

    public function testRunTaskBirthdayPreviewUsesNotificationServicePreviewMethods(): void
    {
        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())
            ->method('getBirthdayUsers')
            ->willReturn([['id' => 1, 'nome' => 'Ana']]);
        $notificationService->expects($this->once())
            ->method('getUpcomingBirthdays')
            ->with(7)
            ->willReturn([['id' => 2, 'nome' => 'Bia']]);

        $runner = new SchedulerTaskRunner(
            notificationService: $notificationService,
        );

        $result = $runner->runTask(SchedulerTaskRunner::TASK_DISPATCH_BIRTHDAYS, [
            'preview' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['result']['preview']);
        $this->assertCount(1, $result['result']['birthday_users']);
        $this->assertCount(1, $result['result']['upcoming_birthdays']);
    }

    public function testRunAllExecutesAllConfiguredOperationalTasks(): void
    {
        $reminderService = $this->createMock(LancamentoReminderDispatchService::class);
        $reminderService->expects($this->once())->method('dispatch')->willReturn(['processed' => 1]);

        $notificationService = $this->createMock(NotificationService::class);
        $notificationService->expects($this->once())->method('processBirthdayNotifications')->with(true)->willReturn(['birthday_users' => 0]);
        $notificationService->expects($this->once())->method('processScheduledCampaigns')->willReturn(['processed' => 0, 'sent' => 0, 'failed' => 0]);

        $faturaReminderService = $this->createMock(FaturaReminderDispatchService::class);
        $faturaReminderService->expects($this->once())->method('dispatch')->willReturn(['processed' => 1]);

        $subscriptionExpirationService = $this->createMock(SubscriptionExpirationService::class);
        $subscriptionExpirationService->expects($this->once())->method('processExpiredSubscriptions')->willReturn(['checked' => 1]);

        $lancamentoCreationService = $this->createMock(LancamentoCreationService::class);
        $lancamentoCreationService->expects($this->once())->method('estenderRecorrenciasInfinitas')->willReturn(2);

        $recorrenciaCartaoService = $this->createMock(RecorrenciaCartaoService::class);
        $recorrenciaCartaoService->expects($this->once())->method('processRecurringCardItems')->willReturn(['processados' => 1]);

        $runner = new SchedulerTaskRunner(
            $reminderService,
            $notificationService,
            $faturaReminderService,
            $subscriptionExpirationService,
            $lancamentoCreationService,
            $recorrenciaCartaoService,
        );

        $result = $runner->runAll();

        $this->assertTrue($result['success']);
        $this->assertSame(7, $result['executed']);
        $this->assertSame(7, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertContains(
            SchedulerTaskRunner::TASK_PROCESS_RECURRING_CARD_ITEMS,
            array_column($result['results'], 'task')
        );
    }
}
