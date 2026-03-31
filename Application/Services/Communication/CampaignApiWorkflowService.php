<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Models\Cupom;
use Application\Models\MessageCampaign;
use Application\Services\Infrastructure\LogService;
use Carbon\Carbon;
use Throwable;

class CampaignApiWorkflowService
{
    public function __construct(
        private readonly NotificationService $notificationService = new NotificationService()
    ) {
    }

    public function listCampaigns(int $page, int $perPage): array
    {
        return $this->notificationService->listCampaigns($page, $perPage);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createCampaign(int $adminId, string $adminName, array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return $this->failure('O título é obrigatório');
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return $this->failure('A mensagem é obrigatória');
        }

        if (strlen($title) > 255) {
            return $this->failure('O título deve ter no máximo 255 caracteres');
        }

        $type = $payload['type'] ?? MessageCampaign::TYPE_INFO;
        $sendNotification = (bool) ($payload['send_notification'] ?? true);
        $sendEmail = (bool) ($payload['send_email'] ?? false);
        $link = !empty($payload['link']) ? trim((string) $payload['link']) : null;
        $linkText = !empty($payload['link_text']) ? trim((string) $payload['link_text']) : null;

        $linkValidation = $this->validateLink($link);
        if ($linkValidation !== null) {
            return $linkValidation;
        }

        $filters = $this->extractFilters($payload['filters'] ?? []);
        $filterValidation = $this->validateStoreFilters($filters);
        if ($filterValidation !== null) {
            return $filterValidation;
        }

        if (!$sendNotification && !$sendEmail) {
            return $this->failure('Selecione pelo menos um canal de envio (notificação ou e-mail)');
        }

        $validTypes = array_keys(MessageCampaign::getTypes());
        if (!in_array($type, $validTypes, true)) {
            return $this->failure('Tipo de campanha inválido');
        }

        $scheduleResolution = $this->resolveScheduledAt($payload['scheduled_at'] ?? null);
        if (!$scheduleResolution['success']) {
            return $scheduleResolution;
        }

        $couponResolution = $this->resolveCouponId($payload['cupom_id'] ?? null);
        if (!$couponResolution['success']) {
            return $couponResolution;
        }

        $campaign = $this->notificationService->sendCampaign(
            $adminId,
            $title,
            $message,
            $type,
            $filters,
            $sendNotification,
            $sendEmail,
            $link,
            $linkText,
            $scheduleResolution['value'],
            $couponResolution['value']
        );

        if ($scheduleResolution['value'] !== null) {
            LogService::info('Campanha agendada', [
                'campaign_id' => $campaign->id,
                'admin_id' => $adminId,
                'admin_name' => $adminName,
                'scheduled_at' => $scheduleResolution['value'],
            ]);

            return [
                'success' => true,
                'message' => 'Campanha agendada com sucesso',
                'data' => [
                    'campaign_id' => $campaign->id,
                    'title' => $campaign->title,
                    'status' => $campaign->status,
                    'scheduled_at' => $campaign->scheduled_at->format('d/m/Y H:i'),
                ],
            ];
        }

        $this->logImmediateCampaignResult($campaign, $adminId, $adminName);

        return [
            'success' => true,
            'message' => $this->buildImmediateCampaignMessage($campaign),
            'data' => [
                'campaign_id' => $campaign->id,
                'title' => $campaign->title,
                'total_recipients' => $campaign->total_recipients,
                'emails_sent' => $campaign->emails_sent,
                'emails_failed' => $campaign->emails_failed,
                'status' => $campaign->status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function processDueCampaigns(): array
    {
        $result = $this->notificationService->processScheduledCampaigns();

        if (($result['processed'] ?? 0) > 0 || ($result['stuck_recovered'] ?? 0) > 0) {
            LogService::info('Fila de campanhas sincronizada manualmente', $result);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function preview(array $query): array
    {
        $filters = $this->extractFilters($query);

        return [
            'count' => $this->notificationService->countUsersByFilters($filters),
            'filters' => $filters,
        ];
    }

    public function getStats(): array
    {
        return $this->notificationService->getStats();
    }

    public function getOptions(): array
    {
        return [
            'types' => MessageCampaign::getTypes(),
            'plans' => MessageCampaign::getPlanOptions(),
            'statuses' => MessageCampaign::getStatusOptions(),
            'inactive_days' => MessageCampaign::getInactiveDaysOptions(),
        ];
    }

    public function showCampaign(int $id): ?array
    {
        $campaign = $this->findCampaignWithRelations($id);
        if (!$campaign) {
            return null;
        }

        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'message' => $campaign->message,
            'link' => $campaign->link,
            'link_text' => $campaign->link_text,
            'type' => $campaign->type,
            'icon' => $campaign->icon,
            'color' => $campaign->color,
            'filters' => $campaign->filters,
            'filters_description' => $campaign->filters_description,
            'send_notification' => $campaign->send_notification,
            'send_email' => $campaign->send_email,
            'channels_description' => $campaign->channels_description,
            'total_recipients' => $campaign->total_recipients,
            'notifications_read' => $campaign->notifications_read,
            'read_rate' => $campaign->read_rate,
            'emails_sent' => $campaign->emails_sent,
            'emails_failed' => $campaign->emails_failed,
            'email_success_rate' => $campaign->email_success_rate,
            'status' => $campaign->status,
            'status_badge' => $campaign->status_badge,
            'is_scheduled' => $campaign->is_scheduled,
            'scheduled_at' => $campaign->scheduled_at?->format('d/m/Y H:i'),
            'cupom' => $campaign->cupom ? [
                'id' => $campaign->cupom->id,
                'codigo' => $campaign->cupom->codigo,
                'desconto_formatado' => $campaign->cupom->getDescontoFormatado(),
            ] : null,
            'creator' => [
                'id' => $campaign->creator->id ?? null,
                'nome' => $campaign->creator->nome ?? 'Sistema',
                'email' => $campaign->creator->email ?? null,
            ],
            'sent_at' => $campaign->sent_at?->format('d/m/Y H:i'),
            'created_at' => $campaign->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelScheduled(int $id): array
    {
        $campaign = $this->findCampaign($id);
        if (!$campaign) {
            return $this->failure('Campanha não encontrada', 404);
        }

        if (!$campaign->is_scheduled) {
            return $this->failure('Esta campanha não está agendada');
        }

        $campaign->status = MessageCampaign::STATUS_CANCELLED;
        $campaign->scheduled_at = null;
        $campaign->save();

        LogService::info('Campanha agendada cancelada', [
            'campaign_id' => $campaign->id,
        ]);

        return [
            'success' => true,
            'message' => 'Campanha agendada cancelada com sucesso',
            'data' => [
                'campaign_id' => $campaign->id,
                'status' => $campaign->status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getBirthdays(int $days): array
    {
        $days = min(max($days, 1), 30);
        $today = $this->notificationService->getBirthdayUsers();
        $upcoming = $this->notificationService->getUpcomingBirthdays($days);

        return [
            'today' => $today,
            'today_count' => count($today),
            'upcoming' => $upcoming,
            'upcoming_count' => count($upcoming),
            'days_range' => $days,
        ];
    }

    public function sendBirthdays(bool $sendEmail): array
    {
        $result = $this->notificationService->processBirthdayNotifications($sendEmail);

        LogService::info('Notificações de aniversário disparadas manualmente', [
            'notifications_sent' => $result['notifications_sent'] ?? 0,
            'send_email' => $sendEmail,
        ]);

        return $result;
    }

    private function validateLink(?string $link): ?array
    {
        if ($link === null) {
            return null;
        }

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return $this->failure('O link informado não é uma URL válida');
        }

        $scheme = parse_url($link, PHP_URL_SCHEME);
        if (!in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return $this->failure('O link deve usar protocolo http ou https');
        }

        return null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{plan:string,status:string,days_inactive:?int,email_verified:?bool}
     */
    private function extractFilters(array $input): array
    {
        return [
            'plan' => $input['plan'] ?? 'all',
            'status' => $input['status'] ?? 'all',
            'days_inactive' => !empty($input['days_inactive'])
                ? (int) $input['days_inactive']
                : null,
            'email_verified' => isset($input['email_verified'])
                ? filter_var($input['email_verified'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $input['email_verified']
                : null,
        ];
    }

    private function validateStoreFilters(array $filters): ?array
    {
        if (!in_array($filters['plan'], ['all', 'free', 'pro'], true)) {
            return $this->failure('Filtro de plano inválido');
        }

        if (!in_array($filters['status'], ['all', 'active', 'inactive'], true)) {
            return $this->failure('Filtro de status inválido');
        }

        if ($filters['days_inactive'] !== null && ($filters['days_inactive'] < 1 || $filters['days_inactive'] > 365)) {
            return $this->failure('Dias de inatividade deve estar entre 1 e 365');
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveScheduledAt(mixed $rawValue): array
    {
        if ($rawValue === null || $rawValue === '') {
            return [
                'success' => true,
                'value' => null,
            ];
        }

        try {
            $scheduledDate = Carbon::parse((string) $rawValue);
            if ($scheduledDate->lte(Carbon::now())) {
                return $this->failure('A data de agendamento deve ser no futuro');
            }

            return [
                'success' => true,
                'value' => $scheduledDate->format('Y-m-d H:i:s'),
            ];
        } catch (Throwable) {
            return $this->failure('Data de agendamento inválida');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCouponId(mixed $rawCouponId): array
    {
        if (empty($rawCouponId)) {
            return [
                'success' => true,
                'value' => null,
            ];
        }

        $coupon = Cupom::find((int) $rawCouponId);
        if (!$coupon || !$coupon->isValid()) {
            return $this->failure('Cupom inválido ou expirado');
        }

        return [
            'success' => true,
            'value' => $coupon->id,
        ];
    }

    /**
     * @return array{success:false,status:int,message:string}
     */
    private function failure(string $message, int $status = 400): array
    {
        return [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];
    }

    private function buildImmediateCampaignMessage(MessageCampaign $campaign): string
    {
        return match ($campaign->status) {
            MessageCampaign::STATUS_PARTIAL => 'Campanha enviada parcialmente. Confira as falhas no histórico.',
            MessageCampaign::STATUS_FAILED => 'Campanha processada, mas falhou em todos os canais selecionados.',
            default => 'Campanha enviada com sucesso',
        };
    }

    private function logImmediateCampaignResult(MessageCampaign $campaign, int $adminId, string $adminName): void
    {
        $context = [
            'campaign_id' => $campaign->id,
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'total_recipients' => $campaign->total_recipients,
            'emails_sent' => $campaign->emails_sent,
            'emails_failed' => $campaign->emails_failed,
            'status' => $campaign->status,
        ];

        if ($campaign->status === MessageCampaign::STATUS_FAILED) {
            LogService::warning('Campanha falhou', $context);
            return;
        }

        if ($campaign->status === MessageCampaign::STATUS_PARTIAL) {
            LogService::warning('Campanha enviada parcialmente', $context);
            return;
        }

        LogService::info('Campanha enviada', $context);
    }

    protected function findCampaign(int $id): ?MessageCampaign
    {
        return MessageCampaign::find($id);
    }

    protected function findCampaignWithRelations(int $id): ?MessageCampaign
    {
        return MessageCampaign::with(['creator:id,nome,email', 'cupom:id,codigo,tipo_desconto,valor_desconto'])->find($id);
    }
}
