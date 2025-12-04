<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class AssinaturaUsuario extends Model
{
    public const ST_PENDING  = 'pending';
    public const ST_ACTIVE   = 'active';
    public const ST_PAST_DUE = 'past_due';
    public const ST_CANCELED = 'canceled';
    public const ST_PAUSED   = 'paused';

    protected $table = 'assinaturas_usuarios';
    protected $fillable = [
        'user_id',
        'plano_id',
        'gateway',
        'external_customer_id',
        'external_subscription_id',
        'status',
        'renova_em',
        'cancelada_em'
    ];
    protected $casts = ['renova_em' => 'datetime', 'cancelada_em' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function scopeAtivas($q)
    {
        return $q->where('status', self::ST_ACTIVE);
    }
}