<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Lancamento extends Model
{
    protected $table = 'lancamentos';
    protected $fillable = [
        'admin_id',
        'tipo',
        'categoria_id',
        'descricao',
        'valor',
        'data',
        'observacao'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
