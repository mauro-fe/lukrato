<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Telefone extends Model
{
    protected $table = 'telefones';
    protected $primaryKey = 'id_telefone';
    public $timestamps = false;
    protected $fillable = ['numero', 'id_usuario', 'tipo', 'id_ddd'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id');
    }
    public function ddd()
    {
        return $this->belongsTo(Ddd::class, 'id_ddd', 'id_ddd');
    }
}