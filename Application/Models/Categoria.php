<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nome', 'tipo', 'user_id'];

    protected $casts = [
        'user_id' => 'int',
    ];

    /** dono da categoria */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // ---- SCOPES ----
    public function scopeReceitas($q)
    {
        return $q->whereIn('tipo', ['receita', 'ambas']);
    }

    public function scopeDespesas($q)
    {
        return $q->whereIn('tipo', ['despesa', 'ambas']);
    }

    /** filtra por usuário */
    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }
}
