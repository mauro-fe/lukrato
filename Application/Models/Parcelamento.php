<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

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
        'usuario_id',
        'descricao',
        'valor_total',
        'numero_parcelas',
        'parcelas_pagas',
        'categoria_id',
        'conta_id',
        'tipo',
        'status',
        'data_criacao',
    ];

    protected $casts = [
        'usuario_id' => 'int',
        'valor_total' => 'float',
        'numero_parcelas' => 'int',
        'parcelas_pagas' => 'int',
        'categoria_id' => 'int',
        'conta_id' => 'int',
        'data_criacao' => 'date:Y-m-d',
    ];

    /**
     * Relacionamento com o usuário
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relacionamento com a categoria
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Relacionamento com a conta
     */
    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    /**
     * Relacionamento com os lançamentos (parcelas)
     */
    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class, 'parcelamento_id')
            ->orderBy('numero_parcela');
    }

    /**
     * Calcula o valor de cada parcela
     */
    public function getValorParcelaAttribute()
    {
        return $this->valor_total / $this->numero_parcelas;
    }

    /**
     * Verifica se o parcelamento está concluído
     */
    public function isCompleto()
    {
        return $this->parcelas_pagas >= $this->numero_parcelas;
    }

    /**
     * Calcula o percentual pago
     */
    public function getPercentualPagoAttribute()
    {
        if ($this->numero_parcelas == 0) {
            return 0;
        }
        return ($this->parcelas_pagas / $this->numero_parcelas) * 100;
    }

    /**
     * Retorna o valor restante a pagar
     */
    public function getValorRestanteAttribute()
    {
        $parcelasPendentes = $this->numero_parcelas - $this->parcelas_pagas;
        return $parcelasPendentes * $this->valorParcela;
    }
}
