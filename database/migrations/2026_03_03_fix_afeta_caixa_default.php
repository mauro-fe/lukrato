<?php

/**
 * Migration: Corrigir DEFAULT de afeta_caixa e sincronizar dados existentes
 *
 * Problema: afeta_caixa tinha DEFAULT 1, fazendo com que lançamentos criados
 * sem definir esse campo explicitamente afetassem o caixa mesmo quando pago=0.
 *
 * Correções:
 * 1. Alterar DEFAULT de afeta_caixa para 0
 * 2. Converter NULLs em afeta_caixa para o valor correto baseado em pago
 * 3. Sincronizar afeta_caixa com pago para lançamentos inconsistentes
 *    (exceto cartão de crédito que tem regra própria)
 *
 * Uso: php cli/manage_migrations.php
 */

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // 1. Alterar DEFAULT de afeta_caixa para 0
        DB::statement("ALTER TABLE lancamentos ALTER COLUMN afeta_caixa SET DEFAULT 0");
        echo "  ✓ DEFAULT de afeta_caixa alterado para 0\n";

        // 2. Converter NULLs: se pago=1 → afeta_caixa=1, se pago=0 → afeta_caixa=0
        $nullsFixed = DB::table('lancamentos')
            ->whereNull('afeta_caixa')
            ->update([
                'afeta_caixa' => DB::raw('CASE WHEN pago = 1 THEN 1 ELSE 0 END')
            ]);
        echo "  ✓ {$nullsFixed} registros com afeta_caixa NULL corrigidos\n";

        // 3. Lançamentos pendentes (pago=0) que estão afetando caixa indevidamente
        //    Excluir cartões de crédito (origem_tipo = 'cartao_credito') pois têm regra própria
        $pendentesFixed = DB::table('lancamentos')
            ->where('pago', 0)
            ->where('afeta_caixa', 1)
            ->where(function ($q) {
                $q->whereNull('origem_tipo')
                    ->orWhere('origem_tipo', '!=', 'cartao_credito');
            })
            ->update(['afeta_caixa' => 0]);
        echo "  ✓ {$pendentesFixed} lançamentos pendentes tinham afeta_caixa=1 → corrigido para 0\n";

        // 4. Lançamentos pagos (pago=1) que NÃO estão afetando caixa (possível inconsistência)
        //    Excluir os que foram explicitamente marcados pelo usuário como não afetando caixa
        //    Por segurança, apenas reportar estes sem alterar
        $pagosNaoAfetam = DB::table('lancamentos')
            ->where('pago', 1)
            ->where('afeta_caixa', 0)
            ->count();
        if ($pagosNaoAfetam > 0) {
            echo "  ℹ️ {$pagosNaoAfetam} lançamentos pagos com afeta_caixa=0 (podem ter sido definidos pelo usuário)\n";
        }

        // 5. Resumo final
        $total = DB::table('lancamentos')->count();
        $afetando = DB::table('lancamentos')->where('afeta_caixa', 1)->count();
        $naoAfetando = DB::table('lancamentos')->where('afeta_caixa', 0)->count();
        echo "\n  📊 Resumo: {$total} lançamentos total | {$afetando} afetam caixa | {$naoAfetando} não afetam\n";
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lancamentos ALTER COLUMN afeta_caixa SET DEFAULT 1");
        echo "  ✓ DEFAULT de afeta_caixa revertido para 1\n";
    }
};
