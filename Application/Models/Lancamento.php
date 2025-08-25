<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Lancamento extends Model
{
    protected $table = 'lancamentos';     // confirme o nome certo da tabela

    protected $fillable = [
        'tipo',
        'data',
        'categoria_id',
        'descricao',
        'observacao',
        'valor'
    ];

    protected $casts = [
        'data'  => 'date:Y-m-d',
        'valor' => 'float',
    ];

    // RELACIONAMENTO QUE O CONTROLLER USA:
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
