<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaInvestimento extends Model
{
    protected $table = 'categorias_investimento';

    protected $fillable = [
        'nome',
        'icone',
        'cor',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public $timestamps = false;

    /** Um tipo de investimento pode ter vários investimentos */
    public function investimentos()
    {
        return $this->hasMany(Investimento::class, 'categoria_id');
    }
}
