<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingWhatsAppTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'whatsapp_pending';

    protected $fillable = [
        'user_id',
        'wa_message_id',
        'descricao',
        'valor',
        'tipo',
        'data',
        'categoria_id',
        'subcategoria_id',
        'categoria_nome',
        'subcategoria_nome',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'user_id'          => 'int',
        'valor'            => 'float',
        'categoria_id'     => 'int',
        'subcategoria_id'  => 'int',
        'data'             => 'date',
        'expires_at'       => 'datetime',
    ];

    // ─── Scopes ────────────────────────────────────────────

    public function scopeAwaiting($query)
    {
        return $query->where('status', 'awaiting_confirm')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'awaiting_confirm')
            ->where('expires_at', '<=', now());
    }

    // ─── Helpers ───────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function confirm(): bool
    {
        return $this->update(['status' => 'confirmed']);
    }

    public function reject(): bool
    {
        return $this->update(['status' => 'rejected']);
    }

    public function markExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    // ─── Relationships ────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Categoria::class, 'subcategoria_id');
    }
}
