<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Lancamento extends Model
{
    protected $table = 'lancamentos';
    protected $fillable = ['tipo', 'valor', 'categoria_id', 'descricao', 'data', 'user_id'];
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}