<?php

/**
 * Migration: Expand Achievement Categories
 * 
 * Adiciona novas categorias ao enum de achievements
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "🔄 Expandindo categorias de conquistas...\n\n";

try {
    // Alterar a coluna category para incluir novos valores
    DB::statement("ALTER TABLE achievements MODIFY COLUMN category ENUM('streak', 'financial', 'level', 'usage', 'premium', 'cards', 'milestone', 'special') DEFAULT 'usage'");

    echo "✅ Coluna category atualizada com sucesso!\n";

    // Agora atualizar os registros que precisam das novas categorias
    $updates = [
        ['code' => 'PREMIUM_USER', 'category' => 'premium'],
        ['code' => 'FIRST_CARD', 'category' => 'cards'],
        ['code' => 'FIRST_INVOICE_PAID', 'category' => 'cards'],
        ['code' => 'INVOICES_12_PAID', 'category' => 'cards'],
        ['code' => 'ANNIVERSARY_1_YEAR', 'category' => 'milestone'],
        ['code' => 'ANNIVERSARY_2_YEARS', 'category' => 'milestone'],
        ['code' => 'EARLY_BIRD', 'category' => 'special'],
        ['code' => 'NIGHT_OWL', 'category' => 'special'],
        ['code' => 'CHRISTMAS', 'category' => 'special'],
        ['code' => 'NEW_YEAR', 'category' => 'special'],
        ['code' => 'WEEKEND_WARRIOR', 'category' => 'special'],
        ['code' => 'SPEED_DEMON', 'category' => 'special'],
    ];

    foreach ($updates as $update) {
        DB::table('achievements')
            ->where('code', $update['code'])
            ->update(['category' => $update['category']]);
        echo "📝 {$update['code']} => {$update['category']}\n";
    }

    echo "\n✅ Todas as categorias foram atualizadas!\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
