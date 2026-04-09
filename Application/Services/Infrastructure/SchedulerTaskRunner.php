<?php

declare(strict_types=1);

namespace Application\Services\Infrastructure;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Enums\LogCategory;
use Application\Services\Billing\SubscriptionExpirationService;
use Application\Services\Cartao\RecorrenciaCartaoService;
use Application\Services\Communication\FaturaReminderDispatchService;
use Application\Services\Communication\LancamentoReminderDispatchService;
use Application\Services\Communication\MailService;
use Application\Services\Communication\NotificationService;
use Application\Services\Lancamento\LancamentoCreationService;
use Throwable;

class SchedulerTaskRunner
{
    public const TASK_DISPATCH_REMINDERS = 'dispatch-reminders';
    public const TASK_DISPATCH_BIRTHDAYS = 'dispatch-birthdays';
    public const TASK_DISPATCH_FATURA_REMINDERS = 'dispatch-fatura-reminders';
    public const TASK_PROCESS_EXPIRED_SUBSCRIPTIONS = 'process-expired-subscriptions';
    public const TASK_GENERATE_RECURRING_LANCAMENTOS = 'generate-recurring-lancamentos';
    public const TASK_PROCESS_RECURRING_CARD_ITEMS = 'process-recurring-card-items';
    public const TASK_DISPATCH_SCHEDULED_CAMPAIGNS = 'dispatch-scheduled-campaigns';

    private LancamentoReminderDispatchService $lancamentoReminderService;
    private NotificationService $notificationService;
    private FaturaReminderDispatchService $faturaReminderService;
    private SubscriptionExpirationService $subscriptionExpirationService;
    private LancamentoCreationService $lancamentoCreationService;
    private RecorrenciaCartaoService $recorrenciaCartaoService;
    private MailService $mailService;
    private InfrastructureRuntimeConfig $runtimeConfig;

    public function __construct(
        ?LancamentoReminderDispatchService $lancamentoReminderService = null,
        ?NotificationService $notificationService = null,
        ?FaturaReminderDispatchService $faturaReminderService = null,
        ?SubscriptionExpirationService $subscriptionExpirationService = null,
        ?LancamentoCreationService $lancamentoCreationService = null,
        ?RecorrenciaCartaoService $recorrenciaCartaoService = null,
        ?MailService $mailService = null,
        ?InfrastructureRuntimeConfig $runtimeConfig = null
    ) {
        $this->lancamentoReminderService = ApplicationContainer::resolveOrNew($lancamentoReminderService, LancamentoReminderDispatchService::class);
        $this->notificationService = ApplicationContainer::resolveOrNew($notificationService, NotificationService::class);
        $this->faturaReminderService = ApplicationContainer::resolveOrNew($faturaReminderService, FaturaReminderDispatchService::class);
        $this->subscriptionExpirationService = ApplicationContainer::resolveOrNew($subscriptionExpirationService, SubscriptionExpirationService::class);
        $this->lancamentoCreationService = ApplicationContainer::resolveOrNew($lancamentoCreationService, LancamentoCreationService::class);
        $this->recorrenciaCartaoService = ApplicationContainer::resolveOrNew($recorrenciaCartaoService, RecorrenciaCartaoService::class);
        $this->mailService = ApplicationContainer::resolveOrNew($mailService, MailService::class);
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, InfrastructureRuntimeConfig::class);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function listTasks(): array
    {
        return [
            self::TASK_DISPATCH_REMINDERS => [
                'description' => 'Dispara lembretes de lancamentos por in-app e email.',
                'recommended_schedule' => 'a cada 10 minutos',
            ],
            self::TASK_DISPATCH_BIRTHDAYS => [
                'description' => 'Dispara notificacoes de aniversario.',
                'recommended_schedule' => 'diariamente as 08:00',
            ],
            self::TASK_DISPATCH_FATURA_REMINDERS => [
                'description' => 'Dispara lembretes de vencimento de fatura de cartao.',
                'recommended_schedule' => 'a cada 1 hora',
            ],
            self::TASK_PROCESS_EXPIRED_SUBSCRIPTIONS => [
                'description' => 'Processa expiracao e bloqueio de assinaturas PRO.',
                'recommended_schedule' => 'a cada 1 hora',
            ],
            self::TASK_GENERATE_RECURRING_LANCAMENTOS => [
                'description' => 'Gera proximos lancamentos recorrentes vencidos.',
                'recommended_schedule' => 'diariamente as 02:00',
            ],
            self::TASK_PROCESS_RECURRING_CARD_ITEMS => [
                'description' => 'Gera itens recorrentes de cartao de credito.',
                'recommended_schedule' => 'diariamente as 03:00',
            ],
            self::TASK_DISPATCH_SCHEDULED_CAMPAIGNS => [
                'description' => 'Envia campanhas agendadas cujo horario ja chegou.',
                'recommended_schedule' => 'a cada 5 minutos',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        return [
            'status' => 'ok',
            'php_sapi' => PHP_SAPI,
            'environment' => $this->runtimeConfig->appEnvironment(),
            'time' => date('c'),
            'task_count' => count($this->listTasks()),
            'tasks' => array_keys($this->listTasks()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function debug(): array
    {
        return [
            'health' => $this->health(),
            'base_url' => defined('BASE_URL') ? BASE_URL : null,
            'storage_path' => $this->runtimeConfig->configuredStoragePath(),
            'mail_configured' => $this->mailService->isConfigured(),
            'tasks' => $this->listTasks(),
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function runTask(string $task, array $options = []): array
    {
        $startedAt = date('c');

        try {
            $result = match ($task) {
                self::TASK_DISPATCH_REMINDERS => $this->lancamentoReminderService->dispatch(),
                self::TASK_DISPATCH_BIRTHDAYS => $this->runBirthdayDispatch($options),
                self::TASK_DISPATCH_FATURA_REMINDERS => $this->faturaReminderService->dispatch(),
                self::TASK_PROCESS_EXPIRED_SUBSCRIPTIONS => $this->subscriptionExpirationService->processExpiredSubscriptions(),
                self::TASK_GENERATE_RECURRING_LANCAMENTOS => [
                    'created' => $this->lancamentoCreationService->estenderRecorrenciasInfinitas(),
                ],
                self::TASK_PROCESS_RECURRING_CARD_ITEMS => $this->recorrenciaCartaoService->processRecurringCardItems(),
                self::TASK_DISPATCH_SCHEDULED_CAMPAIGNS => $this->notificationService->processScheduledCampaigns(),
                default => throw new \InvalidArgumentException("Tarefa de scheduler invalida: {$task}"),
            };

            LogService::info('[SchedulerTaskRunner] Tarefa executada', [
                'task' => $task,
                'success' => true,
            ]);

            return [
                'task' => $task,
                'success' => true,
                'started_at' => $startedAt,
                'finished_at' => date('c'),
                'result' => $result,
            ];
        } catch (Throwable $exception) {
            LogService::captureException($exception, LogCategory::GENERAL, [
                'task' => $task,
                'component' => 'scheduler',
            ]);

            return [
                'task' => $task,
                'success' => false,
                'started_at' => $startedAt,
                'finished_at' => date('c'),
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function runAll(array $options = []): array
    {
        $results = [];

        foreach ($this->defaultRunOrder() as $task) {
            $results[] = $this->runTask($task, $options);
        }

        $successCount = count(array_filter($results, static fn(array $result): bool => ($result['success'] ?? false) === true));

        return [
            'success' => $successCount === count($results),
            'executed' => count($results),
            'successful' => $successCount,
            'failed' => count($results) - $successCount,
            'results' => $results,
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function runBirthdayDispatch(array $options): array
    {
        $service = $this->notificationService;

        if (($options['preview'] ?? false) === true) {
            return [
                'preview' => true,
                'birthday_users' => $service->getBirthdayUsers(),
                'upcoming_birthdays' => $service->getUpcomingBirthdays(7),
            ];
        }

        $sendEmail = !($options['no_email'] ?? false);

        return $service->processBirthdayNotifications($sendEmail);
    }

    /**
     * @return list<string>
     */
    private function defaultRunOrder(): array
    {
        return [
            self::TASK_DISPATCH_REMINDERS,
            self::TASK_DISPATCH_BIRTHDAYS,
            self::TASK_DISPATCH_FATURA_REMINDERS,
            self::TASK_PROCESS_EXPIRED_SUBSCRIPTIONS,
            self::TASK_GENERATE_RECURRING_LANCAMENTOS,
            self::TASK_PROCESS_RECURRING_CARD_ITEMS,
            self::TASK_DISPATCH_SCHEDULED_CAMPAIGNS,
        ];
    }
}
