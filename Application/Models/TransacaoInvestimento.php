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
        'investimento_id',
        'tipo',
        'quantidade',
        'preco',
        'taxas',
        'data_transacao',
        'observacoes',
    ];

    protected $casts = [
        'quantidade'     => 'float',
        'preco'          => 'float',
        'taxas'          => 'float',
        'data_transacao' => 'date',
    ];

    public function investimento()
    {
        return $this->belongsTo(Investimento::class, 'investimento_id');
    }
}