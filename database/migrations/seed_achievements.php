<?php

/**
 * Seed: Popular tabela de conquistas
 * Data: 2026-01-04
 * 
 * Popula a tabela achievements com todas as conquistas Free e Pro
 */

require_once __DIR__ . '/../../bootstrap.php';

use Application\Models\Achievement;
use Application\Enums\AchievementType;

// Limpar conquistas antigas (opcional)
$clearOld = readline("Deseja limpar conquistas antigas? (s/n): ");
if (strtolower(trim($clearOld)) === 's') {
    Achievement::query()->delete();
    echo "✓ Conquistas antigas removidas\n\n";
}

$achievements = [
    // ========== CONQUISTAS GRATUITAS ==========
    [
        'code' => AchievementType::FIRST_LAUNCH->value,
        'name' => AchievementType::FIRST_LAUNCH->displayName(),
        'description' => AchievementType::FIRST_LAUNCH->description(),
        'icon' => AchievementType::FIRST_LAUNCH->icon(),
        'points_reward' => AchievementType::FIRST_LAUNCH->pointsReward(),
        'category' => AchievementType::FIRST_LAUNCH->category(),
        'plan_type' => 'free',
        'sort_order' => 1,
        'active' => true,
    ],
    [
        'code' => AchievementType::STREAK_3->value,
        'name' => AchievementType::STREAK_3->displayName(),
        'description' => AchievementType::STREAK_3->description(),
        'icon' => AchievementType::STREAK_3->icon(),
        'points_reward' => AchievementType::STREAK_3->pointsReward(),
        'category' => AchievementType::STREAK_3->category(),
        'plan_type' => 'free',
        'sort_order' => 2,
        'active' => true,
    ],
    [
        'code' => AchievementType::STREAK_7->value,
        'name' => AchievementType::STREAK_7->displayName(),
        'description' => AchievementType::STREAK_7->description(),
        'icon' => AchievementType::STREAK_7->icon(),
        'points_reward' => AchievementType::STREAK_7->pointsReward(),
        'category' => AchievementType::STREAK_7->category(),
        'plan_type' => 'free',
        'sort_order' => 3,
        'active' => true,
    ],
    [
        'code' => AchievementType::DAYS_30_USING->value,
        'name' => AchievementType::DAYS_30_USING->displayName(),
        'description' => AchievementType::DAYS_30_USING->description(),
        'icon' => AchievementType::DAYS_30_USING->icon(),
        'points_reward' => AchievementType::DAYS_30_USING->pointsReward(),
        'category' => AchievementType::DAYS_30_USING->category(),
        'plan_type' => 'free',
        'sort_order' => 4,
        'active' => true,
    ],
    [
        'code' => AchievementType::FIRST_META->value,
        'name' => AchievementType::FIRST_META->displayName(),
        'description' => AchievementType::FIRST_META->description(),
        'icon' => AchievementType::FIRST_META->icon(),
        'points_reward' => AchievementType::FIRST_META->pointsReward(),
        'category' => AchievementType::FIRST_META->category(),
        'plan_type' => 'free',
        'sort_order' => 5,
        'active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_10_LAUNCHES->value,
        'name' => AchievementType::TOTAL_10_LAUNCHES->displayName(),
        'description' => AchievementType::TOTAL_10_LAUNCHES->description(),
        'icon' => AchievementType::TOTAL_10_LAUNCHES->icon(),
        'points_reward' => AchievementType::TOTAL_10_LAUNCHES->pointsReward(),
        'category' => AchievementType::TOTAL_10_LAUNCHES->category(),
        'plan_type' => 'free',
        'sort_order' => 6,
        'active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_5_CATEGORIES->value,
        'name' => AchievementType::TOTAL_5_CATEGORIES->displayName(),
        'description' => AchievementType::TOTAL_5_CATEGORIES->description(),
        'icon' => AchievementType::TOTAL_5_CATEGORIES->icon(),
        'points_reward' => AchievementType::TOTAL_5_CATEGORIES->pointsReward(),
        'category' => AchievementType::TOTAL_5_CATEGORIES->category(),
        'plan_type' => 'free',
        'sort_order' => 7,
        'active' => true,
    ],

    // ========== CONQUISTAS PRO ==========
    [
        'code' => AchievementType::PREMIUM_USER->value,
        'name' => AchievementType::PREMIUM_USER->displayName(),
        'description' => AchievementType::PREMIUM_USER->description(),
        'icon' => AchievementType::PREMIUM_USER->icon(),
        'points_reward' => AchievementType::PREMIUM_USER->pointsReward(),
        'category' => AchievementType::PREMIUM_USER->category(),
        'plan_type' => 'pro',
        'sort_order' => 20,
        'active' => true,
    ],
    [
        'code' => AchievementType::MASTER_ORGANIZATION->value,
        'name' => AchievementType::MASTER_ORGANIZATION->displayName(),
        'description' => AchievementType::MASTER_ORGANIZATION->description(),
        'icon' => AchievementType::MASTER_ORGANIZATION->icon(),
        'points_reward' => AchievementType::MASTER_ORGANIZATION->pointsReward(),
        'category' => AchievementType::MASTER_ORGANIZATION->category(),
        'plan_type' => 'pro',
        'sort_order' => 21,
        'active' => true,
    ],
    [
        'code' => AchievementType::ECONOMIST_MASTER->value,
        'name' => AchievementType::ECONOMIST_MASTER->displayName(),
        'description' => AchievementType::ECONOMIST_MASTER->description(),
        'icon' => AchievementType::ECONOMIST_MASTER->icon(),
        'points_reward' => AchievementType::ECONOMIST_MASTER->pointsReward(),
        'category' => AchievementType::ECONOMIST_MASTER->category(),
        'plan_type' => 'pro',
        'sort_order' => 22,
        'active' => true,
    ],
    [
        'code' => AchievementType::CONSISTENCY_TOTAL->value,
        'name' => AchievementType::CONSISTENCY_TOTAL->displayName(),
        'description' => AchievementType::CONSISTENCY_TOTAL->description(),
        'icon' => AchievementType::CONSISTENCY_TOTAL->icon(),
        'points_reward' => AchievementType::CONSISTENCY_TOTAL->pointsReward(),
        'category' => AchievementType::CONSISTENCY_TOTAL->category(),
        'plan_type' => 'pro',
        'sort_order' => 23,
        'active' => true,
    ],
    [
        'code' => AchievementType::META_ACHIEVED->value,
        'name' => AchievementType::META_ACHIEVED->displayName(),
        'description' => AchievementType::META_ACHIEVED->description(),
        'icon' => AchievementType::META_ACHIEVED->icon(),
        'points_reward' => AchievementType::META_ACHIEVED->pointsReward(),
        'category' => AchievementType::META_ACHIEVED->category(),
        'plan_type' => 'pro',
        'sort_order' => 24,
        'active' => true,
    ],
    [
        'code' => AchievementType::LEVEL_8->value,
        'name' => AchievementType::LEVEL_8->displayName(),
        'description' => AchievementType::LEVEL_8->description(),
        'icon' => AchievementType::LEVEL_8->icon(),
        'points_reward' => AchievementType::LEVEL_8->pointsReward(),
        'category' => AchievementType::LEVEL_8->category(),
        'plan_type' => 'pro',
        'sort_order' => 25,
        'active' => true,
    ],

    // ========== CONQUISTAS COMUNS ==========
    [
        'code' => AchievementType::POSITIVE_MONTH->value,
        'name' => AchievementType::POSITIVE_MONTH->displayName(),
        'description' => AchievementType::POSITIVE_MONTH->description(),
        'icon' => AchievementType::POSITIVE_MONTH->icon(),
        'points_reward' => AchievementType::POSITIVE_MONTH->pointsReward(),
        'category' => AchievementType::POSITIVE_MONTH->category(),
        'plan_type' => 'all',
        'sort_order' => 10,
        'active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_100_LAUNCHES->value,
        'name' => AchievementType::TOTAL_100_LAUNCHES->displayName(),
        'description' => AchievementType::TOTAL_100_LAUNCHES->description(),
        'icon' => AchievementType::TOTAL_100_LAUNCHES->icon(),
        'points_reward' => AchievementType::TOTAL_100_LAUNCHES->pointsReward(),
        'category' => AchievementType::TOTAL_100_LAUNCHES->category(),
        'plan_type' => 'all',
        'sort_order' => 11,
        'active' => true,
    ],
    [
        'code' => AchievementType::LEVEL_5->value,
        'name' => AchievementType::LEVEL_5->displayName(),
        'description' => AchievementType::LEVEL_5->description(),
        'icon' => AchievementType::LEVEL_5->icon(),
        'points_reward' => AchievementType::LEVEL_5->pointsReward(),
        'category' => AchievementType::LEVEL_5->category(),
        'plan_type' => 'all',
        'sort_order' => 12,
        'active' => true,
    ],
];

echo "Inserindo conquistas...\n\n";

foreach ($achievements as $data) {
    $existing = Achievement::where('code', $data['code'])->first();

    if ($existing) {
        $existing->update($data);
        echo "✓ Conquista atualizada: {$data['name']}\n";
    } else {
        Achievement::create($data);
        echo "✓ Conquista criada: {$data['name']}\n";
    }
}

$total = Achievement::count();
echo "\n✅ Seed concluído! Total de conquistas: {$total}\n";
