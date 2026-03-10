<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingAiAction extends Model
{
    use SoftDeletes;

    protected $table = 'pending_ai_actions';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'action_type',
        'payload',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'user_id'         => 'int',
        'conversation_id' => 'int',
        'payload'         => 'array',
        'expires_at'      => 'datetime',
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

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
