<?php

use Illuminate\Database\Capsule\Manager as DB;

return new class
{
    public function up(): void
    {
        echo "🔄 Adicionando colunas mes_referencia e ano_referencia...\n";

        DB::schema()->table('faturas_cartao_itens', function ($table) {
            $table->integer('mes_referencia')->nullable()->after('data_vencimento');
            $table->integer('ano_referencia')->nullable()->after('mes_referencia');
        });

        echo "✅ Colunas adicionadas!\n";

        // Preencher com base na data_vencimento
        echo "🔄 Preenchendo valores com base na data_vencimento...\n";

        $itens = DB::table('faturas_cartao_itens')->get();
        foreach ($itens as $item) {
            if ($item->data_vencimento) {
                $data = new DateTime($item->data_vencimento);
                DB::table('faturas_cartao_itens')
                    ->where('id', $item->id)
                    ->update([
                        'mes_referencia' => (int)$data->format('m'),
                        'ano_referencia' => (int)$data->format('Y'),
                    ]);
            }
        }

        echo "✅ Valores preenchidos para {$itens->count()} itens!\n";
    }

    public function down(): void
    {
        echo "🔄 Removendo colunas mes_referencia e ano_referencia...\n";

        DB::schema()->table('faturas_cartao_itens', function ($table) {
            $table->dropColumn(['mes_referencia', 'ano_referencia']);
        });

        echo "✅ Colunas removidas!\n";
    }
};
