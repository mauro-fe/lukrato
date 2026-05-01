<?php

namespace Application\Models;

use Application\Casts\MoneyDecimalCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * - recorrenciaPai: opcional, para agrupar lançamentos recorrentes
 * 
 * Recorrência e Lembretes (substitui Agendamentos):
 * - recorrente: Se é lançamento recorrente (infinito ou até uma data)
 * - recorrencia_freq: Frequência (semanal, mensal, etc.)
 * - recorrencia_fim: Data limite (null = infinito)
 * - recorrencia_pai_id: FK para o primeiro lançamento do grupo
 * - cancelado_em: Quando recorrência/futuro foi cancelado
 * - lembrar_antes_segundos: Antecedência do lembrete
 * - canal_email/canal_inapp: Canais de notificação
 */
/**
 * @property int $id
 * @property int $user_id
 * @property string $tipo
 * @property \Carbon\Carbon|string $data
 * @property \Carbon\Carbon|string|null $data_competencia
 * @property int|null $categoria_id
 * @property int|null $subcategoria_id
 * @property int|null $meta_id
 * @property string|null $meta_operacao
 * @property float|null $meta_valor
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
 * @property bool $recorrente
 * @property string|null $recorrencia_freq
 * @property \Carbon\Carbon|string|null $recorrencia_fim
 * @property int|null $recorrencia_total
 * @property int|null $recorrencia_pai_id
 * @property \Carbon\Carbon|string|null $cancelado_em
 * @property int|null $lembrar_antes_segundos
 * @property bool $canal_email
 * @property bool $canal_inapp
 * @property \Carbon\Carbon|string|null $notificado_em
 * @property \Carbon\Carbon|string|null $lembrete_antecedencia_em
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder forUser(int $userId)
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Lancamento extends Model
{
    use SoftDeletes;

    protected $table = 'lancamentos';
    public $timestamps = true;


    public const TIPO_RECEITA        = 'receita';
    public const TIPO_DESPESA        = 'despesa';
    public const TIPO_TRANSFERENCIA  = 'transferencia';

    public const META_OPERACAO_APORTE = 'aporte';
    public const META_OPERACAO_RESGATE = 'resgate';
    public const META_OPERACAO_REALIZACAO = 'realizacao';

    // Constantes para origem_tipo
    public const ORIGEM_NORMAL         = 'normal';
    public const ORIGEM_CARTAO_CREDITO = 'cartao_credito';
    public const ORIGEM_PARCELAMENTO   = 'parcelamento';
    public const ORIGEM_AGENDAMENTO    = 'agendamento';
    public const ORIGEM_TRANSFERENCIA  = 'transferencia';
    public const ORIGEM_RECORRENCIA       = 'recorrencia';
    public const ORIGEM_PAGAMENTO_FATURA  = 'pagamento_fatura';

    protected $fillable = [
        'user_id',
        'tipo',
        'data',
        'hora_lancamento',
        'categoria_id',
        'subcategoria_id',
        'meta_id',
        'meta_operacao',
        'meta_valor',
        'conta_id',
        'conta_id_destino',
        'descricao',
        'observacao',
        'valor',
        'eh_transferencia',
        'eh_saldo_inicial',
        // Campos de cartão de crédito
        'cartao_credito_id',
        'forma_pagamento',  // pix, cartao_credito, cartao_debito, dinheiro, boleto, deposito, transferencia, estorno_cartao
        'eh_parcelado',
        'parcela_atual',
        'total_parcelas',
        'pago',
        'data_pagamento',
        // Campos de parcelamento
        'parcelamento_id',
        'numero_parcela',
        // Campos de competência
        'data_competencia',
        'afeta_competencia',
        'afeta_caixa',
        'origem_tipo',
        // Campos de recorrência e lembrete
        'recorrente',
        'recorrencia_freq',
        'recorrencia_fim',
        'recorrencia_total',
        'recorrencia_pai_id',
        'cancelado_em',
        'lembrar_antes_segundos',
        'canal_email',
        'canal_inapp',
        'notificado_em',
        'lembrete_antecedencia_em',
    ];

    protected $casts = [
        'user_id'           => 'int',
        'categoria_id'      => 'int',
        'subcategoria_id'   => 'int',
        'meta_id'           => 'int',
        'meta_valor'        => 'float',
        'conta_id'          => 'int',
        'conta_id_destino'  => 'int',
        'data'              => 'date:Y-m-d',
        'data_pagamento'    => 'date:Y-m-d',
        'data_competencia'  => 'date:Y-m-d',
        'valor'             => MoneyDecimalCast::class,
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
        'recorrente'        => 'bool',
        'recorrencia_fim'   => 'date:Y-m-d',
        'recorrencia_total' => 'int',
        'recorrencia_pai_id' => 'int',
        'cancelado_em'      => 'datetime',
        'lembrar_antes_segundos' => 'int',
        'canal_email'       => 'bool',
        'canal_inapp'       => 'bool',
        'notificado_em'     => 'datetime',
        'lembrete_antecedencia_em' => 'datetime',
    ];


    /**
     * Relacionamento com Parcelamento (opcional - apenas para agrupamento)
     * Um lançamento PODE pertencer a um parcelamento (cabeçalho)
     */
    public function parcelamento(): BelongsTo
    {
        return $this->belongsTo(Parcelamento::class, 'parcelamento_id');
    }

    /**
     * Relacionamento com Cartão de Crédito (opcional)
     * Um lançamento PODE estar vinculado a um cartão de crédito
     */
    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class, 'cartao_credito_id');
    }

    /**
     * Lançamento "pai" da recorrência (primeiro do grupo)
     */
    public function recorrenciaPai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recorrencia_pai_id');
    }

    /**
     * Lançamentos filhos (gerados por recorrência)
     */
    public function recorrenciaFilhos(): HasMany
    {
        return $this->hasMany(self::class, 'recorrencia_pai_id');
    }

    /**
     * Verifica se este lançamento é recorrente (pai ou filho)
     */
    public function isRecorrente(): bool
    {
        return (bool)$this->recorrente || !empty($this->recorrencia_pai_id);
    }

    /**
     * Verifica se é um lançamento futuro não pago
     */
    public function isFuturo(): bool
    {
        return !$this->pago && $this->data > now()->format('Y-m-d');
    }

    /**
     * Verifica se foi cancelado
     */
    public function isCancelado(): bool
    {
        return !empty($this->cancelado_em);
    }

    /**
     * Relacionamento com Usuário (obrigatório)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Relacionamento com Categoria (opcional)
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relacionamento com Subcategoria (opcional)
     */
    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'subcategoria_id');
    }

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class, 'meta_id');
    }

    /**
     * Relacionamento com Conta (opcional)
     */
    public function conta(): BelongsTo
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



    public function contaDestino(): BelongsTo
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
        // Armazenar como string para evitar deprecation do BigNumber::of() com float
        $this->attributes['valor'] = MoneyDecimalCast::normalize($v) ?? '0.00';
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
        return $query->where('afeta_caixa', 1);
    }

    /**
     * Scope para filtrar por origem
     */
    public function scopeOrigem($query, string $tipo)
    {
        return $query->where('origem_tipo', $tipo);
    }
}
