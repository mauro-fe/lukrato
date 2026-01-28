<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class CupomUsado extends Model
{
    protected $table = 'cupons_usados';
    
    public $timestamps = false;
    
    protected $fillable = [
        'cupom_id',
        'usuario_id',
        'assinatura_id',
        'desconto_aplicado',
        'valor_original',
        'valor_final',
        'usado_em'
    ];

    protected $casts = [
        'desconto_aplicado' => 'decimal:2',
        'valor_original' => 'decimal:2',
        'valor_final' => 'decimal:2',
        'usado_em' => 'datetime'
    ];

    /**
     * Relacionamento com cupom
     */
    public function cupom()
    {
        return $this->belongsTo(Cupom::class, 'cupom_id');
    }

    /**
     * Relacionamento com usuário
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relacionamento com assinatura
     */
    public function assinatura()
    {
        return $this->belongsTo(AssinaturaUsuario::class, 'assinatura_id');
    }
}
