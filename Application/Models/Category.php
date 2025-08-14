<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = ['user_id', 'name', 'type', 'parent_id'];
}
