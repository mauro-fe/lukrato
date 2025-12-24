<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        // 1. Adicionar campo saldo_inicial na tabela contas
        Capsule::schema()->table('contas', function ($table) {
            $table->decimal('saldo_inicial', 15, 2)->default(0)->after('tipo_conta');
        });

        // 2. Migrar dados dos lançamentos de saldo inicial para o campo
        $lancamentosSaldoInicial = Capsule::table('lancamentos')
            ->where('eh_saldo_inicial', 1)
            ->get();

        foreach ($lancamentosSaldoInicial as $lancamento) {
            // Calcular valor com sinal correto
            $valor = $lancamento->tipo === 'receita'
                ? $lancamento->valor
                : -$lancamento->valor;

            // Atualizar conta com saldo inicial
            Capsule::table('contas')
                ->where('id', $lancamento->conta_id)
                ->update(['saldo_inicial' => $valor]);

            // Marcar lançamento para deleção (será removido depois)
            // Mantemos por enquanto para rollback
        }

        echo "✅ Migrados " . count($lancamentosSaldoInicial) . " saldos iniciais para o campo saldo_inicial\n";
    }

    public function down(): void
    {
        // Recriar lançamentos de saldo inicial antes de remover o campo
        $contas = Capsule::table('contas')
            ->where('saldo_inicial', '!=', 0)
            ->get();

        foreach ($contas as $conta) {
            $isReceita = $conta->saldo_inicial >= 0;

            Capsule::table('lancamentos')->insert([
                'user_id' => $conta->user_id,
                'tipo' => $isReceita ? 'receita' : 'despesa',
                'data' => $conta->created_at ?? date('Y-m-d'),
                'categoria_id' => null,
                'conta_id' => $conta->id,
                'conta_id_destino' => null,
                'descricao' => 'Saldo inicial da conta ' . $conta->nome,
                'observacao' => null,
                'valor' => abs($conta->saldo_inicial),
                'eh_transferencia' => 0,
                'eh_saldo_inicial' => 1,
                'created_at' => $conta->created_at ?? date('Y-m-d H:i:s'),
                'updated_at' => $conta->updated_at ?? date('Y-m-d H:i:s'),
            ]);
        }

        // Remover campo
        Capsule::schema()->table('contas', function ($table) {
            $table->dropColumn('saldo_inicial');
        });
    }
};
