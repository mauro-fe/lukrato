<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class LogWebhookCobranca extends Model
{
    protected $table = 'log_webhooks_cobranca';
    protected $fillable = ['provedor', 'tipo_evento', 'payload'];
    protected $casts = ['payload' => 'array'];

    public $timestamps = false;
}
