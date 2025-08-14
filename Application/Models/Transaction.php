<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $fillable = ['user_id', 'account_id', 'category_id', 'date', 'amount', 'type', 'notes'];
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
