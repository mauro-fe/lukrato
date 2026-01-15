<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model para itens de fatura de cartão de crédito
 * 
 * Representa compras/parcelas que ainda não viraram lançamentos.
 * Só viram lançamentos quando a fatura é paga.
 */
class FaturaCartaoItem extends Model
{
    protected $table = 'faturas_cartao_itens';

    protected $fillable = [
        'user_id',
        'cartao_credito_id',
        'fatura_id',
        'lancamento_id',
        'descricao',
        'valor',
        'data_compra',
        'data_vencimento',
        'mes_referencia',
        'ano_referencia',
        'categoria_id',
        'eh_parcelado',
        'parcela_atual',
        'total_parcelas',
        'item_pai_id',
        'pago',
        'data_pagamento',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_compra' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'pago' => 'boolean',
        'eh_parcelado' => 'boolean',
        'parcela_atual' => 'integer',
        'total_parcelas' => 'integer',
        'mes_referencia' => 'integer',
        'ano_referencia' => 'integer',
    ];

    /**
     * Accessor para manter compatibilidade com código que usa valor_parcela
     * O campo real na tabela é 'valor'
     */
    public function getValorParcelaAttribute()
    {
        return $this->valor;
    }

    /**
     * Mutator para manter compatibilidade com código que usa valor_parcela
     */
    public function setValorParcelaAttribute($value)
    {
        $this->attributes['valor'] = $value;
    }

    // Relacionamentos
    public function fatura()
    {
        return $this->belongsTo(Fatura::class, 'fatura_id');
    }

    public function lancamento()
    {
        return $this->belongsTo(Lancamento::class, 'lancamento_id');
    }

    // Scopes
    public function scopeDoMesAno($query, int $mes, int $ano)
    {
        return $query->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano);
    }

    public function scopePendentes($query)
    {
        return $query->where('pago', false);
    }

    public function scopePagos($query)
    {
        return $query->where('pago', true);
    }

    public function scopeDoMes($query, int $mes, int $ano)
    {
        return $query->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes);
    }
}
