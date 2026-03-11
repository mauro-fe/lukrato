<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use DateTime;

class Cupom extends Model
{
    protected $table = 'cupons';

    protected $fillable = [
        'codigo',
        'tipo_desconto',
        'valor_desconto',
        'valido_ate',
        'limite_uso',
        'uso_atual',
        'ativo',
        'apenas_primeira_assinatura',
        'permite_reativacao',
        'meses_inatividade_reativacao',
        'descricao'
    ];

    protected $casts = [
        'valor_desconto' => 'decimal:2',
        'limite_uso' => 'integer',
        'uso_atual' => 'integer',
        'ativo' => 'boolean',
        'apenas_primeira_assinatura' => 'boolean',
        'permite_reativacao' => 'boolean',
        'meses_inatividade_reativacao' => 'integer',
        'valido_ate' => 'datetime'
    ];

    /**
     * Verifica se o cupom é válido
     */
    public function isValid(): bool
    {
        // Verifica se está ativo
        if (!$this->ativo) {
            return false;
        }

        // Verifica se está dentro da validade (considerando data e hora)
        if ($this->valido_ate) {
            $agora = new DateTime();
            $validade = $this->valido_ate instanceof DateTime
                ? $this->valido_ate
                : new DateTime($this->valido_ate);
            if ($agora > $validade) {
                return false;
            }
        }

        // Verifica se atingiu o limite de uso
        if ($this->limite_uso > 0 && $this->uso_atual >= $this->limite_uso) {
            return false;
        }

        return true;
    }

    /**
     * Calcula o desconto para um valor
     */
    public function calcularDesconto(float $valor): float
    {
        if ($this->tipo_desconto === 'percentual') {
            return round(($valor * $this->valor_desconto) / 100, 2);
        }

        // Desconto fixo
        return min($this->valor_desconto, $valor);
    }

    /**
     * Aplica o desconto ao valor
     */
    public function aplicarDesconto(float $valor): float
    {
        $desconto = $this->calcularDesconto($valor);
        return max(0, $valor - $desconto);
    }

    /**
     * Incrementa o uso do cupom (operação atômica)
     */
    public function incrementarUso(): void
    {
        static::where('id', $this->id)->increment('uso_atual');
        $this->refresh();
    }

    /**
     * Histórico de usos
     */
    public function usos()
    {
        return $this->hasMany(CupomUsado::class, 'cupom_id');
    }

    /**
     * Busca cupom por código (case insensitive)
     */
    public static function findByCodigo(string $codigo, bool $lockForUpdate = false): ?self
    {
        $query = self::whereRaw('UPPER(codigo) = ?', [strtoupper($codigo)]);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Formata o desconto para exibição
     */
    public function getDescontoFormatado(): string
    {
        if ($this->tipo_desconto === 'percentual') {
            return $this->valor_desconto . '%';
        }
        return 'R$ ' . number_format($this->valor_desconto, 2, ',', '.');
    }
}
