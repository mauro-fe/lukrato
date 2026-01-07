<?php

declare(strict_types=1);

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model para representar uma fatura de cartão de crédito
 * (compra parcelada que gera múltiplos itens)
 */
class Fatura extends Model
{
    protected $table = 'faturas';

    protected $fillable = [
        'user_id',
        'cartao_credito_id',
        'descricao',
        'valor_total',
        'numero_parcelas',
        'data_compra',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'cartao_credito_id' => 'integer',
        'valor_total' => 'decimal:2',
        'numero_parcelas' => 'integer',
        'data_compra' => 'date',
    ];

    /**
     * Relacionamento: Uma fatura pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento: Uma fatura pertence a um cartão de crédito
     */
    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class, 'cartao_credito_id');
    }

    /**
     * Relacionamento: Uma fatura tem muitos itens (parcelas)
     */
    public function itens(): HasMany
    {
        return $this->hasMany(FaturaCartaoItem::class, 'fatura_id');
    }

    /**
     * Calcula o valor de cada parcela
     */
    public function getValorParcelaAttribute(): float
    {
        if ($this->numero_parcelas <= 0) {
            return 0.0;
        }

        return round($this->valor_total / $this->numero_parcelas, 2);
    }

    /**
     * Verifica se todas as parcelas foram pagas
     */
    public function isPaga(): bool
    {
        return $this->itens()->where('pago', 0)->count() === 0;
    }

    /**
     * Calcula o total já pago
     */
    public function getValorPagoAttribute(): float
    {
        return (float) $this->itens()->where('pago', 1)->sum('valor');
    }

    /**
     * Calcula o total pendente
     */
    public function getValorPendenteAttribute(): float
    {
        return (float) $this->itens()->where('pago', 0)->sum('valor');
    }

    /**
     * Calcula o progresso de pagamento (0-100)
     */
    public function getProgressoAttribute(): int
    {
        if ($this->numero_parcelas <= 0) {
            return 0;
        }

        $pagas = $this->itens()->where('pago', 1)->count();
        return (int) round(($pagas / $this->numero_parcelas) * 100);
    }
}
