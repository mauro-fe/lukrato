<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<Conta, $this>
     */
    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }
}
