<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'accounts';
    protected $fillable = ['user_id', 'name', 'type', 'currency', 'balance_cached'];
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }
}
