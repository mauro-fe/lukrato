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
        'tipo', // 'despesa' ou 'estorno'
        'data_compra',
        'data_vencimento',
        'mes_referencia',
        'ano_referencia',
        'categoria_id',
        'subcategoria_id',
        'eh_parcelado',
        'parcela_atual',
        'total_parcelas',
        'item_pai_id',
        'pago',
        'data_pagamento',
        'recorrente',
        'recorrencia_freq',
        'recorrencia_fim',
        'recorrencia_pai_id',
        'cancelado_em',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'cartao_credito_id' => 'integer',
        'fatura_id' => 'integer',
        'lancamento_id' => 'integer',
        'categoria_id' => 'integer',
        'subcategoria_id' => 'integer',
        'valor' => 'decimal:2',
        'data_compra' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'pago' => 'boolean',
        'eh_parcelado' => 'boolean',
        'item_pai_id' => 'integer',
        'recorrente' => 'boolean',
        'recorrencia_fim' => 'date',
        'recorrencia_pai_id' => 'integer',
        'cancelado_em' => 'datetime',
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

    /**
     * Accessor para manter compatibilidade com código que usa numero_parcela
     * O campo real na tabela é 'parcela_atual'
     */
    public function getNumeroParcelaAttribute()
    {
        return $this->parcela_atual;
    }

    /**
     * Mutator para manter compatibilidade com código que usa numero_parcela
     */
    public function setNumeroParcelaAttribute($value)
    {
        $this->attributes['parcela_atual'] = $value;
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

    public function cartaoCredito()
    {
        return $this->belongsTo(CartaoCredito::class, 'cartao_credito_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Categoria::class, 'subcategoria_id');
    }

    /**
     * Scope para itens do usuário
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
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

    // Relacionamentos de recorrência

    /**
     * Item pai da recorrência (o primeiro item que originou a assinatura)
     */
    public function recorrenciaPai()
    {
        return $this->belongsTo(FaturaCartaoItem::class, 'recorrencia_pai_id');
    }

    /**
     * Itens filhos gerados por esta recorrência
     */
    public function recorrenciaFilhos()
    {
        return $this->hasMany(FaturaCartaoItem::class, 'recorrencia_pai_id');
    }

    // Scopes de recorrência

    /**
     * Itens que são origens de recorrência ativa (não cancelados, sem pai)
     */
    public function scopeRecorrenciasAtivas($query)
    {
        return $query->where('recorrente', true)
            ->whereNull('recorrencia_pai_id')
            ->whereNull('cancelado_em');
    }

    /**
     * Verifica se esta recorrência ainda está ativa
     */
    public function isRecorrenciaAtiva(): bool
    {
        if (!$this->recorrente) {
            return false;
        }
        if ($this->cancelado_em !== null) {
            return false;
        }
        if ($this->recorrencia_fim !== null && $this->recorrencia_fim->lt(now())) {
            return false;
        }
        return true;
    }
}
