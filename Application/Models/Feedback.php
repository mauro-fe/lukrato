<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    public $timestamps = false;

    public const TIPO_ACAO          = 'acao';
    public const TIPO_ASSISTENTE_IA = 'assistente_ia';
    public const TIPO_NPS           = 'nps';
    public const TIPO_SUGESTAO      = 'sugestao';

    protected $fillable = [
        'user_id',
        'tipo_feedback',
        'contexto',
        'rating',
        'comentario',
        'pagina',
        'created_at',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'rating'     => 'integer',
        'created_at' => 'datetime',
    ];

    // --- Scopes ---

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_feedback', $tipo);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // --- Relations ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // --- Helpers ---

    public static function getTipos(): array
    {
        return [
            self::TIPO_ACAO          => 'Acao',
            self::TIPO_ASSISTENTE_IA => 'Assistente IA',
            self::TIPO_NPS           => 'NPS',
            self::TIPO_SUGESTAO      => 'Sugestao',
        ];
    }
}
