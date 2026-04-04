<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacaoPerfil extends Model
{
    protected $table = 'importacao_perfis';

    protected $fillable = [
        'user_id',
        'conta_id',
        'source_type',
        'label',
        'agencia',
        'numero_conta',
        'options_json',
    ];

    protected $casts = [
        'user_id' => 'int',
        'conta_id' => 'int',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }
}

