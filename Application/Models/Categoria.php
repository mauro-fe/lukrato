<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $fillable = ['nome', 'tipo', 'user_id'];
    protected $casts = ['user_id' => 'int'];
    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class, 'categoria_id');
    }

    public function scopeReceitas($q)
    {
        return $q->where('tipo', 'receita');
    }
    public function scopeDespesas($q)
    {
        return $q->where('tipo', 'despesa');
    }
    public function scopeTransferencias($q)
    {
        return $q->where('tipo', 'transferencia');
    }
    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }
}