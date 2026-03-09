<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'wa_message_id',
        'from_phone',
        'user_id',
        'direction',
        'type',
        'body',
        'metadata',
        'processing_status',
        'intent',
        'error_message',
    ];

    protected $casts = [
        'user_id'  => 'int',
        'metadata' => 'array',
    ];

    // ─── Scopes ────────────────────────────────────────────

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeByPhone(mixed $query, string $phone)
    {
        return $query->where('from_phone', $phone);
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * Verifica se esta mensagem já foi processada (idempotência).
     */
    public static function alreadyProcessed(string $waMessageId): bool
    {
        return self::where('wa_message_id', $waMessageId)->exists();
    }

    /**
     * Marca como processada com sucesso.
     */
    public function markProcessed(?string $intent = null): bool
    {
        return $this->update([
            'processing_status' => 'processed',
            'intent'            => $intent,
        ]);
    }

    /**
     * Marca como falha.
     */
    public function markFailed(string $error): bool
    {
        return $this->update([
            'processing_status' => 'failed',
            'error_message'     => mb_substr($error, 0, 500),
        ]);
    }

    /**
     * Marca como ignorada (mensagem sem relevância, status update, etc).
     */
    public function markIgnored(): bool
    {
        return $this->update(['processing_status' => 'ignored']);
    }

    // ─── Relationships ────────────────────────────────────

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
