<?php

/**
 * Migration: Adicionar coluna 'icone' na tabela categorias
 * 
 * - Adiciona coluna `icone` VARCHAR(50) nullable
 * - Atualiza categorias padrão existentes com ícones Lucide
 * - Remove emojis dos nomes de categorias antigas (legacy)
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        // 1. Adicionar coluna icone se não existir
        if (!DB::schema()->hasColumn('categorias', 'icone')) {
            DB::schema()->table('categorias', function (Blueprint $table) {
                $table->string('icone', 50)->nullable()->after('nome');
            });
            echo "  ✓ Coluna 'icone' adicionada à tabela categorias\n";
        } else {
            echo "  ⏭️ Coluna 'icone' já existe\n";
        }

        // 2. Mapeamento de categorias padrão → ícones Lucide
        $iconMap = [
            // Despesas
            'Moradia'            => 'house',
            'Alimentação'        => 'utensils',
            'Transporte'         => 'car',
            'Contas e Serviços'  => 'lightbulb',
            'Saúde'              => 'heart-pulse',
            'Educação'           => 'graduation-cap',
            'Vestuário'          => 'shirt',
            'Lazer'              => 'clapperboard',
            'Cartão de Crédito'  => 'credit-card',
            'Assinaturas'        => 'smartphone',
            'Compras'            => 'shopping-cart',
            'Outros Gastos'      => 'coins',
            // Receitas
            'Salário'            => 'briefcase',
            'Freelance'          => 'laptop',
            'Investimentos'      => 'trending-up',
            'Bônus'              => 'gift',
            'Vendas'             => 'banknote',
            'Prêmios'            => 'trophy',
            'Outras Receitas'    => 'wallet',
            // Legacy (com emoji) — mesmo mapeamento
            '🏠 Moradia'          => 'house',
            '🍔 Alimentação'      => 'utensils',
            '🚗 Transporte'       => 'car',
            '💡 Contas e Serviços' => 'lightbulb',
            '🏥 Saúde'            => 'heart-pulse',
            '🎓 Educação'         => 'graduation-cap',
            '👕 Vestuário'        => 'shirt',
            '🎬 Lazer'            => 'clapperboard',
            '💳 Cartão de Crédito' => 'credit-card',
            '📱 Assinaturas'      => 'smartphone',
            '🛒 Compras'          => 'shopping-cart',
            '💰 Outros Gastos'    => 'coins',
            '💼 Salário'          => 'briefcase',
            '💰 Freelance'        => 'laptop',
            '📈 Investimentos'    => 'trending-up',
            '🎁 Bônus'            => 'gift',
            '💸 Vendas'           => 'banknote',
            '🏆 Prêmios'          => 'trophy',
            '💵 Outras Receitas'  => 'wallet',
        ];

        // 3. Atualizar ícones nas categorias existentes
        $updated = 0;
        foreach ($iconMap as $nome => $icone) {
            $affected = DB::table('categorias')
                ->where('nome', $nome)
                ->whereNull('icone')
                ->update(['icone' => $icone]);
            $updated += $affected;
        }
        echo "  ✓ {$updated} categorias atualizadas com ícones Lucide\n";

        // 4. Limpar emojis dos nomes (legacy: "🏠 Moradia" → "Moradia")
        $emojiToClean = [
            '🏠 Moradia'           => 'Moradia',
            '🍔 Alimentação'       => 'Alimentação',
            '🚗 Transporte'        => 'Transporte',
            '💡 Contas e Serviços'  => 'Contas e Serviços',
            '🏥 Saúde'             => 'Saúde',
            '🎓 Educação'          => 'Educação',
            '👕 Vestuário'         => 'Vestuário',
            '🎬 Lazer'             => 'Lazer',
            '💳 Cartão de Crédito'  => 'Cartão de Crédito',
            '📱 Assinaturas'       => 'Assinaturas',
            '🛒 Compras'           => 'Compras',
            '💰 Outros Gastos'     => 'Outros Gastos',
            '💼 Salário'           => 'Salário',
            '💰 Freelance'         => 'Freelance',
            '📈 Investimentos'     => 'Investimentos',
            '🎁 Bônus'             => 'Bônus',
            '💸 Vendas'            => 'Vendas',
            '🏆 Prêmios'           => 'Prêmios',
            '💵 Outras Receitas'   => 'Outras Receitas',
        ];

        $cleaned = 0;
        foreach ($emojiToClean as $oldName => $newName) {
            $affected = DB::table('categorias')
                ->where('nome', $oldName)
                ->update(['nome' => $newName]);
            $cleaned += $affected;
        }
        echo "  ✓ {$cleaned} nomes de categorias limpos (emojis removidos)\n";

        // 5. Categorias personalizadas que não têm icone recebem 'tag' como padrão
        $defaulted = DB::table('categorias')
            ->whereNull('icone')
            ->update(['icone' => 'tag']);
        echo "  ✓ {$defaulted} categorias personalizadas receberam ícone padrão 'tag'\n";
    }
};
