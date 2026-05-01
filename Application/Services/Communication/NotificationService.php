<?php

declare(strict_types=1);

namespace Application\Services\Communication;

use Application\Config\CommunicationRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Models\Notification;
use Application\Models\MessageCampaign;
use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Application\Services\Communication\CommunicationServiceProvider;
use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Mail\EmailTemplate;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

/**
 * NotificationService
 * 
 * Gerencia o sistema de notificações internas e campanhas de mensagens.
 * Responsável por:
 * - Criar notificações individuais
 * - Processar campanhas em lote
 * - Filtrar usuários por segmento
 * - Enviar emails de campanha
 */
class NotificationService
{
    private MailService $mailService;
    private LoggerInterface $logger;
    private CampaignDeliveryStatusResolver $deliveryStatusResolver;
    private SchedulerExecutionLock $schedulerLock;
    private CommunicationRuntimeConfig $runtimeConfig;
    private ?array $cachedProUserIds = null;

    public function __construct(
        ?MailService $mailService = null,
        ?LoggerInterface $logger = null,
        ?CampaignDeliveryStatusResolver $deliveryStatusResolver = null,
        ?SchedulerExecutionLock $schedulerLock = null,
        ?CommunicationRuntimeConfig $runtimeConfig = null
    ) {
        $this->mailService = ApplicationContainer::resolveOrNew($mailService, MailService::class);
        $this->logger = $this->resolveLogger($logger);
        $this->deliveryStatusResolver = ApplicationContainer::resolveOrNew(
            $deliveryStatusResolver,
            CampaignDeliveryStatusResolver::class
        );
        $this->schedulerLock = ApplicationContainer::resolveOrNew($schedulerLock, SchedulerExecutionLock::class);
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, CommunicationRuntimeConfig::class);
    }

    /**
     * Cria uma notificação individual para um usuário
     */
    public function createNotification(
        int $userId,
        string $title,
        string $message,
        string $type = Notification::TYPE_INFO,
        ?string $link = null,
        ?int $campaignId = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'campaign_id' => $campaignId,
            'is_read' => false,
        ]);
    }

    /**
     * Obtém notificações de um usuário
     */
    public function getUserNotifications(int $userId, int $limit = 20, bool $unreadOnly = false): array
    {
        $query = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->get()->toArray();
    }

    /**
     * Conta notificações não lidas de um usuário
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Marca uma notificação como lida
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Marca todas as notificações de um usuário como lidas
     */
    public function markAllAsRead(int $userId): int
    {
        // Obter notificações não lidas com campaign_id para atualizar contadores
        $unreadWithCampaign = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->whereNotNull('campaign_id')
            ->get(['id', 'campaign_id']);

        // Atualizar contadores das campanhas
        $campaignCounts = $unreadWithCampaign->groupBy('campaign_id')->map->count();
        foreach ($campaignCounts as $campaignId => $count) {
            MessageCampaign::where('id', $campaignId)->increment('notifications_read', $count);
        }

        // Marcar todas como lidas
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
    }

    /**
     * Deleta uma notificação
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        return Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    private function resolveLogger(?LoggerInterface $logger): LoggerInterface
    {
        if ($logger instanceof LoggerInterface) {
            return $logger;
        }

        $container = ApplicationContainer::ensureProviderRegistered(CommunicationServiceProvider::class);

        return $container->make(LoggerInterface::class);
    }

    /**
     * Deleta todas as notificações lidas de um usuário
     */
    public function deleteReadNotifications(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', true)
            ->delete();
    }

    // =========================================================================
    // CAMPANHAS
    // =========================================================================

    /**
     * Cria e envia uma campanha de mensagens
     */
    public function sendCampaign(
        int $adminId,
        string $title,
        string $message,
        string $type = MessageCampaign::TYPE_INFO,
        array $filters = [],
        bool $sendNotification = true,
        bool $sendEmail = false,
        ?string $link = null,
        ?string $linkText = null,
        ?string $scheduledAt = null,
        ?int $cupomId = null
    ): MessageCampaign {
        // Validar admin
        $admin = Usuario::find($adminId);
        if (!$admin || $admin->is_admin != 1) {
            throw new Exception('Apenas administradores podem enviar campanhas.');
        }

        // Se agendada, criar como scheduled e retornar sem enviar
        if ($scheduledAt) {
            $campaign = MessageCampaign::create([
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'link_text' => $linkText,
                'type' => $type,
                'filters' => $filters,
                'send_notification' => $sendNotification,
                'send_email' => $sendEmail,
                'created_by' => $adminId,
                'cupom_id' => $cupomId,
                'status' => MessageCampaign::STATUS_SCHEDULED,
                'scheduled_at' => $scheduledAt,
            ]);

            $this->logger->info('[NotificationService] Campanha agendada', [
                'campaign_id' => $campaign->id,
                'scheduled_at' => $scheduledAt,
            ]);

            return $campaign;
        }

        // Criar campanha para envio imediato
        $campaign = MessageCampaign::create([
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'link_text' => $linkText,
            'type' => $type,
            'filters' => $filters,
            'send_notification' => $sendNotification,
            'send_email' => $sendEmail,
            'created_by' => $adminId,
            'cupom_id' => $cupomId,
            'status' => MessageCampaign::STATUS_SENDING,
        ]);

        $this->logger->info('[NotificationService] Campanha criada', [
            'campaign_id' => $campaign->id,
            'title' => $title,
            'filters' => $filters,
        ]);

        return $this->executeCampaignSend($campaign);
    }

    /**
     * Executa o envio efetivo de uma campanha (usado por envio imediato e agendado)
     */
    public function executeCampaignSend(MessageCampaign $campaign): MessageCampaign
    {
        try {
            // Contar destinatários primeiro (query leve)
            $totalRecipients = $this->countUsersByFilters($campaign->filters ?? []);

            $campaign->total_recipients = $totalRecipients;
            $campaign->status = MessageCampaign::STATUS_SENDING;
            $campaign->save();

            if ($totalRecipients === 0) {
                $campaign->status = MessageCampaign::STATUS_SENT;
                $campaign->sent_at = Carbon::now();
                $campaign->save();

                $this->logger->warning('[NotificationService] Campanha sem destinatários', [
                    'campaign_id' => $campaign->id,
                ]);

                return $campaign;
            }

            $notificationsDelivered = 0;
            $emailEligible = 0;
            $emailsSent = 0;
            $emailsFailed = 0;

            // Carregar cupom vinculado (se houver)
            $cupom = $campaign->cupom_id ? $campaign->cupom : null;

            // Acrescentar info do cupom à mensagem da notificação
            $notificationMessage = $campaign->message;
            if ($cupom) {
                $notificationMessage .= "\n\nUse o cupom {$cupom->codigo} para ganhar {$cupom->getDescontoFormatado()} de desconto!";
            }

            // Processar em lotes para evitar estouro de memória
            $this->buildFilteredUsersQuery($campaign->filters ?? [])
                ->chunk(200, function ($users) use ($campaign, $notificationMessage, &$notificationsDelivered, &$emailEligible, &$emailsSent, &$emailsFailed) {
                    foreach ($users as $user) {
                        // Criar notificação interna
                        if ($campaign->send_notification) {
                            $this->createNotification(
                                $user->id,
                                $campaign->title,
                                $notificationMessage,
                                $campaign->type,
                                $campaign->link,
                                $campaign->id
                            );
                            $notificationsDelivered++;
                        }

                        // Enviar email
                        if ($campaign->send_email && !empty($user->email)) {
                            $emailEligible++;

                            try {
                                $emailSent = $this->sendCampaignEmail($user, $campaign);

                                if ($emailSent) {
                                    $emailsSent++;
                                } else {
                                    $emailsFailed++;
                                    $this->logger->warning('[NotificationService] MailService retornou falha sem excecao', [
                                        'campaign_id' => $campaign->id,
                                        'user_id' => $user->id,
                                    ]);
                                }
                            } catch (Exception $e) {
                                $emailsFailed++;
                                $this->logger->error('[NotificationService] Falha ao enviar email', [
                                    'campaign_id' => $campaign->id,
                                    'user_id' => $user->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                });

            // Atualizar estatísticas
            $campaign->emails_sent = $emailsSent;
            $campaign->emails_failed = $emailsFailed;
            $campaign->status = $this->deliveryStatusResolver->resolve(
                (bool) $campaign->send_notification,
                $notificationsDelivered,
                (bool) $campaign->send_email,
                $emailEligible,
                $emailsSent,
                $emailsFailed
            );
            $campaign->sent_at = Carbon::now();
            $campaign->save();

            $this->logger->info('[NotificationService] Campanha enviada', [
                'campaign_id' => $campaign->id,
                'total_recipients' => $totalRecipients,
                'notifications_delivered' => $notificationsDelivered,
                'email_eligible' => $emailEligible,
                'emails_sent' => $emailsSent,
                'emails_failed' => $emailsFailed,
                'status' => $campaign->status,
            ]);

            return $campaign;
        } catch (Exception $e) {
            $campaign->status = MessageCampaign::STATUS_FAILED;
            $campaign->save();

            $this->logger->error('[NotificationService] Falha ao processar campanha', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Processa campanhas agendadas cujo horário já chegou
     */
    public function processScheduledCampaigns(): array
    {
        $stats = ['processed' => 0, 'sent' => 0, 'partial' => 0, 'failed' => 0, 'stuck_recovered' => 0];

        try {
            $this->schedulerLock->acquire('dispatch-scheduled-campaigns');
        } catch (Exception) {
            $this->logger->info('[NotificationService] Fila de campanhas agendadas ja esta em processamento');
            return $stats;
        }

        try {
            // Recuperar campanhas presas em "sending" por mais de 10 minutos
            $stuckCampaigns = MessageCampaign::where('status', MessageCampaign::STATUS_SENDING)
                ->where('updated_at', '<', Carbon::now()->subMinutes(10))
                ->get();

            foreach ($stuckCampaigns as $stuck) {
                $stuck->status = MessageCampaign::STATUS_FAILED;
                $stuck->save();
                $stats['stuck_recovered']++;

                $this->logger->warning('[NotificationService] Campanha presa em sending recuperada', [
                    'campaign_id' => $stuck->id,
                    'updated_at' => $stuck->updated_at?->toDateTimeString(),
                ]);
            }

            // Processar campanhas agendadas cujo horário já chegou
            $campaigns = MessageCampaign::readyToSend()->get();

            foreach ($campaigns as $campaign) {
                $stats['processed']++;
                try {
                    $processedCampaign = $this->executeCampaignSend($campaign);

                    if ($processedCampaign->status === MessageCampaign::STATUS_SENT) {
                        $stats['sent']++;
                    } elseif ($processedCampaign->status === MessageCampaign::STATUS_PARTIAL) {
                        $stats['partial']++;
                    } else {
                        $stats['failed']++;
                    }

                    $this->logger->info('[NotificationService] Campanha agendada enviada', [
                        'campaign_id' => $campaign->id,
                        'status' => $processedCampaign->status,
                    ]);
                } catch (Exception $e) {
                    $stats['failed']++;
                    $this->logger->error('[NotificationService] Falha na campanha agendada', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $stats;
        } finally {
            $this->schedulerLock->release();
        }
    }

    /**
     * Busca usuários que correspondem aos filtros
     * 
     * @return Usuario[]
     */
    public function getUsersByFilters(array $filters): array
    {
        return $this->buildFilteredUsersQuery($filters)->get()->all();
    }

    /**
     * Constrói a query filtrada de usuários (reutilizável por get e count)
     *
     * @return Builder<Usuario>
     */
    private function buildFilteredUsersQuery(array $filters): Builder
    {
        $query = Usuario::query()
            ->where('usuarios.id', '>', 0); // Garantir query válida

        // Filtro por plano
        $plan = $filters['plan'] ?? 'all';
        if ($plan === 'free') {
            $proUserIds = $this->getProUserIds();
            if (!empty($proUserIds)) {
                $query->whereNotIn('usuarios.id', $proUserIds);
            }
        } elseif ($plan === 'pro') {
            $proUserIds = $this->getProUserIds();
            if (empty($proUserIds)) {
                $query->whereRaw('1 = 0'); // Nenhum usuário PRO
            } else {
                $query->whereIn('usuarios.id', $proUserIds);
            }
        }

        // Filtro por status de atividade (LEFT JOIN com user_progress)
        $status = $filters['status'] ?? 'all';
        $daysInactive = $filters['days_inactive'] ?? null;

        if ($status === 'inactive' || $daysInactive) {
            $days = $daysInactive ?: 30;
            $cutoffDate = Carbon::now()->subDays($days)->toDateTimeString();

            // Usuários inativos: sem registro em user_progress OU last_activity_date < cutoff
            $query->leftJoin('user_progress', 'usuarios.id', '=', 'user_progress.user_id')
                ->where(function ($q) use ($cutoffDate) {
                    $q->whereNull('user_progress.user_id')
                        ->orWhereNull('user_progress.last_activity_date')
                        ->orWhere('user_progress.last_activity_date', '<', $cutoffDate);
                })
                ->select('usuarios.*');
        } elseif ($status === 'active') {
            $cutoffDate = Carbon::now()->subDays(7)->toDateTimeString();
            $query->join('user_progress', 'usuarios.id', '=', 'user_progress.user_id')
                ->where('user_progress.last_activity_date', '>=', $cutoffDate)
                ->select('usuarios.*');
        }

        // Filtro por email verificado
        $emailVerified = $filters['email_verified'] ?? null;
        if ($emailVerified === true) {
            $query->whereNotNull('usuarios.email_verified_at');
        } elseif ($emailVerified === false) {
            $query->whereNull('usuarios.email_verified_at');
        }

        // Excluir admins
        $query->where(function ($q) {
            $q->where('usuarios.is_admin', '!=', 1)
                ->orWhereNull('usuarios.is_admin');
        });

        return $query;
    }

    /**
     * Retorna IDs de usuários com plano PRO ativo (com memoização)
     */
    private function getProUserIds(): array
    {
        if ($this->cachedProUserIds !== null) {
            return $this->cachedProUserIds;
        }

        // Buscar o ID do plano PRO para não confundir com assinaturas do plano free
        $proPlan = Plano::where('code', 'pro')->first();

        if (!$proPlan) {
            $this->logger->warning('[NotificationService] Plano PRO não encontrado');
            $this->cachedProUserIds = [];
            return [];
        }

        $this->cachedProUserIds = AssinaturaUsuario::where('plano_id', $proPlan->id)
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_CANCELED // Ainda ativa até data de expiração
            ])
            ->where('renova_em', '>', Carbon::now())
            ->pluck('user_id')
            ->unique()
            ->toArray();

        return $this->cachedProUserIds;
    }

    /**
     * Conta usuários por filtro (preview antes de enviar)
     */
    public function countUsersByFilters(array $filters): int
    {
        return $this->buildFilteredUsersQuery($filters)->count();
    }

    /**
     * Envia email de campanha para um usuário
     */
    private function sendCampaignEmail(Usuario $user, MessageCampaign $campaign): bool
    {
        $nome = $user->primeiro_nome ?? $user->nome ?? 'Usuário';
        $safeName = htmlspecialchars($nome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $subject = $campaign->title;

        // Construir conteúdo do email
        $content = EmailTemplate::row(
            'Mensagem',
            nl2br(htmlspecialchars($campaign->message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            false
        );

        // Adicionar CTA se existir
        if (!empty($campaign->link)) {
            $buttonText = $campaign->link_text ?: 'Saiba mais';
            $content .= EmailTemplate::button($buttonText, $campaign->link);
        }

        // Adicionar bloco de cupom se vinculado
        $cupom = $campaign->cupom_id ? $campaign->cupom : null;
        if ($cupom) {
            $cupomCode = htmlspecialchars($cupom->codigo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $cupomDesconto = htmlspecialchars($cupom->getDescontoFormatado(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $content .= '<div style="margin: 20px 0; padding: 16px; background: linear-gradient(135deg, #f59e0b15, #f59e0b08); border: 2px dashed #f59e0b; border-radius: 12px; text-align: center;">'
                . '<p style="margin: 0 0 8px; font-size: 14px; color: #6b7280;">Use o cupom abaixo e ganhe <strong>' . $cupomDesconto . ' de desconto</strong>:</p>'
                . '<p style="margin: 0; font-size: 24px; font-weight: 700; color: #f59e0b; letter-spacing: 2px; font-family: monospace;">' . $cupomCode . '</p>'
                . '</div>';
        }

        // Determinar cor do header baseado no tipo
        $headerColor = match ($campaign->type) {
            MessageCampaign::TYPE_PROMO => '#f59e0b',
            MessageCampaign::TYPE_UPDATE => '#8b5cf6',
            MessageCampaign::TYPE_ALERT => '#ef4444',
            MessageCampaign::TYPE_SUCCESS => '#10b981',
            MessageCampaign::TYPE_REMINDER => '#3b82f6',
            default => '#092741',
        };

        // Determinar título do header
        $headerTitle = match ($campaign->type) {
            MessageCampaign::TYPE_PROMO => '🎁 Oferta Especial',
            MessageCampaign::TYPE_UPDATE => '🚀 Novidades',
            MessageCampaign::TYPE_ALERT => '⚠️ Aviso Importante',
            MessageCampaign::TYPE_SUCCESS => '✅ Informação',
            MessageCampaign::TYPE_REMINDER => '🔔 Lembrete',
            default => '📢 Comunicado',
        };

        $html = EmailTemplate::wrap(
            $subject,
            $headerColor,
            $headerTitle,
            "Olá {$safeName}, temos uma mensagem para você!",
            $content,
            'Este e-mail foi enviado pela equipe Lukrato. Você recebeu porque faz parte da nossa comunidade.'
        );

        $text = "{$campaign->title}\n\n"
            . "{$campaign->message}\n\n"
            . ($cupom ? "Cupom: {$cupom->codigo} ({$cupom->getDescontoFormatado()} de desconto)\n\n" : '')
            . ($campaign->link ? "Acesse: {$campaign->link}\n\n" : '')
            . "Equipe Lukrato";

        return $this->mailService->send(
            $user->email,
            $nome,
            $subject,
            $html,
            $text
        );
    }

    // =========================================================================
    // HISTÓRICO E ESTATÍSTICAS
    // =========================================================================

    /**
     * Lista campanhas com paginação
     */
    public function listCampaigns(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $campaigns = MessageCampaign::with('creator:id,nome,email')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $total = MessageCampaign::count();

        return [
            'campaigns' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'message' => $campaign->message,
                    'type' => $campaign->type,
                    'icon' => $campaign->icon,
                    'color' => $campaign->color,
                    'filters_description' => $campaign->filters_description,
                    'channels_description' => $campaign->channels_description,
                    'total_recipients' => $campaign->total_recipients,
                    'notifications_read' => $campaign->notifications_read,
                    'read_rate' => $campaign->read_rate,
                    'emails_sent' => $campaign->emails_sent,
                    'emails_failed' => $campaign->emails_failed,
                    'status' => $campaign->status,
                    'status_badge' => $campaign->status_badge,
                    'is_scheduled' => $campaign->is_scheduled,
                    'was_scheduled' => $campaign->was_scheduled,
                    'send_email' => $campaign->send_email,
                    'send_notification' => $campaign->send_notification,
                    'scheduled_at' => $campaign->scheduled_at?->format('d/m/Y H:i'),
                    'creator_name' => $campaign->creator->nome ?? 'Sistema',
                    'sent_at' => $campaign->sent_at?->format('d/m/Y H:i'),
                    'created_at' => $campaign->created_at->format('d/m/Y H:i'),
                ];
            })->toArray(),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Obtém estatísticas gerais de notificações
     */
    public function getStats(): array
    {
        $totalCampaigns = MessageCampaign::count();
        $totalNotifications = Notification::count();
        $totalRead = Notification::where('is_read', true)->count();
        $totalUnread = Notification::where('is_read', false)->count();

        // Campanhas do último mês
        $lastMonth = Carbon::now()->subMonth();
        $campaignsLastMonth = MessageCampaign::where('created_at', '>=', $lastMonth)->count();

        // Taxa de leitura geral
        $readRate = $totalNotifications > 0
            ? round(($totalRead / $totalNotifications) * 100, 1)
            : 0;

        return [
            'total_campaigns' => $totalCampaigns,
            'campaigns_last_month' => $campaignsLastMonth,
            'total_notifications' => $totalNotifications,
            'total_read' => $totalRead,
            'total_unread' => $totalUnread,
            'read_rate' => $readRate,
        ];
    }

    // =========================================================================
    // TRIGGERS AUTOMÁTICOS (PREPARAÇÃO PARA FUTURO)
    // =========================================================================

    /**
     * Tipo de trigger automático (enum-like para futuro)
     */
    public const TRIGGER_PLAN_LIMIT_REACHED = 'plan_limit_reached';
    public const TRIGGER_DAYS_INACTIVE = 'days_inactive';
    public const TRIGGER_SUBSCRIPTION_EXPIRING = 'subscription_expiring';
    public const TRIGGER_NEW_FEATURE = 'new_feature';

    /**
     * Estrutura preparada para triggers automáticos (não implementado ainda)
     * 
     * Este método serve como base para futura implementação de
     * notificações automáticas baseadas em eventos do sistema.
     */
    public function getTriggerTypes(): array
    {
        return [
            self::TRIGGER_PLAN_LIMIT_REACHED => [
                'name' => 'Limite do plano atingido',
                'description' => 'Quando usuário gratuito atinge limite de lançamentos/contas',
                'suggested_type' => MessageCampaign::TYPE_PROMO,
            ],
            self::TRIGGER_DAYS_INACTIVE => [
                'name' => 'Dias sem atividade',
                'description' => 'Quando usuário fica X dias sem usar o sistema',
                'suggested_type' => MessageCampaign::TYPE_REMINDER,
            ],
            self::TRIGGER_SUBSCRIPTION_EXPIRING => [
                'name' => 'Assinatura expirando',
                'description' => 'Quando assinatura PRO está próxima do vencimento',
                'suggested_type' => MessageCampaign::TYPE_ALERT,
            ],
            self::TRIGGER_NEW_FEATURE => [
                'name' => 'Nova funcionalidade',
                'description' => 'Anunciar novo recurso do sistema',
                'suggested_type' => MessageCampaign::TYPE_UPDATE,
            ],
        ];
    }

    // =========================================================================
    // NOTIFICAÇÕES DE ANIVERSÁRIO
    // =========================================================================

    public const TRIGGER_BIRTHDAY = 'birthday';

    /**
     * Processa notificações de aniversário para todos os aniversariantes do dia
     * 
     * @param bool $sendEmail Se deve enviar email além da notificação interna
     * @return array Estatísticas do processamento
     */
    public function processBirthdayNotifications(bool $sendEmail = true): array
    {
        $today = Carbon::today();
        $stats = [
            'date' => $today->format('d/m/Y'),
            'birthday_users' => 0,
            'notifications_sent' => 0,
            'emails_sent' => 0,
            'emails_failed' => 0,
            'already_notified' => 0,
            'no_birthdate' => 0,
        ];

        // Buscar usuários que fazem aniversário hoje (mesmo dia e mês)
        $birthdayUsers = Usuario::whereNotNull('data_nascimento')
            ->whereRaw('MONTH(data_nascimento) = ?', [$today->month])
            ->whereRaw('DAY(data_nascimento) = ?', [$today->day])
            ->get();

        $stats['birthday_users'] = $birthdayUsers->count();

        foreach ($birthdayUsers as $user) {
            // Verificar se já foi notificado este ano
            $alreadyNotified = Notification::where('user_id', $user->id)
                ->where('type', Notification::TYPE_BIRTHDAY)
                ->whereYear('created_at', $today->year)
                ->exists();

            if ($alreadyNotified) {
                $stats['already_notified']++;
                continue;
            }

            // Calcular idade
            $birthDate = Carbon::parse($user->data_nascimento);
            $age = $birthDate->age;

            // Criar notificação interna
            $this->sendBirthdayNotification($user, $age);
            $stats['notifications_sent']++;

            // Enviar email se configurado
            if ($sendEmail && !empty($user->email)) {
                try {
                    $emailSent = $this->sendBirthdayEmail($user, $age);

                    if ($emailSent) {
                        $stats['emails_sent']++;
                    } else {
                        $stats['emails_failed']++;
                        $this->logger->warning(
                            "Envio de email de aniversario retornou false para {$user->email}"
                        );
                    }
                } catch (Exception $e) {
                    $stats['emails_failed']++;
                    $this->logger->error("Erro ao enviar email de aniversário para {$user->email}: " . $e->getMessage());
                }
            }
        }

        return $stats;
    }

    /**
     * Envia notificação interna de aniversário
     */
    private function sendBirthdayNotification(Usuario $user, int $age): Notification
    {
        $firstName = explode(' ', trim($user->nome))[0];

        $title = "🎂 Feliz Aniversário, {$firstName}!";
        $message = "Hoje é um dia muito especial! " .
            "O Lukrato deseja a você um feliz aniversário repleto de realizações. " .
            "Que seus {$age} anos sejam celebrados com muita alegria! 🎉🎈";

        return $this->createNotification(
            $user->id,
            $title,
            $message,
            Notification::TYPE_BIRTHDAY,
            null, // sem link
            null  // sem campanha
        );
    }

    /**
     * Envia email de aniversário
     */
    private function sendBirthdayEmail(Usuario $user, int $age): bool
    {
        $firstName = explode(' ', trim($user->nome))[0];

        $subject = "🎂 Feliz Aniversário, {$firstName}! - Lukrato";

        $htmlContent = $this->buildBirthdayEmailHtml($user, $firstName, $age);

        return $this->mailService->send(
            $user->email,
            $user->nome,
            $subject,
            $htmlContent
        );
    }

    /**
     * Constrói o HTML do email de aniversário
     */
    private function buildBirthdayEmailHtml(Usuario $user, string $firstName, int $age): string
    {
        $year = date('Y');
        $baseUrl = $this->runtimeConfig->appUrl();
        $dashboardUrl = ($baseUrl !== '' ? $baseUrl : 'https://lukrato.com.br') . '/dashboard';

        $content = "
            <div style='text-align: center; padding: 20px 0;'>
                <p style='font-size: 18px; color: #666; margin: 0;'>
                    Hoje você completa <strong style='color: #e67e22;'>{$age} anos</strong>! 🎉
                </p>
            </div>
            
            <div style='background: linear-gradient(135deg, #fff5eb 0%, #ffe8d6 100%); border-radius: 12px; padding: 25px; margin: 20px 0; text-align: center;'>
                <p style='font-size: 16px; color: #333; line-height: 1.6; margin: 0;'>
                    O time <strong style='color: #e67e22;'>Lukrato</strong> deseja a você um dia 
                    muito especial, repleto de alegria, conquistas e realizações!
                </p>
                <p style='font-size: 16px; color: #333; line-height: 1.6; margin: 15px 0 0 0;'>
                    🎈 Que este novo ciclo traga prosperidade financeira e que suas metas sejam alcançadas! 🎈
                </p>
            </div>
            
            <div style='text-align: center; padding: 20px 0;'>
                <p style='font-size: 14px; color: #888;'>
                    Continue organizando suas finanças com a gente!
                </p>
                <a href='{$dashboardUrl}' 
                   style='display: inline-block; background: linear-gradient(135deg, #e67e22, #d97706); 
                          color: white; padding: 12px 30px; border-radius: 8px; 
                          text-decoration: none; font-weight: 600; margin-top: 10px;'>
                    Acessar Lukrato
                </a>
            </div>
            
            <div style='text-align: center; font-size: 30px; padding: 10px 0;'>
                🎂 🎈 🎁 🎉 🥳
            </div>
        ";

        return EmailTemplate::wrap(
            "🎂 Feliz Aniversário - Lukrato",
            "#e67e22", // headerBg - laranja
            "🎂 Feliz Aniversário, {$firstName}!",
            "O Lukrato deseja a você um dia muito especial",
            $content,
            "Lukrato - © {$year}. Todos os direitos reservados."
        );
    }

    /**
     * Busca usuários que fazem aniversário em uma data específica
     */
    public function getBirthdayUsers(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        return Usuario::whereNotNull('data_nascimento')
            ->whereRaw('MONTH(data_nascimento) = ?', [$date->month])
            ->whereRaw('DAY(data_nascimento) = ?', [$date->day])
            ->get()
            ->map(function ($user) {
                $birthDate = Carbon::parse($user->data_nascimento);
                return [
                    'id' => $user->id,
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'data_nascimento' => $birthDate->format('d/m/Y'),
                    'idade' => $birthDate->age,
                ];
            })
            ->toArray();
    }

    /**
     * Busca próximos aniversariantes (para dashboard admin)
     */
    public function getUpcomingBirthdays(int $days = 7): array
    {
        $today = Carbon::today();
        $results = [];

        for ($i = 0; $i <= $days; $i++) {
            $checkDate = $today->copy()->addDays($i);
            $users = $this->getBirthdayUsers($checkDate);

            if (!empty($users)) {
                foreach ($users as $user) {
                    $user['dias_restantes'] = $i;
                    $user['data_aniversario'] = $checkDate->format('d/m');
                    $results[] = $user;
                }
            }
        }

        return $results;
    }
}
