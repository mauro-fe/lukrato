<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Investimento extends Model
{
    protected $table = 'investimentos';

    public $timestamps = false;

    protected $fillable = [
        'user_id',         // ID do usuário dono do investimento
        'categoria_id',    // FK categoria
        'conta_id',        // FK conta opcional
        'nome',            // nome do ativo (ex: Petrobras PN)
        'ticker',          // código de negociação
        'quantidade',      // quantidade de cotas/ações
        'preco_medio',     // preço médio de compra
        'preco_atual',     // último preço de mercado
        'data_compra',     // data da compra (opcional)
        'observacoes',     // notas ou comentários
    ];

    protected $casts = [
        'quantidade'   => 'float',
        'preco_medio'  => 'float',
        'preco_atual'  => 'float',
        'data_compra'  => 'date',
    ];

    /** RELACIONAMENTOS */
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

    /** GETTERS dinâmicos */
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
    // Filtro por usuário reutilizável
    public function scopeForUser($query, int $userId)
    {
        // Se sua coluna for 'user_id'
        return $query->where('user_id', $userId);

        // Se na sua base o nome for 'usuario_id', troque para:
        // return $query->where('usuario_id', $userId);
    }
}
