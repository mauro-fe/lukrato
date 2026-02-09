<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: Notification
 * 
 * Representa uma notificação individual para um usuário.
 * Pode ser originada de uma campanha ou criada diretamente.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $message
 * @property string|null $link
 * @property string $type
 * @property bool $is_read
 * @property \Carbon\Carbon|null $read_at
 * @property int|null $campaign_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Usuario $user
 * @property-read MessageCampaign|null $campaign
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|null find(int|string $id)
 * @method static \Illuminate\Database\Eloquent\Model|static create(array $attributes = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Notification extends Model
{
    protected $table = 'notifications';

    // Tipos de notificação disponíveis
    public const TYPE_INFO = 'info';
    public const TYPE_PROMO = 'promo';
    public const TYPE_UPDATE = 'update';
    public const TYPE_ALERT = 'alert';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_BIRTHDAY = 'birthday';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'link',
        'type',
        'is_read',
        'read_at',
        'campaign_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'campaign_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Usuário destinatário da notificação
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Campanha de origem (se aplicável)
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MessageCampaign::class, 'campaign_id');
    }

    /**
     * Marcar como lida
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        $this->is_read = true;
        $this->read_at = now();
        $saved = $this->save();

        // Atualizar contador da campanha se aplicável
        if ($saved && $this->campaign_id) {
            MessageCampaign::where('id', $this->campaign_id)
                ->increment('notifications_read');
        }

        return $saved;
    }

    /**
     * Marcar como não lida
     */
    public function markAsUnread(): bool
    {
        if (!$this->is_read) {
            return true;
        }

        // Decrementar contador da campanha se aplicável
        if ($this->campaign_id) {
            MessageCampaign::where('id', $this->campaign_id)
                ->where('notifications_read', '>', 0)
                ->decrement('notifications_read');
        }

        $this->is_read = false;
        $this->read_at = null;
        return $this->save();
    }

    /**
     * Retorna ícone baseado no tipo
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PROMO => 'fa-crown',
            self::TYPE_UPDATE => 'fa-rocket',
            self::TYPE_ALERT => 'fa-exclamation-triangle',
            self::TYPE_SUCCESS => 'fa-check-circle',
            self::TYPE_REMINDER => 'fa-bell',
            default => 'fa-info-circle',
        };
    }

    /**
     * Retorna cor baseada no tipo
     */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PROMO => '#f59e0b',    // Amarelo/dourado
            self::TYPE_UPDATE => '#8b5cf6',   // Roxo
            self::TYPE_ALERT => '#ef4444',    // Vermelho
            self::TYPE_SUCCESS => '#10b981',  // Verde
            self::TYPE_REMINDER => '#3b82f6', // Azul
            default => '#6b7280',             // Cinza
        };
    }

    /**
     * Scope: Apenas notificações não lidas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Apenas notificações lidas
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: Por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
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
            self::TYPE_PROMO => 'Promoção',
            self::TYPE_UPDATE => 'Novidade',
            self::TYPE_ALERT => 'Alerta',
            self::TYPE_SUCCESS => 'Sucesso',
            self::TYPE_REMINDER => 'Lembrete',
        ];
    }
}
