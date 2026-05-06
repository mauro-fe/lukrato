<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLog extends Model
{
    public $timestamps = false;

    protected $table = 'ai_logs';

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'prompt',
        'response',
        'provider',
        'model',
        'tokens_prompt',
        'tokens_completion',
        'tokens_total',
        'response_time_ms',
        'success',
        'error_message',
        'source',
        'confidence',
        'prompt_version',
    ];

    protected $casts = [
        'user_id'           => 'int',
        'channel'           => 'string',
        'tokens_prompt'     => 'int',
        'tokens_completion' => 'int',
        'tokens_total'      => 'int',
        'response_time_ms'  => 'int',
        'success'           => 'bool',
        'confidence'        => 'float',
        'created_at'        => 'datetime',
    ];

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
