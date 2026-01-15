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
        'fatura_id',
        'lancamento_id',
        'numero_parcela',
        'valor_parcela',
        'mes_referencia',
        'ano_referencia',
        'pago',
        'data_pagamento',
    ];

    protected $casts = [
        'valor_parcela' => 'decimal:2',
        'data_pagamento' => 'date',
        'pago' => 'boolean',
        'numero_parcela' => 'integer',
        'mes_referencia' => 'integer',
        'ano_referencia' => 'integer',
    ];

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
