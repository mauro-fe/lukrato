<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class LogWebhookCobranca extends Model
{
    protected $table = 'log_webhooks_cobranca'; // nome da tabela

    protected $fillable = [
        'provedor',
        'tipo_evento',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array', // converte JSON em array automaticamente
    ];
}
