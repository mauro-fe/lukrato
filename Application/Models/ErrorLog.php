<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model ErrorLog — registro persistente de erros/warnings no banco de dados.
 *
 * Tabela: error_logs
 */
class ErrorLog extends Model
{
    protected $table = 'error_logs';

    protected $fillable = [
        'level',
        'category',
        'message',
        'context',
        'exception_class',
        'exception_message',
        'file',
        'line',
        'stack_trace',
        'user_id',
        'url',
        'method',
        'ip',
        'user_agent',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'context'     => 'array',
        'resolved_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ─── Scopes ────────────────────────────────────────────

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─── Relations ──────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function resolver()
    {
        return $this->belongsTo(Usuario::class, 'resolved_by');
    }

    // ─── Helpers ────────────────────────────────────────────

    public function markResolved(?int $resolvedBy = null): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
