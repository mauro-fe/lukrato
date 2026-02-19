<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Meta - Metas financeiras do usuário
 *
 * @property int $id
 * @property int $user_id
 * @property string $titulo
 * @property string|null $descricao
 * @property string $tipo
 * @property float $valor_alvo
 * @property float $valor_atual
 * @property \Carbon\Carbon|string $data_inicio
 * @property \Carbon\Carbon|string|null $data_prazo
 * @property string $icone
 * @property string $cor
 * @property int|null $conta_id
 * @property string $prioridade
 * @property string $status
 * @property \Carbon\Carbon|string|null $created_at
 * @property \Carbon\Carbon|string|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder ativas()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Meta extends Model
{
    protected $table = 'metas';
    public $timestamps = true;

    public const TIPO_ECONOMIA      = 'economia';
    public const TIPO_QUITACAO      = 'quitacao';
    public const TIPO_INVESTIMENTO  = 'investimento';
    public const TIPO_COMPRA        = 'compra';
    public const TIPO_EMERGENCIA    = 'emergencia';

    public const STATUS_ATIVA       = 'ativa';
    public const STATUS_CONCLUIDA   = 'concluida';
    public const STATUS_PAUSADA     = 'pausada';
    public const STATUS_CANCELADA   = 'cancelada';

    public const PRIORIDADE_BAIXA   = 'baixa';
    public const PRIORIDADE_MEDIA   = 'media';
    public const PRIORIDADE_ALTA    = 'alta';

    protected $fillable = [
        'user_id',
        'titulo',
        'descricao',
        'tipo',
        'valor_alvo',
        'valor_atual',
        'data_inicio',
        'data_prazo',
        'icone',
        'cor',
        'conta_id',
        'prioridade',
        'status',
    ];

    protected $casts = [
        'user_id'     => 'int',
        'valor_alvo'  => 'float',
        'valor_atual' => 'float',
        'conta_id'    => 'int',
        'data_inicio' => 'date:Y-m-d',
        'data_prazo'  => 'date:Y-m-d',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeAtivas($q)
    {
        return $q->where('status', self::STATUS_ATIVA);
    }

    public function scopeConcluidas($q)
    {
        return $q->where('status', self::STATUS_CONCLUIDA);
    }

    public function scopeByPrioridade($q, string $prioridade)
    {
        return $q->where('prioridade', $prioridade);
    }

    // ============================================================
    // ACCESSORS / COMPUTED
    // ============================================================

    /**
     * Percentual de progresso (0-100)
     */
    public function getProgressoAttribute(): float
    {
        if ($this->valor_alvo <= 0) return 100.0;
        return min(100, round(($this->valor_atual / $this->valor_alvo) * 100, 1));
    }

    /**
     * Valor restante para atingir a meta
     */
    public function getValorRestanteAttribute(): float
    {
        return max(0, $this->valor_alvo - $this->valor_atual);
    }

    /**
     * Dias restantes até o prazo (null se sem prazo)
     */
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->data_prazo) return null;
        $hoje = new \DateTime();
        $prazo = new \DateTime($this->data_prazo instanceof \Carbon\Carbon ? $this->data_prazo->format('Y-m-d') : $this->data_prazo);
        $diff = $hoje->diff($prazo);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Valor mensal sugerido para atingir a meta no prazo
     */
    public function getAporteMensalSugeridoAttribute(): ?float
    {
        $diasRestantes = $this->dias_restantes;
        if ($diasRestantes === null || $diasRestantes <= 0) return null;

        $mesesRestantes = max(1, ceil($diasRestantes / 30));
        $valorRestante = $this->valor_restante;

        if ($valorRestante <= 0) return 0;
        return round($valorRestante / $mesesRestantes, 2);
    }

    /**
     * Se a meta está atrasada (passou do prazo sem concluir)
     */
    public function isAtrasada(): bool
    {
        if ($this->status !== self::STATUS_ATIVA) return false;
        $dias = $this->dias_restantes;
        return $dias !== null && $dias < 0;
    }

    /**
     * Se a meta foi concluída (valor_atual >= valor_alvo)
     */
    public function isCompleta(): bool
    {
        return $this->valor_atual >= $this->valor_alvo;
    }

    /**
     * Retorna o array serializado para a API
     */
    public function toApiArray(): array
    {
        return [
            'id'                     => $this->id,
            'titulo'                 => $this->titulo,
            'descricao'              => $this->descricao,
            'tipo'                   => $this->tipo,
            'valor_alvo'             => $this->valor_alvo,
            'valor_atual'            => $this->valor_atual,
            'data_inicio'            => $this->data_inicio?->format('Y-m-d'),
            'data_prazo'             => $this->data_prazo?->format('Y-m-d'),
            'icone'                  => $this->icone,
            'cor'                    => $this->cor,
            'conta_id'               => $this->conta_id,
            'prioridade'             => $this->prioridade,
            'status'                 => $this->status,
            'progresso'              => $this->progresso,
            'valor_restante'         => $this->valor_restante,
            'dias_restantes'         => $this->dias_restantes,
            'aporte_mensal_sugerido' => $this->aporte_mensal_sugerido,
            'is_atrasada'            => $this->isAtrasada(),
            'is_completa'            => $this->isCompleta(),
            'conta_nome'             => $this->conta?->nome,
            'created_at'             => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
