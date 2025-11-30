<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Endereco extends Model
{
    protected $table = 'enderecos';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'cep',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'tipo',
    ];


    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}