<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Ddd extends Model
{
    protected $table = 'ddd';
    protected $primaryKey = 'id_ddd';
    public $timestamps = false;
    protected $fillable = ['codigo'];
}