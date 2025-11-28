<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class TransacaoInvestimento extends Model
{
    protected $table = 'transacoes_investimento';
    public $timestamps = false;
    public const CREATED_AT = null;
    public const UPDATED_AT = null;


    protected $fillable = [
        'investimento_id',   // FK investimento
        'tipo',              // compra | venda
        'quantidade',        // quantidade negociada
        'preco',             // preço unitário
        'taxas',             // taxas e custos
        'data_transacao',    // data da transação
        'observacoes',       // notas opcionais
    ];

    protected $casts = [
        'quantidade'     => 'float',
        'preco'          => 'float',
        'taxas'          => 'float',
        'data_transacao' => 'date',
    ];

    /** RELACIONAMENTO */
    public function investimento()
    {
        return $this->belongsTo(Investimento::class, 'investimento_id');
    }
}
