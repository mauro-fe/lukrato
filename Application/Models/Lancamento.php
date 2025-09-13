<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Lancamento extends Model
{
    protected $table = 'lancamentos';
    public $timestamps = true;

    // ---------------------------
    // Tipos de lançamento
    // ---------------------------
    public const TIPO_RECEITA        = 'receita';
    public const TIPO_DESPESA        = 'despesa';
    public const TIPO_TRANSFERENCIA  = 'transferencia'; // opcional – usamos eh_transferencia=1

    // ---------------------------
    // Mass assignment
    // ---------------------------
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
        'eh_saldo_inicial', // <— NOVO
    ];

    protected $casts = [
        'user_id'           => 'int',
        'categoria_id'      => 'int',
        'conta_id'          => 'int',
        'conta_id_destino'  => 'int',
        'data'              => 'date:Y-m-d',
        'valor'             => 'float',
        'eh_transferencia'  => 'bool',
        'eh_saldo_inicial'  => 'bool', // <— NOVO
    ];

    // ---------------------------
    // Relacionamentos
    // ---------------------------
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

    // ---------------------------
    // Scopes
    // ---------------------------
    /** Filtra por usuário */
    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    /** Filtra por mês YYYY-MM */
    public function scopeMonth($q, string $yyyy_mm)
    {
        [$y, $m] = array_map('intval', explode('-', $yyyy_mm));
        return $q->whereYear('data', $y)->whereMonth('data', $m);
    }

    /** Filtra por período [YYYY-MM-DD .. YYYY-MM-DD] (inclusive) */
    public function scopeBetweenDates($q, string $startDate, string $endDate)
    {
        return $q->whereBetween('data', [$startDate, $endDate]);
    }

    /** Somente lançamentos que NÃO são transferências */
    public function scopeNotTransfer($q)
    {
        return $q->where('eh_transferencia', 0);
    }

    /** Somente transferências */
    public function scopeOnlyTransfer($q)
    {
        return $q->where('eh_transferencia', 1);
    }

    /** Movimentos relativos a uma conta (inclui transferências onde a conta participa) */
    public function scopeByAccount($q, int $contaId)
    {
        return $q->where(function ($w) use ($contaId) {
            $w->where('conta_id', $contaId)
                ->orWhere('conta_id_destino', $contaId);
        });
    }

    /** Apenas Receitas (não-transferências) */
    public function scopeOnlyReceitas($q)
    {
        return $q->where('eh_transferencia', 0)
            ->where('tipo', self::TIPO_RECEITA);
    }

    /** Apenas Despesas (não-transferências) */
    public function scopeOnlyDespesas($q)
    {
        return $q->where('eh_transferencia', 0)
            ->where('tipo', self::TIPO_DESPESA);
    }

    // ---------------------------
    // Mutators / Ajudantes
    // ---------------------------
    /** Aceita "R$ 1.234,56" ou "1234,56" etc. */
    public function setValorAttribute($v): void
    {
        if (is_string($v)) {
            $s = trim($v);
            // remove R$, espaços e separador de milhar
            $s = str_replace(['R$', ' ', '.'], ['', '', ''], $s);
            // vírgula decimal -> ponto
            $s = str_replace(',', '.', $s);
            $v = is_numeric($s) ? (float)$s : 0.0;
        }
        $this->attributes['valor'] = (float)$v;
    }

    /** Retorna +1, -1 ou 0 conforme o tipo (receita/despesa/transferência) */
    public function sinal(): int
    {
        if ($this->eh_transferencia) {
            // o sinal efetivo depende se a conta é origem ou destino; use valorAssinadoPorConta()
            return 0;
        }
        return $this->tipo === self::TIPO_RECEITA ? 1
            : ($this->tipo === self::TIPO_DESPESA ? -1 : 0);
    }

    /** Valor com sinal considerando o tipo (ignora transferência) */
    public function valorAssinado(): float
    {
        return $this->sinal() * (float)$this->valor;
    }

    /**
     * Valor com sinal considerando uma conta específica:
     * - Se não for transferência: idem valorAssinado()
     * - Se for transferência: negativo para conta origem; positivo para conta destino.
     */
    public function valorAssinadoPorConta(int $contaId): float
    {
        if (!$this->eh_transferencia) {
            return $this->valorAssinado();
        }
        if ((int)$this->conta_id === $contaId) {
            return -1 * (float)$this->valor; // saiu da conta origem
        }
        if ((int)$this->conta_id_destino === $contaId) {
            return +1 * (float)$this->valor; // entrou na conta destino
        }
        return 0.0;
    }
    public function getContaNomeAttribute(): string
    {
        // carrega relacionamento se ainda não veio
        $conta = $this->relationLoaded('conta') ? $this->conta : $this->conta()->first();
        if ($conta) {
            return $conta->nome ?: ($conta->instituicao ?: '—');
        }
        return '—';
    }
}
