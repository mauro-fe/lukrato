<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    public $timestamps = false;

    protected $table = 'ai_logs';

    protected $fillable = [
        'user_id',
        'type',
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
    ];

    protected $casts = [
        'user_id'           => 'int',
        'tokens_prompt'     => 'int',
        'tokens_completion' => 'int',
        'tokens_total'      => 'int',
        'response_time_ms'  => 'int',
        'success'           => 'bool',
        'created_at'        => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
