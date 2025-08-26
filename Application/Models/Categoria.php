<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    // precisa existir no BD: colunas 'nome' e 'tipo'
    protected $fillable = ['nome', 'tipo'];

    // ---- SCOPES USADOS PELO /api/options ----
    public function scopeReceitas($q)
    {
        return $q->whereIn('tipo', ['receita', 'ambas']);
    }

    public function scopeDespesas($q)
    {
        return $q->whereIn('tipo', ['despesa', 'ambas']);
    }
}
