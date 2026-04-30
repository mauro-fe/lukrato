<?php

declare(strict_types=1);

namespace Application\Models;

use Application\Casts\MoneyDecimalCast;
use Illuminate\Database\Eloquent\Model;

class ImportacaoItem extends Model
{
    protected $table = 'importacao_itens';

    protected $fillable = [
        'lote_id',
        'user_id',
        'conta_id',
        'lancamento_id',
        'row_hash',
        'status',
        'external_id',
        'data',
        'amount',
        'tipo',
        'description',
        'memo',
        'raw_json',
        'message',
    ];

    protected $casts = [
        'lote_id' => 'int',
        'user_id' => 'int',
        'conta_id' => 'int',
        'lancamento_id' => 'int',
        'amount' => MoneyDecimalCast::class,
        'data' => 'date:Y-m-d',
    ];

    public function lote()
    {
        return $this->belongsTo(ImportacaoLote::class, 'lote_id');
    }

    public function lancamento()
    {
        return $this->belongsTo(Lancamento::class, 'lancamento_id');
    }
}
