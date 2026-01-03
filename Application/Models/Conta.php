<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property string|null $cor
 * @property string|null $instituicao
 * @property int|null $instituicao_financeira_id
 * @property string|null $tipo_conta
 * @property float|null $saldo_inicial
 * @property string|null $moeda
 * @property int|null $tipo_id
 * @property bool $ativo
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder ativas()
 * @method static \Illuminate\Database\Eloquent\Builder arquivadas()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
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
        'saldo_inicial',
        'moeda',
        'tipo_id',
        'ativo',
    ];

    protected $casts = [
        'user_id'       => 'int',
        'instituicao_financeira_id' => 'int',
        'saldo_inicial' => 'float',
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
