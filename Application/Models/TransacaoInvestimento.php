<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransacaoInvestimento
 *
 * @property int $id
 * @property int $investimento_id
 * @property string $tipo
 * @property float $quantidade
 * @property float $preco
 * @property float|null $taxas
 * @property \Carbon\Carbon|string|null $data_transacao
 * @property string|null $observacoes
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TransacaoInvestimento where(string $column, $value = null)
 * @mixin \Eloquent
 */
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
