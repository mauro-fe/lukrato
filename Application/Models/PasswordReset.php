<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $table = 'password_resets';

    public $timestamps = false; // vamos controlar na mão

    protected $fillable = [
        'email',
        'token',
        'created_at',
        'expires_at',
        'used_at',
    ];
}