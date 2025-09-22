<?php
// Application/Models/TipoDocumento.php
namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';
    protected $primaryKey = 'id_tipo';
    public $timestamps = false;
    protected $fillable = ['ds_tipo'];
}
