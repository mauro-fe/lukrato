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
        'status',
    ];

    // Constantes de status
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PARCIAL = 'parcial';
    public const STATUS_PAGA = 'paga';
    public const STATUS_CANCELADO = 'cancelado';

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
        return $this->belongsTo(Usuario::class, 'user_id');
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
        return (float) $this->itens()->where('pago', 1)->sum('valor_parcela');
    }

    /**
     * Calcula o total pendente
     */
    public function getValorPendenteAttribute(): float
    {
        return (float) $this->itens()->where('pago', 0)->sum('valor_parcela');
    }

    /**
     * Calcula o progresso de pagamento (0-100)
     */
    public function getProgressoAttribute(): int
    {
        // Usar itens já carregados se disponível, senão fazer query
        if ($this->relationLoaded('itens')) {
            $totalItens = $this->itens->count();
            $pagas = $this->itens->where('pago', 1)->count();
        } else {
            $totalItens = $this->itens()->count();
            $pagas = $this->itens()->where('pago', 1)->count();
        }

        if ($totalItens <= 0) {
            return 0;
        }

        $progresso = (int) round(($pagas / $totalItens) * 100);
        return min($progresso, 100); // Nunca ultrapassa 100%
    }

    /**
     * Atualiza o status da fatura baseado nas parcelas pagas
     */
    public function atualizarStatus(): void
    {
        // Recarregar itens para garantir dados atualizados
        $this->load('itens');

        $totalItens = $this->itens->count();
        $itensPagos = $this->itens->where('pago', 1)->count();

        error_log("🔍 [FATURA STATUS] Fatura ID: {$this->id}, Total: {$totalItens}, Pagos: {$itensPagos}");

        if ($totalItens === 0) {
            $this->status = self::STATUS_PENDENTE;
            error_log("📊 [FATURA STATUS] Status: PENDENTE (sem itens)");
        } elseif ($itensPagos === 0) {
            $this->status = self::STATUS_PENDENTE;
            error_log("📊 [FATURA STATUS] Status: PENDENTE (nenhum item pago)");
        } elseif ($itensPagos >= $totalItens) {
            $this->status = self::STATUS_PAGA;
            error_log("✅ [FATURA STATUS] Status: PAGA (todos itens pagos)");
        } else {
            $this->status = self::STATUS_PARCIAL;
            error_log("📊 [FATURA STATUS] Status: PARCIAL ({$itensPagos}/{$totalItens} pagos)");
        }

        $this->save();
        error_log("💾 [FATURA STATUS] Status salvo: {$this->status}");
    }

    /**
     * Calcula o status atual baseado nas parcelas (sem salvar)
     */
    public function calcularStatus(): string
    {
        $totalItens = $this->itens()->count();
        $itensPagos = $this->itens()->where('pago', 1)->count();

        if ($totalItens === 0 || $itensPagos === 0) {
            return self::STATUS_PENDENTE;
        } elseif ($itensPagos >= $totalItens) {
            return self::STATUS_PAGA;
        } else {
            return self::STATUS_PARCIAL;
        }
    }
}
