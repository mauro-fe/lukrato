<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Parcelamento - Tabela AUXILIAR (Cabeçalho)
 * 
 * IMPORTANTE: Este model NÃO é a fonte da verdade financeira!
 * 
 * Finalidade:
 * - Agrupar visualmente múltiplas parcelas
 * - Facilitar edição/exclusão em bloco
 * - Melhorar UX (mostrar "3/12" ao invés de 12 linhas separadas)
 * 
 * A VERDADE FINANCEIRA está em:
 * - `lancamentos`: cada parcela é um registro individual
 * - Saldo, relatórios e gráficos usam APENAS `lancamentos`
 * 
 * Fluxo:
 * 1. Usuário cria parcelamento (ex: compra de R\$ 1200 em 12x)
 * 2. Sistema cria 1 registro em `parcelamentos` (cabeçalho)
 * 3. Sistema cria 12 registros em `lancamentos` (R\$ 100 cada)
 * 4. Cada lançamento tem `parcelamento_id` apontando para o cabeçalho
 * 
 * Uso correto:
 * - Cartão de crédito: criar parcelamento + lançamentos
 * - Agendamentos: NÃO usar parcelamento (lógica própria)
 */
class Parcelamento extends Model
{
    protected $table = 'parcelamentos';
    public $timestamps = true;

    public const TIPO_ENTRADA = 'entrada';
    public const TIPO_SAIDA = 'saida';

    public const STATUS_ATIVO = 'ativo';
    public const STATUS_CANCELADO = 'cancelado';
    public const STATUS_CONCLUIDO = 'concluido';

    protected $fillable = [
        'user_id',
        'descricao',
        'valor_total',
        'numero_parcelas',
        'parcelas_pagas',
        'categoria_id',
        'conta_id',
        'cartao_credito_id',
        'tipo',
        'status',
        'data_criacao',
    ];

    protected $casts = [
        'user_id' => 'int',
        'valor_total' => 'float',
        'numero_parcelas' => 'int',
        'parcelas_pagas' => 'int',
        'categoria_id' => 'int',
        'conta_id' => 'int',
        'cartao_credito_id' => 'int',
        'data_criacao' => 'date:Y-m-d',
    ];

    /**
     * Relacionamento com Lançamentos (1:N)
     * Um parcelamento TEM MUITOS lançamentos (as parcelas)
     * CADA PARCELA É UM LANÇAMENTO INDIVIDUAL
     */
    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class, 'parcelamento_id')
            ->orderBy('data', 'asc');
    }

    /**
     * Relacionamento com Usuário
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Relacionamento com Categoria
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relacionamento com Conta
     */
    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    /**
     * Relacionamento com Cartão de Crédito (opcional)
     */
    public function cartaoCredito()
    {
        return $this->belongsTo(CartaoCredito::class, 'cartao_credito_id');
    }

    /**
     * Calcular valor de cada parcela
     */
    public function getValorParcelaAttribute(): float
    {
        if (empty($this->numero_parcelas) || $this->numero_parcelas == 0) {
            return 0;
        }
        return $this->valor_total / $this->numero_parcelas;
    }

    /**
     * Verificar se todas as parcelas foram pagas
     * ATENÇÃO: Usa `lancamentos` como fonte da verdade
     */
    public function isCompleto(): bool
    {
        $totalParcelas = $this->lancamentos()->count();
        $parcelasPagas = $this->lancamentos()->where('pago', true)->count();

        return $totalParcelas > 0 && $totalParcelas === $parcelasPagas;
    }

    /**
     * Calcular percentual pago
     * ATENÇÃO: Usa `lancamentos` como fonte da verdade
     */
    public function getPercentualPagoAttribute(): float
    {
        // Se o relacionamento já foi carregado, usar os dados carregados
        if ($this->relationLoaded('lancamentos')) {
            $totalParcelas = $this->lancamentos->count();
            if ($totalParcelas == 0) return 0;

            $parcelasPagas = $this->lancamentos->where('pago', true)->count();
            return ($parcelasPagas / $totalParcelas) * 100;
        }

        // Caso contrário, fazer query
        $totalParcelas = $this->lancamentos()->count();
        if ($totalParcelas == 0) return 0;

        $parcelasPagas = $this->lancamentos()->where('pago', true)->count();
        return ($parcelasPagas / $totalParcelas) * 100;
    }

    /**
     * Calcular valor restante
     * ATENÇÃO: Usa `lancamentos` como fonte da verdade
     */
    public function getValorRestanteAttribute(): float
    {
        // Se o relacionamento já foi carregado, usar os dados carregados
        if ($this->relationLoaded('lancamentos')) {
            $valorPago = $this->lancamentos->where('pago', true)->sum('valor');
            return $this->valor_total - $valorPago;
        }

        // Caso contrário, fazer query
        $valorPago = $this->lancamentos()
            ->where('pago', true)
            ->sum('valor');

        return $this->valor_total - $valorPago;
    }
}
