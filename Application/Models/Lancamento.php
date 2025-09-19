<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Lancamento extends Model
{
    protected $table = 'lancamentos';
    public $timestamps = true;


    public const TIPO_RECEITA        = 'receita';
    public const TIPO_DESPESA        = 'despesa';
    public const TIPO_TRANSFERENCIA  = 'transferencia';

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
    ];

    protected $casts = [
        'user_id'           => 'int',
        'categoria_id'      => 'int',
        'conta_id'          => 'int',
        'conta_id_destino'  => 'int',
        'data'              => 'date:Y-m-d',
        'valor'             => 'float',
        'eh_transferencia'  => 'bool',
        'eh_saldo_inicial'  => 'bool',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    public function contaDestino()
    {
        return $this->belongsTo(Conta::class, 'conta_id_destino');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
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
}
