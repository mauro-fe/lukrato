<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $table = 'telegram_messages';

    protected $fillable = [
        'tg_update_id',
        'tg_message_id',
        'chat_id',
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

    public function scopeByChatId(mixed $query, string $chatId)
    {
        return $query->where('chat_id', $chatId);
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * Verifica se este update já foi processado (idempotência).
     */
    public static function alreadyProcessed(string $updateId): bool
    {
        return self::where('tg_update_id', $updateId)->exists();
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
     * Marca como ignorada.
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
