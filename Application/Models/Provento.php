<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Provento extends Model
{
    protected $table = 'proventos';

    protected $fillable = [
        'investimento_id',
        'valor',
        'tipo',
        'data_pagamento',
        'observacoes',
    ];

    protected $casts = [
        'valor'          => 'float',
        'data_pagamento' => 'date',
    ];

    public function investimento()
    {
        return $this->belongsTo(Investimento::class, 'investimento_id');
    }
}