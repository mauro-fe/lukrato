<?php

/**
 * Corrige a categoria das conquistas de indicação
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Corrigindo conquistas de indicação ===\n\n";

// 1. Primeiro, alterar o ENUM para incluir 'social'
echo "Alterando ENUM da coluna category...\n";
DB::statement("ALTER TABLE achievements MODIFY COLUMN category ENUM('streak','financial','level','usage','premium','cards','milestone','special','social') DEFAULT 'usage'");
echo "✅ ENUM alterado!\n\n";

// 2. Verificar conquistas existentes
$codes = ['FIRST_REFERRAL', 'REFERRALS_5', 'REFERRALS_10', 'REFERRALS_25'];
$achievements = DB::table('achievements')
    ->whereIn('code', $codes)
    ->get();

echo "Conquistas encontradas: " . count($achievements) . "\n\n";

foreach ($achievements as $ach) {
    echo "- {$ach->code}: {$ach->name} (categoria atual: '{$ach->category}')\n";
}

// Atualizar categoria para 'social' usando query raw
foreach ($codes as $code) {
    DB::statement("UPDATE achievements SET category = 'social' WHERE code = ?", [$code]);
    echo "Atualizando {$code}...\n";
}

echo "\n✅ Concluído!\n\n";

// Verificar resultado
$achievements = DB::table('achievements')
    ->whereIn('code', $codes)
    ->get();

echo "=== Resultado final ===\n";
foreach ($achievements as $ach) {
    echo "- {$ach->code}: categoria = '{$ach->category}'\n";
}
