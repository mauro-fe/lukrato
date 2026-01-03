<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Investimento
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $categoria_id
 * @property int|null $conta_id
 * @property string $nome
 * @property string|null $ticker
 * @property float|null $quantidade
 * @property float|null $preco_medio
 * @property float|null $preco_atual
 * @property \Carbon\Carbon|null $data_compra
 * @property string|null $observacoes
 *
 * @property-read float $valor_investido
 * @property-read float $valor_atual
 * @property-read float $lucro
 * @property-read float $rentabilidade
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Investimento where(string $column, $value = null)
 * @mixin \Eloquent
 */

class Investimento extends Model
{
    protected $table = 'investimentos';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'categoria_id',
        'conta_id',
        'nome',
        'ticker',
        'quantidade',
        'preco_medio',
        'preco_atual',
        'data_compra',
        'observacoes',
    ];

    protected $casts = [
        'quantidade'   => 'float',
        'preco_medio'  => 'float',
        'preco_atual'  => 'float',
        'data_compra'  => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaInvestimento::class, 'categoria_id');
    }

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    public function transacoes()
    {
        return $this->hasMany(TransacaoInvestimento::class, 'investimento_id');
    }

    public function proventos()
    {
        return $this->hasMany(Provento::class, 'investimento_id');
    }

    public function getValorInvestidoAttribute(): float
    {
        return round(($this->quantidade ?? 0) * ($this->preco_medio ?? 0), 2);
    }

    public function getValorAtualAttribute(): float
    {
        return round(($this->quantidade ?? 0) * ($this->preco_atual ?? 0), 2);
    }

    public function getLucroAttribute(): float
    {
        return round($this->valor_atual - $this->valor_investido, 2);
    }

    public function getRentabilidadeAttribute(): float
    {
        return $this->valor_investido > 0
            ? round(($this->lucro / $this->valor_investido) * 100, 2)
            : 0.0;
    }
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
