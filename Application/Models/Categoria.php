<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $fillable = ['nome', 'user_id'];
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class);
    }
}