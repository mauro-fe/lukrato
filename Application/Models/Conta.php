<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class Conta extends Model
{
    protected $table = 'contas';

    protected $fillable = [
        'user_id',
        'nome',
        'cor',
        'instituicao',
        'moeda',
        'tipo_id',
        'saldo_inicial',
        'ativo',
    ];

    protected $casts = [
        'user_id'       => 'int',
        'saldo_inicial' => 'float',
        'tipo_id',
        'ativo'         => 'bool',
    ];

    // Dono da conta
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // Lançamentos “normais” (receita/despesa) desta conta
    public function lancamentos()
    {
        return $this->hasMany(Lancamento::class, 'conta_id');
    }

    // Transferências recebidas
    public function transferenciasRecebidas()
    {
        return $this->hasMany(Lancamento::class, 'conta_destino_id')
            ->where('eh_transferencia', 1);
    }

    // Transferências enviadas
    public function transferenciasEnviadas()
    {
        return $this->hasMany(Lancamento::class, 'conta_id')
            ->where('eh_transferencia', 1);
    }

    // ---- SCOPES ----
    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeAtivas($q)
    {
        return $q->where('ativo', 1);
    }
    public function scopeArquivadas($q)
    {
        return $q->where('ativo', 0);
    }
}
