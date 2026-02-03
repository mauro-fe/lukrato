<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para gerenciar indicações entre usuários
 * 
 * @property int $id
 * @property int $referrer_id
 * @property int $referred_id
 * @property string $status
 * @property int $referrer_reward_days
 * @property int $referred_reward_days
 * @property bool $referrer_rewarded
 * @property bool $referred_rewarded
 * @property \Carbon\Carbon|null $referrer_rewarded_at
 * @property \Carbon\Carbon|null $referred_rewarded_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Usuario $referrer
 * @property-read Usuario $referred
 */
class Indicacao extends Model
{
    protected $table = 'indicacoes';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    // Recompensas padrão em dias
    public const DEFAULT_REFERRER_REWARD_DAYS = 15;
    public const DEFAULT_REFERRED_REWARD_DAYS = 7;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'status',
        'referrer_reward_days',
        'referred_reward_days',
        'referrer_rewarded',
        'referred_rewarded',
        'referrer_rewarded_at',
        'referred_rewarded_at',
        'completed_at',
    ];

    protected $casts = [
        'referrer_reward_days' => 'integer',
        'referred_reward_days' => 'integer',
        'referrer_rewarded' => 'boolean',
        'referred_rewarded' => 'boolean',
        'referrer_rewarded_at' => 'datetime',
        'referred_rewarded_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Usuário que fez a indicação
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'referrer_id');
    }

    /**
     * Usuário que foi indicado
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'referred_id');
    }

    /**
     * Verifica se a indicação está pendente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica se a indicação foi completada
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Marca como completada
     */
    public function markAsCompleted(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        return $this->save();
    }

    /**
     * Marca que o referrer recebeu a recompensa
     */
    public function markReferrerRewarded(): bool
    {
        $this->referrer_rewarded = true;
        $this->referrer_rewarded_at = now();
        return $this->save();
    }

    /**
     * Marca que o indicado recebeu a recompensa
     */
    public function markReferredRewarded(): bool
    {
        $this->referred_rewarded = true;
        $this->referred_rewarded_at = now();
        return $this->save();
    }

    /**
     * Scope: Apenas indicações completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Indicações de um usuário específico (como referrer)
     */
    public function scopeByReferrer($query, int $userId)
    {
        return $query->where('referrer_id', $userId);
    }

    /**
     * Conta quantas indicações um usuário fez
     */
    public static function countByReferrer(int $userId): int
    {
        return self::where('referrer_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->count();
    }

    /**
     * Verifica se um usuário já foi indicado
     */
    public static function wasReferred(int $userId): bool
    {
        return self::where('referred_id', $userId)->exists();
    }
}
