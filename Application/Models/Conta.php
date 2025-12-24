<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conta extends Model
{
    use SoftDeletes;

    protected $table = 'contas';

    protected $fillable = [
        'user_id',
        'nome',
        'cor',
        'instituicao',
        'instituicao_financeira_id',
        'tipo_conta',
        'moeda',
        'tipo_id',
        'ativo',
    ];

    protected $casts = [
        'user_id'       => 'int',
        'instituicao_financeira_id' => 'int',
        'tipo_id',
        'ativo'         => 'bool',
    ];

    protected $with = ['instituicaoFinanceira'];
    protected $dates = ['deleted_at'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function instituicaoFinanceira()
    {
        return $this->belongsTo(InstituicaoFinanceira::class, 'instituicao_financeira_id');
    }

    public function cartoesCredito()
    {
        return $this->hasMany(CartaoCredito::class, 'conta_id');
    }

    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class, 'conta_id');
    }

    public function transferenciasRecebidas()
    {
        return $this->hasMany(Lancamento::class, 'conta_id_destino')
            ->where('eh_transferencia', 1);
    }

    public function transferenciasEnviadas()
    {
        return $this->hasMany(Lancamento::class, 'conta_id')
            ->where('eh_transferencia', 1);
    }

    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeAtivas($q)
    {
        return $q->where('ativo', 1);
    }
    public function scopeArquivadas($q)
    {
        return $q->where('ativo', 0);
    }
}