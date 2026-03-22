<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property bool $has_conta
 * @property bool $has_lancamento
 * @property \Carbon\Carbon|string|null $first_lancamento_at
 * @property \Carbon\Carbon|string|null $onboarding_completed_at
 * @property \Carbon\Carbon|string|null $created_at
 * @property \Carbon\Carbon|string|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Model|static|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|static updateOrCreate(array $attributes, array $values = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class OnboardingProgress extends Model
{
    protected $table = 'user_onboarding_progress';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'has_conta',
        'has_lancamento',
        'first_lancamento_at',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'has_conta' => 'bool',
        'has_lancamento' => 'bool',
        'first_lancamento_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function isCompleted(): bool
    {
        return $this->onboarding_completed_at !== null;
    }
}
