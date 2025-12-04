<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos';
    protected $fillable = ['numero', 'id_tipo', 'id_usuario'];
    public $timestamps = false;

    public function tipo()
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo', 'id_tipo');
    }
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id');
    }
}