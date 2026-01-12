<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration para adicionar campo status na tabela faturas
 * 
 * O campo status armazena o estado atual da fatura:
 * - pendente: Nenhuma parcela paga (progresso = 0)
 * - parcial: Algumas parcelas pagas (0 < progresso < 100)
 * - paga: Todas parcelas pagas (progresso = 100)
 * - cancelado: Fatura foi cancelada
 */
return new class {
    public function up(): void
    {
        // Adicionar campo status se não existir
        if (!Capsule::schema()->hasColumn('faturas', 'status')) {
            Capsule::schema()->table('faturas', function (Blueprint $table) {
                $table->enum('status', ['pendente', 'parcial', 'paga', 'cancelado'])
                    ->default('pendente')
                    ->after('data_compra');

                // Índice para melhorar performance dos filtros
                $table->index('status', 'idx_faturas_status');
            });
        }

        // Atualizar status das faturas existentes baseado nas parcelas
        $this->atualizarStatusFaturasExistentes();
    }

    public function down(): void
    {
        if (Capsule::schema()->hasColumn('faturas', 'status')) {
            Capsule::schema()->table('faturas', function (Blueprint $table) {
                $table->dropIndex('idx_faturas_status');
                $table->dropColumn('status');
            });
        }
    }

    /**
     * Atualiza o status de todas as faturas existentes
     */
    private function atualizarStatusFaturasExistentes(): void
    {
        $faturas = Capsule::table('faturas')->get();

        foreach ($faturas as $fatura) {
            // Buscar itens desta fatura
            $totalItens = Capsule::table('faturas_cartao_itens')
                ->where('fatura_id', $fatura->id)
                ->count();

            $itensPagos = Capsule::table('faturas_cartao_itens')
                ->where('fatura_id', $fatura->id)
                ->where('pago', 1)
                ->count();

            // Determinar status
            $status = 'pendente';
            if ($totalItens > 0) {
                if ($itensPagos === 0) {
                    $status = 'pendente';
                } elseif ($itensPagos >= $totalItens) {
                    $status = 'paga';
                } else {
                    $status = 'parcial';
                }
            }

            // Atualizar fatura
            Capsule::table('faturas')
                ->where('id', $fatura->id)
                ->update(['status' => $status]);
        }
    }
};
