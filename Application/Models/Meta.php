<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meta extends Model
{
    protected $table = 'metas';
    public $timestamps = true;

    public const TIPO_ECONOMIA = 'economia';
    public const TIPO_QUITACAO = 'quitacao';
    public const TIPO_INVESTIMENTO = 'investimento';
    public const TIPO_COMPRA = 'compra';
    public const TIPO_EMERGENCIA = 'emergencia';
    public const TIPO_VIAGEM = 'viagem';
    public const TIPO_EDUCACAO = 'educacao';
    public const TIPO_MORADIA = 'moradia';
    public const TIPO_VEICULO = 'veiculo';
    public const TIPO_SAUDE = 'saude';
    public const TIPO_NEGOCIO = 'negocio';
    public const TIPO_APOSENTADORIA = 'aposentadoria';
    public const TIPO_OUTRO = 'outro';

    public const STATUS_ATIVA = 'ativa';
    public const STATUS_CONCLUIDA = 'concluida';
    public const STATUS_REALIZADA = 'realizada';
    public const STATUS_PAUSADA = 'pausada';
    public const STATUS_CANCELADA = 'cancelada';

    public const MODELO_RESERVA = 'reserva';
    public const MODELO_REALIZACAO = 'realizacao';

    public const PRIORIDADE_BAIXA = 'baixa';
    public const PRIORIDADE_MEDIA = 'media';
    public const PRIORIDADE_ALTA = 'alta';

    protected $fillable = [
        'user_id',
        'titulo',
        'descricao',
        'tipo',
        'modelo',
        'valor_alvo',
        'valor_alocado',
        'valor_aporte_manual',
        'valor_realizado',
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
        'user_id' => 'int',
        'valor_alvo' => 'float',
        'valor_alocado' => 'float',
        'valor_aporte_manual' => 'float',
        'valor_realizado' => 'float',
        'valor_atual' => 'float',
        'conta_id' => 'int',
        'data_inicio' => 'date:Y-m-d',
        'data_prazo' => 'date:Y-m-d',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'meta_id');
    }

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAtivas($query)
    {
        return $query->where('status', self::STATUS_ATIVA);
    }

    public function scopeConcluidas($query)
    {
        return $query->where('status', self::STATUS_CONCLUIDA);
    }

    public function scopeByPrioridade($query, string $prioridade)
    {
        return $query->where('prioridade', $prioridade);
    }

    public function getValorAlocadoAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return (float) ($this->attributes['valor_atual'] ?? 0);
    }

    public function setValorAlocadoAttribute($value): void
    {
        $normalized = number_format(round(max(0, (float) $value), 2), 2, '.', '');

        $this->attributes['valor_alocado'] = $normalized;
        $this->attributes['valor_atual'] = $normalized;
    }

    public function getValorAtualAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return (float) ($this->attributes['valor_alocado'] ?? 0);
    }

    public function setValorAtualAttribute($value): void
    {
        $this->setValorAlocadoAttribute($value);
    }

    public function getProgressoAttribute(): float
    {
        if ($this->valor_alvo <= 0) {
            return 100.0;
        }

        $base = $this->modelo === self::MODELO_REALIZACAO
            ? (float) $this->valor_alocado + (float) ($this->valor_realizado ?? 0)
            : (float) $this->valor_alocado;

        return min(100, round(($base / $this->valor_alvo) * 100, 1));
    }

    public function getValorRestanteAttribute(): float
    {
        $base = $this->modelo === self::MODELO_REALIZACAO
            ? (float) $this->valor_alocado + (float) ($this->valor_realizado ?? 0)
            : (float) $this->valor_alocado;

        return max(0, $this->valor_alvo - $base);
    }

    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->data_prazo) {
            return null;
        }

        $hoje = new \DateTime();
        $prazo = new \DateTime($this->data_prazo instanceof \Carbon\Carbon ? $this->data_prazo->format('Y-m-d') : $this->data_prazo);
        $diff = $hoje->diff($prazo);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function getAporteMensalSugeridoAttribute(): ?float
    {
        $diasRestantes = $this->dias_restantes;
        if ($diasRestantes === null || $diasRestantes <= 0) {
            return null;
        }

        $mesesRestantes = max(1, ceil($diasRestantes / 30));
        $valorRestante = $this->valor_restante;

        if ($valorRestante <= 0) {
            return 0;
        }

        return round($valorRestante / $mesesRestantes, 2);
    }

    public function isAtrasada(): bool
    {
        if ($this->status !== self::STATUS_ATIVA) {
            return false;
        }

        $dias = $this->dias_restantes;
        return $dias !== null && $dias < 0;
    }

    public function isCompleta(): bool
    {
        return $this->progresso >= 100;
    }

    public function toApiArray(): array
    {
        $valorAtualDisplay = ($this->modelo === self::MODELO_REALIZACAO)
            ? (float) $this->valor_alocado + (float) ($this->valor_realizado ?? 0)
            : (float) $this->valor_atual;

        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'tipo' => $this->tipo,
            'modelo' => $this->modelo ?: self::MODELO_RESERVA,
            'valor_alvo' => $this->valor_alvo,
            'valor_alocado' => $this->valor_alocado,
            'valor_aporte_manual' => (float) ($this->valor_aporte_manual ?? 0),
            'valor_realizado' => (float) ($this->valor_realizado ?? 0),
            'valor_atual' => $valorAtualDisplay,
            'data_inicio' => $this->data_inicio?->format('Y-m-d'),
            'data_prazo' => $this->data_prazo?->format('Y-m-d'),
            'icone' => $this->icone,
            'cor' => $this->cor,
            'conta_id' => null,
            'prioridade' => $this->prioridade,
            'status' => $this->status,
            'progresso' => $this->progresso,
            'valor_restante' => $this->valor_restante,
            'dias_restantes' => $this->dias_restantes,
            'aporte_mensal_sugerido' => $this->aporte_mensal_sugerido,
            'is_atrasada' => $this->isAtrasada(),
            'is_completa' => $this->isCompleta(),
            'conta_nome' => null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
