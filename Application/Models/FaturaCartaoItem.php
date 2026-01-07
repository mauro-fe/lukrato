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
        'descricao',
        'valor',
        'data_compra',
        'data_vencimento',
        'categoria_id',
        'parcela_atual',
        'total_parcelas',
        'mes_referencia',
        'ano_referencia',
        'pago',
        'data_pagamento',
        'lancamento_id',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_compra' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'eh_parcelado' => 'boolean',
        'pago' => 'boolean',
    ];

    // Relacionamentos
    public function cartaoCredito()
    {
        return $this->belongsTo(CartaoCredito::class, 'cartao_credito_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function fatura()
    {
        return $this->belongsTo(Fatura::class, 'fatura_id');
    }

    public function lancamento()
    {
        return $this->belongsTo(Lancamento::class, 'lancamento_id');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDoCartao($query, int $cartaoId)
    {
        return $query->where('cartao_credito_id', $cartaoId);
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
