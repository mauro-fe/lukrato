<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telefone extends Model
{
    protected $table = 'telefones';
    protected $primaryKey = 'id_telefone';
    public $timestamps = false;
    protected $fillable = ['numero', 'id_usuario', 'tipo', 'id_ddd'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id');
    }
    public function ddd(): BelongsTo
    {
        return $this->belongsTo(Ddd::class, 'id_ddd', 'id_ddd');
    }
}
