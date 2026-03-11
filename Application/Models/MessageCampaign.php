<?php

declare(strict_types=1);

namespace Application\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: MessageCampaign
 * 
 * Representa uma campanha de mensagens enviada pelo sysadmin.
 * Registra histórico, filtros aplicados e estatísticas.
 *
 * @property int $id
 * @property string $title
 * @property string $message
 * @property string|null $link
 * @property string|null $link_text
 * @property string $type
 * @property array|null $filters
 * @property bool $send_notification
 * @property bool $send_email
 * @property int $total_recipients
 * @property int $emails_sent
 * @property int $emails_failed
 * @property int $notifications_read
 * @property int $created_by
 * @property string $status
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $scheduled_at
 * @property int|null $cupom_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Usuario $creator
 * @property-read Cupom|null $cupom
 * @property-read \Illuminate\Database\Eloquent\Collection|Notification[] $notifications
 * @property-read bool $is_scheduled
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|null find(int|string $id)
 * @method static \Illuminate\Database\Eloquent\Model|static create(array $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class MessageCampaign extends Model
{
    protected $table = 'message_campaigns';

    // Status da campanha
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';

    // Tipos (mesmos do Notification)
    public const TYPE_INFO = 'info';
    public const TYPE_PROMO = 'promo';
    public const TYPE_UPDATE = 'update';
    public const TYPE_ALERT = 'alert';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_REMINDER = 'reminder';

    // Filtros de plano
    public const PLAN_ALL = 'all';
    public const PLAN_FREE = 'free';
    public const PLAN_PRO = 'pro';

    // Filtros de status
    public const USER_STATUS_ALL = 'all';
    public const USER_STATUS_ACTIVE = 'active';
    public const USER_STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'title',
        'message',
        'link',
        'link_text',
        'type',
        'filters',
        'send_notification',
        'send_email',
        'total_recipients',
        'emails_sent',
        'emails_failed',
        'notifications_read',
        'created_by',
        'cupom_id',
        'status',
        'sent_at',
        'scheduled_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'send_notification' => 'boolean',
        'send_email' => 'boolean',
        'total_recipients' => 'integer',
        'emails_sent' => 'integer',
        'emails_failed' => 'integer',
        'notifications_read' => 'integer',
        'created_by' => 'integer',
        'cupom_id' => 'integer',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Admin que criou a campanha
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'created_by');
    }

    /**
     * Cupom vinculado à campanha (opcional)
     */
    public function cupom(): BelongsTo
    {
        return $this->belongsTo(Cupom::class, 'cupom_id');
    }

    /**
     * Notificações geradas por esta campanha
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'campaign_id');
    }

    /**
     * Retorna descrição legível dos filtros aplicados
     */
    public function getFiltersDescriptionAttribute(): string
    {
        if (empty($this->filters)) {
            return 'Todos os usuários';
        }

        $parts = [];

        // Plano
        $plan = $this->filters['plan'] ?? 'all';
        if ($plan === 'free') {
            $parts[] = 'Plano Gratuito';
        } elseif ($plan === 'pro') {
            $parts[] = 'Plano PRO';
        }

        // Status de atividade
        $status = $this->filters['status'] ?? 'all';
        if ($status === 'active') {
            $parts[] = 'Ativos';
        } elseif ($status === 'inactive') {
            $parts[] = 'Inativos';
        }

        // Dias inativos
        $daysInactive = $this->filters['days_inactive'] ?? null;
        if ($daysInactive) {
            $parts[] = "Sem atividade há {$daysInactive}+ dias";
        }

        // Email verificado
        $emailVerified = $this->filters['email_verified'] ?? null;
        if ($emailVerified === true) {
            $parts[] = 'Email verificado';
        } elseif ($emailVerified === false) {
            $parts[] = 'Email não verificado';
        }

        return empty($parts) ? 'Todos os usuários' : implode(', ', $parts);
    }

    /**
     * Retorna descrição dos canais utilizados
     */
    public function getChannelsDescriptionAttribute(): string
    {
        $channels = [];

        if ($this->send_notification) {
            $channels[] = 'Notificação';
        }

        if ($this->send_email) {
            $channels[] = 'E-mail';
        }

        return empty($channels) ? 'Nenhum' : implode(' + ', $channels);
    }

    /**
     * Retorna taxa de leitura em percentual
     */
    public function getReadRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0.0;
        }

        return round(($this->notifications_read / $this->total_recipients) * 100, 1);
    }

    /**
     * Retorna taxa de sucesso de email em percentual
     */
    public function getEmailSuccessRateAttribute(): float
    {
        $totalEmails = $this->emails_sent + $this->emails_failed;
        if ($totalEmails === 0) {
            return 0.0;
        }

        return round(($this->emails_sent / $totalEmails) * 100, 1);
    }

    /**
     * Retorna ícone baseado no tipo
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PROMO => 'crown',
            self::TYPE_UPDATE => 'rocket',
            self::TYPE_ALERT => 'triangle-alert',
            self::TYPE_SUCCESS => 'circle-check',
            self::TYPE_REMINDER => 'bell',
            default => 'info',
        };
    }

    /**
     * Retorna cor baseada no tipo
     */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PROMO => '#f59e0b',
            self::TYPE_UPDATE => '#8b5cf6',
            self::TYPE_ALERT => '#ef4444',
            self::TYPE_SUCCESS => '#10b981',
            self::TYPE_REMINDER => '#3b82f6',
            default => '#6b7280',
        };
    }

    /**
     * Verifica se a campanha é agendada (draft com scheduled_at)
     */
    public function getIsScheduledAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->scheduled_at !== null;
    }

    /**
     * Retorna badge de status
     */
    public function getStatusBadgeAttribute(): array
    {
        // Status especial para campanhas agendadas
        if ($this->is_scheduled) {
            $label = 'Agendada ' . $this->scheduled_at->format('d/m H:i');
            return ['label' => $label, 'color' => '#6366f1', 'icon' => 'clock'];
        }

        return match ($this->status) {
            self::STATUS_DRAFT => ['label' => 'Rascunho', 'color' => '#6b7280'],
            self::STATUS_SENDING => ['label' => 'Enviando...', 'color' => '#f59e0b'],
            self::STATUS_SENT => ['label' => 'Enviada', 'color' => '#10b981'],
            self::STATUS_FAILED => ['label' => 'Falhou', 'color' => '#ef4444'],
            self::STATUS_PARTIAL => ['label' => 'Parcial', 'color' => '#f97316'],
            default => ['label' => 'Desconhecido', 'color' => '#6b7280'],
        };
    }

    /**
     * Scope: Campanhas enviadas
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_PARTIAL]);
    }

    /**
     * Scope: Campanhas agendadas prontas para envio
     */
    public function scopeReadyToSend(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', Carbon::now());
    }

    /**
     * Scope: Recentes primeiro
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Retorna tipos disponíveis
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_INFO => 'Informação',
            self::TYPE_PROMO => 'Promoção/Upgrade',
            self::TYPE_UPDATE => 'Novidade/Lançamento',
            self::TYPE_ALERT => 'Alerta Importante',
            self::TYPE_SUCCESS => 'Sucesso/Confirmação',
            self::TYPE_REMINDER => 'Lembrete',
        ];
    }

    /**
     * Retorna opções de plano para filtro
     */
    public static function getPlanOptions(): array
    {
        return [
            self::PLAN_ALL => 'Todos os planos',
            self::PLAN_FREE => 'Apenas Gratuito',
            self::PLAN_PRO => 'Apenas PRO',
        ];
    }

    /**
     * Retorna opções de status para filtro
     */
    public static function getStatusOptions(): array
    {
        return [
            self::USER_STATUS_ALL => 'Todos os status',
            self::USER_STATUS_ACTIVE => 'Apenas ativos',
            self::USER_STATUS_INACTIVE => 'Apenas inativos',
        ];
    }

    /**
     * Retorna opções de dias inativos
     */
    public static function getInactiveDaysOptions(): array
    {
        return [
            null => 'Sem filtro de inatividade',
            7 => 'Inativos há 7+ dias',
            15 => 'Inativos há 15+ dias',
            30 => 'Inativos há 30+ dias',
            60 => 'Inativos há 60+ dias',
            90 => 'Inativos há 90+ dias',
        ];
    }
}
