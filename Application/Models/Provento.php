<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Provento
 *
 * @property int $id
 * @property int $investimento_id
 * @property float $valor
 * @property string|null $tipo
 * @property \Carbon\Carbon|string|null $data_pagamento
 * @property string|null $observacoes
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Provento where(string $column, $value = null)
 * @mixin \Eloquent
 */
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
