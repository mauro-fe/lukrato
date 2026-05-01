<?php

namespace Application\Models;

use Application\Casts\MoneyDecimalCast;
use Illuminate\Database\Capsule\Manager as Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class CartaoCredito
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $conta_id
 * @property string $nome_cartao
 * @property string|null $bandeira
 * @property string|null $ultimos_digitos
 * @property float|null $limite_total
 * @property float|string|null $limite_disponivel
 * @property int|null $dia_vencimento
 * @property int|null $dia_fechamento
 * @property string|null $cor_cartao
 * @property bool $ativo
 *
 * @property-read Conta|null $conta
 * @property-read Usuario|null $usuario
 * @property-read string $numero_mascarado
 * @property-read float $limite_utilizado
 * @property-read float $percentual_uso
 * @property-read string|null $proximo_vencimento
 *
 * @method static \Illuminate\Database\Eloquent\Builder|CartaoCredito where(string $column, $value = null)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class CartaoCredito extends Model
{
    protected $table = 'cartoes_credito';

    protected $fillable = [
        'user_id',
        'conta_id',
        'nome_cartao',
        'bandeira',
        'ultimos_digitos',
        'limite_total',
        'limite_disponivel',
        'dia_vencimento',
        'dia_fechamento',
        'cor_cartao',
        'ativo',
        'arquivado',
        'lembrar_fatura_antes_segundos',
        'fatura_canal_email',
        'fatura_canal_inapp',
        'fatura_notificado_mes',
    ];

    protected $casts = [
        'user_id' => 'int',
        'conta_id' => 'int',
        'limite_total' => MoneyDecimalCast::class,
        'limite_disponivel' => MoneyDecimalCast::class,
        'dia_vencimento' => 'int',
        'dia_fechamento' => 'int',
        'ativo' => 'bool',
        'arquivado' => 'bool',
        'lembrar_fatura_antes_segundos' => 'int',
        'fatura_canal_email' => 'bool',
        'fatura_canal_inapp' => 'bool',
    ];

    protected $appends = ['numero_mascarado', 'limite_utilizado', 'limite_disponivel_real', 'percentual_uso'];

    /**
     * Relacionamento com usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Relacionamento com conta
     */
    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'conta_id');
    }

    /**
     * Lançamentos deste cartão
     */
    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'cartao_credito_id');
    }

    /**
     * Itens de fatura deste cartão
     */
    public function itensFatura(): HasMany
    {
        return $this->hasMany(FaturaCartaoItem::class, 'cartao_credito_id');
    }

    /**
     * Scope para cartões do usuário
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para cartões ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true)->where('arquivado', false);
    }

    /**
     * Scope para cartões de uma conta específica
     */
    public function scopeDaConta($query, int $contaId)
    {
        return $query->where('conta_id', $contaId);
    }

    /**
     * Retorna número mascarado do cartão
     */
    public function getNumeroMascaradoAttribute(): string
    {
        return '**** **** **** ' . $this->ultimos_digitos;
    }

    /**
     * Retorna limite utilizado (calcula dinamicamente baseado em itens não pagos)
     * 
     * Lógica:
     * - Soma despesas não pagas (valores positivos)
     * - Subtrai estornos (valores negativos) que já liberam o limite
     */
    public function getLimiteUtilizadoAttribute(): float
    {
        // Busca total de despesas não pagas (valores positivos)
        $totalDespesasNaoPagas = Manager::table('faturas_cartao_itens')
            ->where('cartao_credito_id', $this->id)
            ->where('pago', false)
            ->where('tipo', '!=', 'estorno')
            ->sum('valor');

        // Busca total de estornos (valores negativos, que liberam limite)
        // Estornos têm pago=true mas devem ser contabilizados para liberar limite
        $totalEstornos = Manager::table('faturas_cartao_itens')
            ->where('cartao_credito_id', $this->id)
            ->where('tipo', 'estorno')
            ->sum('valor'); // Já é negativo

        // Limite utilizado = despesas não pagas + estornos (que são negativos, então diminuem)
        return (float) max(0, $totalDespesasNaoPagas + $totalEstornos);
    }

    /**
     * Retorna limite disponível real (calcula dinamicamente)
     */
    public function getLimiteDisponivelRealAttribute(): float
    {
        return (float) ($this->limite_total - $this->limite_utilizado);
    }

    /**
     * Retorna percentual de uso do limite (calcula dinamicamente)
     */
    public function getPercentualUsoAttribute(): float
    {
        if ($this->limite_total <= 0) {
            return 0;
        }

        return round(($this->limite_utilizado / $this->limite_total) * 100, 2);
    }

    /**
     * Retorna ícone da bandeira
     */
    public function getBandeiraIconeAttribute(): string
    {
        $icones = [
            'visa' => 'credit-card',
            'mastercard' => 'credit-card',
            'elo' => 'credit-card',
            'amex' => 'credit-card',
            'hipercard' => 'credit-card',
            'diners' => 'credit-card',
        ];

        return $icones[strtolower($this->bandeira)] ?? 'credit-card';
    }

    /**
     * Atualiza limite disponível baseado nos itens de fatura não pagos e estornos
     */
    public function atualizarLimiteDisponivel(): void
    {
        // Usa o accessor calculado que já considera despesas e estornos
        $totalUtilizado = (float) $this->limite_utilizado;
        $novoLimiteDisponivel = (float) ($this->getRawOriginal('limite_total') ?? 0) - $totalUtilizado;

        // Decimal casts do Eloquent usam brick/math; enviar string evita warnings/deprecations.
        $this->limite_disponivel = number_format($novoLimiteDisponivel, 2, '.', '');
        $this->save();
    }

    /**
     * Verifica se tem limite disponível
     */
    public function temLimiteDisponivel(float $valor): bool
    {
        return $this->limite_disponivel >= $valor;
    }

    /**
     * Calcula data de vencimento da próxima fatura
     */
    public function getProximoVencimentoAttribute(): ?string
    {
        if (!$this->dia_vencimento) {
            return null;
        }

        $hoje = new \DateTime();
        $mesAtual = (int) $hoje->format('n');
        $anoAtual = (int) $hoje->format('Y');
        $diaAtual = (int) $hoje->format('j');

        // Se já passou o vencimento deste mês, próximo vencimento é mês que vem
        if ($diaAtual > $this->dia_vencimento) {
            $mesProximo = $mesAtual + 1;
            $anoProximo = $anoAtual;

            if ($mesProximo > 12) {
                $mesProximo = 1;
                $anoProximo++;
            }
        } else {
            $mesProximo = $mesAtual;
            $anoProximo = $anoAtual;
        }

        $diaReal = min($this->dia_vencimento, (int) date('t', mktime(0, 0, 0, $mesProximo, 1, $anoProximo)));

        return sprintf('%04d-%02d-%02d', $anoProximo, $mesProximo, $diaReal);
    }
}
