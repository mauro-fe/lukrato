<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    protected $table = 'agendamentos';

    protected $fillable = [
        'user_id',                 // <— trocado
        'conta_id',
        'categoria_id',
        'titulo',
        'descricao',
        'tipo',                    // 'despesa' | 'receita'
        'valor_centavos',
        'moeda',
        'data_pagamento',
        'proxima_execucao',
        'lembrar_antes_segundos',
        'canal_email',
        'canal_inapp',
        'recorrente',
        'recorrencia_freq',
        'recorrencia_intervalo',
        'recorrencia_fim',
        'notificado_em',
        'concluido_em',
        'status',
    ];

    protected $casts = [
        'data_pagamento'   => 'datetime',
        'proxima_execucao' => 'datetime',
        'recorrencia_fim'  => 'date',
        'notificado_em'    => 'datetime',
        'concluido_em'     => 'datetime',
        'canal_email'      => 'bool',
        'canal_inapp'      => 'bool',
        'recorrente'       => 'bool',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
