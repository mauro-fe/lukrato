<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    protected $table = 'planos';
    protected $fillable = ['code', 'nome', 'preco_centavos', 'intervalo', 'ativo', 'metadados'];
    protected $casts = ['metadados' => 'array'];
    public function assinaturas()
    {
        return $this->hasMany(AssinaturaUsuario::class, 'plano_id');
    }
}