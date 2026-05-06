<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    protected $table = 'planos';
    protected $fillable = ['code', 'nome', 'preco_centavos', 'intervalo', 'ativo', 'metadados'];
    protected $casts = ['metadados' => 'array'];
    /**
     * @return HasMany<AssinaturaUsuario, $this>
     */
    public function assinaturas(): HasMany
    {
        return $this->hasMany(AssinaturaUsuario::class, 'plano_id');
    }
}
