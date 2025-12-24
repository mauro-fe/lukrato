<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

class InstituicaoFinanceira extends Model
{
    protected $table = 'instituicoes_financeiras';

    protected $fillable = [
        'nome',
        'codigo',
        'tipo',
        'cor_primaria',
        'cor_secundaria',
        'logo_path',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'bool',
    ];

    protected $appends = ['logo_url'];

    /**
     * Contas que usam esta instituição
     */
    public function contas()
    {
        return $this->hasMany(Conta::class, 'instituicao_financeira_id');
    }

    /**
     * Scope para apenas instituições ativas
     */
    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope por tipo de instituição
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Retorna URL completa do logo
     */
    public function getLogoUrlAttribute(): string
    {
        if (!$this->logo_path) {
            return (defined('BASE_URL') ? BASE_URL : '/') . 'assets/img/banks/default.svg';
        }

        $publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : __DIR__ . '/../../public';
        $fullPath = $publicPath . '/' . ltrim($this->logo_path, '/');
        
        if (file_exists($fullPath)) {
            return (defined('BASE_URL') ? BASE_URL : '/') . ltrim($this->logo_path, '/');
        }
        
        // Fallback para logo padrão
        return (defined('BASE_URL') ? BASE_URL : '/') . 'assets/img/banks/default.svg';
    }

    /**
     * Retorna estilo CSS com as cores da instituição
     */
    public function getEstiloCoresAttribute(): string
    {
        return "background: {$this->cor_primaria}; color: {$this->cor_secundaria};";
    }
}
