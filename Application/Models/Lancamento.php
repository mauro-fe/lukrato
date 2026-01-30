<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Lancamento - Fonte única da verdade financeira
 * 
 * Este model representa TODOS os fatos financeiros do sistema:
 * - Lançamentos simples (receita/despesa)
 * - Parcelas de cartão de crédito (cada parcela = 1 lançamento)
 * - Lançamentos oriundos de agendamentos pagos
 * 
 * REFATORAÇÃO - Separação Competência vs Caixa:
 * - data: Data do fluxo de caixa (quando afeta saldo)
 * - data_competencia: Data da despesa real (quando ocorreu)
 * - afeta_competencia: Se conta nos relatórios do mês de competência
 * - afeta_caixa: Se afeta saldo disponível
 * - origem_tipo: Tipo de origem (normal, cartao_credito, etc)
 * 
 * Relacionamentos:
 * - parcelamento: opcional, para agrupar parcelas visualmente
 * - cartaoCredito: opcional, para lançamentos de cartão
 * - agendamento: opcional, para rastreio de origem
 */
/**
 * @property int $id
 * @property int $user_id
 * @property string $tipo
 * @property \Carbon\Carbon|string $data
 * @property \Carbon\Carbon|string|null $data_competencia
 * @property int|null $categoria_id
 * @property int|null $conta_id
 * @property int|null $conta_id_destino
 * @property string|null $descricao
 * @property string|null $observacao
 * @property float $valor
 * @property bool $eh_transferencia
 * @property bool $eh_saldo_inicial
 * @property int|null $cartao_credito_id
 * @property bool $eh_parcelado
 * @property int|null $parcela_atual
 * @property int|null $total_parcelas
 * @property bool $pago
 * @property \Carbon\Carbon|string|null $data_pagamento
 * @property int|null $parcelamento_id
 * @property int|null $numero_parcela
 * @property bool $afeta_competencia
 * @property bool $afeta_caixa
 * @property string|null $origem_tipo
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder forUser(int $userId)
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Lancamento extends Model
{
    protected $table = 'lancamentos';
    public $timestamps = true;


    public const TIPO_RECEITA        = 'receita';
    public const TIPO_DESPESA        = 'despesa';
    public const TIPO_TRANSFERENCIA  = 'transferencia';

    // Constantes para origem_tipo
    public const ORIGEM_NORMAL         = 'normal';
    public const ORIGEM_CARTAO_CREDITO = 'cartao_credito';
    public const ORIGEM_PARCELAMENTO   = 'parcelamento';
    public const ORIGEM_AGENDAMENTO    = 'agendamento';
    public const ORIGEM_TRANSFERENCIA  = 'transferencia';

    protected $fillable = [
        'user_id',
        'tipo',
        'data',
        'categoria_id',
        'conta_id',
        'conta_id_destino',
        'descricao',
        'observacao',
        'valor',
        'eh_transferencia',
        'eh_saldo_inicial',
        // Campos de cartão de crédito
        'cartao_credito_id',
        'eh_parcelado',
        'parcela_atual',
        'total_parcelas',
        'pago',
        'data_pagamento',
        // Campos de parcelamento
        'parcelamento_id',
        'numero_parcela',
        // Campos de competência (novos)
        'data_competencia',
        'afeta_competencia',
        'afeta_caixa',
        'origem_tipo',
    ];

    protected $casts = [
        'user_id'           => 'int',
        'categoria_id'      => 'int',
        'conta_id'          => 'int',
        'conta_id_destino'  => 'int',
        'data'              => 'date:Y-m-d',
        'data_pagamento'    => 'date:Y-m-d',
        'data_competencia'  => 'date:Y-m-d',
        'valor'             => 'float',
        'eh_transferencia'  => 'bool',
        'eh_saldo_inicial'  => 'bool',
        'cartao_credito_id' => 'int',
        'eh_parcelado'      => 'bool',
        'parcela_atual'     => 'int',
        'total_parcelas'    => 'int',
        'pago'              => 'bool',
        'parcelamento_id'   => 'int',
        'numero_parcela'    => 'int',
        'afeta_competencia' => 'bool',
        'afeta_caixa'       => 'bool',
    ];


    /**
     * Relacionamento com Parcelamento (opcional - apenas para agrupamento)
     * Um lançamento PODE pertencer a um parcelamento (cabeçalho)
     */
    public function parcelamento()
    {
        return $this->belongsTo(Parcelamento::class, 'parcelamento_id');
    }

    /**
     * Relacionamento com Cartão de Crédito (opcional)
     * Um lançamento PODE estar vinculado a um cartão de crédito
     */
    public function cartaoCredito()
    {
        return $this->belongsTo(CartaoCredito::class, 'cartao_credito_id');
    }

    /**
     * Relacionamento com Usuário (obrigatório)
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Relacionamento com Categoria (opcional)
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relacionamento com Conta (opcional)
     */
    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    /**
     * Verifica se este lançamento é uma parcela (possui parcelamento_id)
     */
    public function isParcela(): bool
    {
        return !empty($this->parcelamento_id);
    }



    public function contaDestino()
    {
        return $this->belongsTo(Conta::class, 'conta_id_destino');
    }





    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeMonth($q, string $yyyy_mm)
    {
        [$y, $m] = array_map('intval', explode('-', $yyyy_mm));
        return $q->whereYear('data', $y)->whereMonth('data', $m);
    }

    public function scopeBetweenDates($q, string $startDate, string $endDate)
    {
        return $q->whereBetween('data', [$startDate, $endDate]);
    }

    public function scopeNotTransfer($q)
    {
        return $q->where('eh_transferencia', 0);
    }

    public function scopeOnlyTransfer($q)
    {
        return $q->where('eh_transferencia', 1);
    }

    public function scopeByAccount($q, int $contaId)
    {
        return $q->where(function ($w) use ($contaId) {
            $w->where('conta_id', $contaId)
                ->orWhere('conta_id_destino', $contaId);
        });
    }

    public function scopeOnlyReceitas($q)
    {
        return $q->where('eh_transferencia', 0)
            ->where('tipo', self::TIPO_RECEITA);
    }

    public function scopeOnlyDespesas($q)
    {
        return $q->where('eh_transferencia', 0)
            ->where('tipo', self::TIPO_DESPESA);
    }

    public function setValorAttribute($v): void
    {
        if (is_string($v)) {
            $s = trim($v);
            $s = str_replace(['R$', ' ', '.'], ['', '', ''], $s);
            $s = str_replace(',', '.', $s);
            $v = is_numeric($s) ? (float)$s : 0.0;
        }
        $this->attributes['valor'] = (float)$v;
    }

    public function sinal(): int
    {
        if ($this->eh_transferencia) {
            return 0;
        }
        return $this->tipo === self::TIPO_RECEITA ? 1
            : ($this->tipo === self::TIPO_DESPESA ? -1 : 0);
    }

    public function valorAssinado(): float
    {
        return $this->sinal() * (float)$this->valor;
    }
    public function valorAssinadoPorConta(int $contaId): float
    {
        if (!$this->eh_transferencia) {
            return $this->valorAssinado();
        }
        if ((int)$this->conta_id === $contaId) {
            return -1 * (float)$this->valor;
        }
        if ((int)$this->conta_id_destino === $contaId) {
            return +1 * (float)$this->valor;
        }
        return 0.0;
    }
    public function getContaNomeAttribute(): string
    {
        $conta = $this->relationLoaded('conta') ? $this->conta : $this->conta()->first();
        if ($conta) {
            return $conta->nome ?: ($conta->instituicao ?: '—');
        }
        return '—';
    }

    // ============================================================================
    // MÉTODOS DE COMPETÊNCIA (Refatoração Cartão de Crédito)
    // ============================================================================

    /**
     * Retorna a data de competência efetiva do lançamento
     * Se data_competencia estiver preenchida, usa ela; senão, usa data
     * 
     * @return \Carbon\Carbon|string
     */
    public function getDataCompetenciaEfetivaAttribute()
    {
        return $this->data_competencia ?? $this->data;
    }

    /**
     * Verifica se este lançamento é de cartão de crédito
     * 
     * @return bool
     */
    public function isCartaoCredito(): bool
    {
        return $this->origem_tipo === self::ORIGEM_CARTAO_CREDITO || !empty($this->cartao_credito_id);
    }

    /**
     * Verifica se o lançamento tem competência diferente do caixa
     * (Ex: compra em janeiro, pagamento em fevereiro)
     * 
     * @return bool
     */
    public function temCompetenciaDiferente(): bool
    {
        if (!$this->data_competencia) {
            return false;
        }

        $dataCompetencia = $this->data_competencia instanceof \Carbon\Carbon
            ? $this->data_competencia->format('Y-m')
            : substr($this->data_competencia, 0, 7);

        $dataCaixa = $this->data instanceof \Carbon\Carbon
            ? $this->data->format('Y-m')
            : substr($this->data, 0, 7);

        return $dataCompetencia !== $dataCaixa;
    }

    /**
     * Scope para filtrar por mês de competência
     * Usa data_competencia se disponível, senão usa data
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $start Data inicial (Y-m-d)
     * @param string $end Data final (Y-m-d)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompetenciaEntre($query, string $start, string $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            // Se tem data_competencia, usa ela
            $q->whereBetween('data_competencia', [$start, $end])
                // Senão, fallback para data
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->whereNull('data_competencia')
                        ->whereBetween('data', [$start, $end]);
                });
        });
    }

    /**
     * Scope para filtrar por mês de caixa (fluxo de caixa)
     * Sempre usa campo data
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $start Data inicial (Y-m-d)
     * @param string $end Data final (Y-m-d)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCaixaEntre($query, string $start, string $end)
    {
        return $query->whereBetween('data', [$start, $end]);
    }

    /**
     * Scope para filtrar apenas lançamentos que afetam competência
     */
    public function scopeAfetaCompetencia($query)
    {
        return $query->where(function ($q) {
            $q->where('afeta_competencia', true)
                ->orWhereNull('afeta_competencia'); // Backward compatibility
        });
    }

    /**
     * Scope para filtrar apenas lançamentos que afetam caixa
     */
    public function scopeAfetaCaixa($query)
    {
        return $query->where(function ($q) {
            $q->where('afeta_caixa', true)
                ->orWhereNull('afeta_caixa'); // Backward compatibility
        });
    }

    /**
     * Scope para filtrar por origem
     */
    public function scopeOrigem($query, string $tipo)
    {
        return $query->where('origem_tipo', $tipo);
    }
}
