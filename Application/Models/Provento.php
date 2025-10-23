<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Provento extends Model
{
    protected $table = 'proventos';

    protected $fillable = [
        'investimento_id',   // FK investimento
        'valor',             // valor recebido
        'tipo',              // dividendo | jcp | rendimento
        'data_pagamento',    // data de recebimento
        'observacoes',       // notas
    ];

    protected $casts = [
        'valor'          => 'float',
        'data_pagamento' => 'date',
    ];

    /** RELACIONAMENTO */
    public function investimento()
    {
        return $this->belongsTo(Investimento::class, 'investimento_id');
    }
}
