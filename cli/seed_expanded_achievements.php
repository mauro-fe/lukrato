<?php

/**
 * Seed: Expanded Achievements
 * 
 * Popula o banco de dados com todas as conquistas expandidas
 * Inclui novas categorias: Lançamentos, Dias Ativos, Economia, Organização, Cartões, Tempo de Uso, Níveis, Especiais
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Achievement;
use Application\Enums\AchievementType;

echo "🎮 ======================================\n";
echo "   LUKRATO - SEED EXPANDED ACHIEVEMENTS\n";
echo "======================================\n\n";

$achievements = [
    // ========== CONQUISTAS GRATUITAS ==========
    [
        'code' => AchievementType::FIRST_LAUNCH->value,
        'name' => AchievementType::FIRST_LAUNCH->displayName(),
        'description' => AchievementType::FIRST_LAUNCH->description(),
        'icon' => AchievementType::FIRST_LAUNCH->icon(),
        'points_reward' => AchievementType::FIRST_LAUNCH->pointsReward(),
        'category' => AchievementType::FIRST_LAUNCH->category(),
        'plan_type' => AchievementType::FIRST_LAUNCH->planType(),
        'sort_order' => 1,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::STREAK_3->value,
        'name' => AchievementType::STREAK_3->displayName(),
        'description' => AchievementType::STREAK_3->description(),
        'icon' => AchievementType::STREAK_3->icon(),
        'points_reward' => AchievementType::STREAK_3->pointsReward(),
        'category' => AchievementType::STREAK_3->category(),
        'plan_type' => AchievementType::STREAK_3->planType(),
        'sort_order' => 2,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::STREAK_7->value,
        'name' => AchievementType::STREAK_7->displayName(),
        'description' => AchievementType::STREAK_7->description(),
        'icon' => AchievementType::STREAK_7->icon(),
        'points_reward' => AchievementType::STREAK_7->pointsReward(),
        'category' => AchievementType::STREAK_7->category(),
        'plan_type' => AchievementType::STREAK_7->planType(),
        'sort_order' => 3,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::DAYS_30_USING->value,
        'name' => AchievementType::DAYS_30_USING->displayName(),
        'description' => AchievementType::DAYS_30_USING->description(),
        'icon' => AchievementType::DAYS_30_USING->icon(),
        'points_reward' => AchievementType::DAYS_30_USING->pointsReward(),
        'category' => AchievementType::DAYS_30_USING->category(),
        'plan_type' => AchievementType::DAYS_30_USING->planType(),
        'sort_order' => 4,
        'is_active' => true,
    ],
    // [
    //     'code' => AchievementType::FIRST_META->value,
    //     'name' => AchievementType::FIRST_META->displayName(),
    //     'description' => AchievementType::FIRST_META->description(),
    //     'icon' => AchievementType::FIRST_META->icon(),
    //     'points_reward' => AchievementType::FIRST_META->pointsReward(),
    //     'category' => AchievementType::FIRST_META->category(),
    //     'plan_type' => AchievementType::FIRST_META->planType(),
    //     'sort_order' => 5,
    //     'is_active' => true,
    // ],
    [
        'code' => AchievementType::TOTAL_10_LAUNCHES->value,
        'name' => AchievementType::TOTAL_10_LAUNCHES->displayName(),
        'description' => AchievementType::TOTAL_10_LAUNCHES->description(),
        'icon' => AchievementType::TOTAL_10_LAUNCHES->icon(),
        'points_reward' => AchievementType::TOTAL_10_LAUNCHES->pointsReward(),
        'category' => AchievementType::TOTAL_10_LAUNCHES->category(),
        'plan_type' => AchievementType::TOTAL_10_LAUNCHES->planType(),
        'sort_order' => 6,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_5_CATEGORIES->value,
        'name' => AchievementType::TOTAL_5_CATEGORIES->displayName(),
        'description' => AchievementType::TOTAL_5_CATEGORIES->description(),
        'icon' => AchievementType::TOTAL_5_CATEGORIES->icon(),
        'points_reward' => AchievementType::TOTAL_5_CATEGORIES->pointsReward(),
        'category' => AchievementType::TOTAL_5_CATEGORIES->category(),
        'plan_type' => AchievementType::TOTAL_5_CATEGORIES->planType(),
        'sort_order' => 7,
        'is_active' => true,
    ],

    // ========== CONQUISTAS PRO ==========
    [
        'code' => AchievementType::MASTER_ORGANIZATION->value,
        'name' => AchievementType::MASTER_ORGANIZATION->displayName(),
        'description' => AchievementType::MASTER_ORGANIZATION->description(),
        'icon' => AchievementType::MASTER_ORGANIZATION->icon(),
        'points_reward' => AchievementType::MASTER_ORGANIZATION->pointsReward(),
        'category' => AchievementType::MASTER_ORGANIZATION->category(),
        'plan_type' => AchievementType::MASTER_ORGANIZATION->planType(),
        'sort_order' => 8,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::ECONOMIST_MASTER->value,
        'name' => AchievementType::ECONOMIST_MASTER->displayName(),
        'description' => AchievementType::ECONOMIST_MASTER->description(),
        'icon' => AchievementType::ECONOMIST_MASTER->icon(),
        'points_reward' => AchievementType::ECONOMIST_MASTER->pointsReward(),
        'category' => AchievementType::ECONOMIST_MASTER->category(),
        'plan_type' => AchievementType::ECONOMIST_MASTER->planType(),
        'sort_order' => 9,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::CONSISTENCY_TOTAL->value,
        'name' => AchievementType::CONSISTENCY_TOTAL->displayName(),
        'description' => AchievementType::CONSISTENCY_TOTAL->description(),
        'icon' => AchievementType::CONSISTENCY_TOTAL->icon(),
        'points_reward' => AchievementType::CONSISTENCY_TOTAL->pointsReward(),
        'category' => AchievementType::CONSISTENCY_TOTAL->category(),
        'plan_type' => AchievementType::CONSISTENCY_TOTAL->planType(),
        'sort_order' => 10,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::META_ACHIEVED->value,
        'name' => AchievementType::META_ACHIEVED->displayName(),
        'description' => AchievementType::META_ACHIEVED->description(),
        'icon' => AchievementType::META_ACHIEVED->icon(),
        'points_reward' => AchievementType::META_ACHIEVED->pointsReward(),
        'category' => AchievementType::META_ACHIEVED->category(),
        'plan_type' => AchievementType::META_ACHIEVED->planType(),
        'sort_order' => 11,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::PREMIUM_USER->value,
        'name' => AchievementType::PREMIUM_USER->displayName(),
        'description' => AchievementType::PREMIUM_USER->description(),
        'icon' => AchievementType::PREMIUM_USER->icon(),
        'points_reward' => AchievementType::PREMIUM_USER->pointsReward(),
        'category' => AchievementType::PREMIUM_USER->category(),
        'plan_type' => AchievementType::PREMIUM_USER->planType(),
        'sort_order' => 12,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::LEVEL_8->value,
        'name' => AchievementType::LEVEL_8->displayName(),
        'description' => AchievementType::LEVEL_8->description(),
        'icon' => AchievementType::LEVEL_8->icon(),
        'points_reward' => AchievementType::LEVEL_8->pointsReward(),
        'category' => AchievementType::LEVEL_8->category(),
        'plan_type' => AchievementType::LEVEL_8->planType(),
        'sort_order' => 13,
        'is_active' => true,
    ],

    // ========== CONQUISTAS COMUNS ==========
    [
        'code' => AchievementType::POSITIVE_MONTH->value,
        'name' => AchievementType::POSITIVE_MONTH->displayName(),
        'description' => AchievementType::POSITIVE_MONTH->description(),
        'icon' => AchievementType::POSITIVE_MONTH->icon(),
        'points_reward' => AchievementType::POSITIVE_MONTH->pointsReward(),
        'category' => AchievementType::POSITIVE_MONTH->category(),
        'plan_type' => AchievementType::POSITIVE_MONTH->planType(),
        'sort_order' => 14,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_100_LAUNCHES->value,
        'name' => AchievementType::TOTAL_100_LAUNCHES->displayName(),
        'description' => AchievementType::TOTAL_100_LAUNCHES->description(),
        'icon' => AchievementType::TOTAL_100_LAUNCHES->icon(),
        'points_reward' => AchievementType::TOTAL_100_LAUNCHES->pointsReward(),
        'category' => AchievementType::TOTAL_100_LAUNCHES->category(),
        'plan_type' => AchievementType::TOTAL_100_LAUNCHES->planType(),
        'sort_order' => 15,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::LEVEL_5->value,
        'name' => AchievementType::LEVEL_5->displayName(),
        'description' => AchievementType::LEVEL_5->description(),
        'icon' => AchievementType::LEVEL_5->icon(),
        'points_reward' => AchievementType::LEVEL_5->pointsReward(),
        'category' => AchievementType::LEVEL_5->category(),
        'plan_type' => AchievementType::LEVEL_5->planType(),
        'sort_order' => 16,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - LANÇAMENTOS ==========
    [
        'code' => AchievementType::TOTAL_250_LAUNCHES->value,
        'name' => AchievementType::TOTAL_250_LAUNCHES->displayName(),
        'description' => AchievementType::TOTAL_250_LAUNCHES->description(),
        'icon' => AchievementType::TOTAL_250_LAUNCHES->icon(),
        'points_reward' => AchievementType::TOTAL_250_LAUNCHES->pointsReward(),
        'category' => AchievementType::TOTAL_250_LAUNCHES->category(),
        'plan_type' => AchievementType::TOTAL_250_LAUNCHES->planType(),
        'sort_order' => 17,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_500_LAUNCHES->value,
        'name' => AchievementType::TOTAL_500_LAUNCHES->displayName(),
        'description' => AchievementType::TOTAL_500_LAUNCHES->description(),
        'icon' => AchievementType::TOTAL_500_LAUNCHES->icon(),
        'points_reward' => AchievementType::TOTAL_500_LAUNCHES->pointsReward(),
        'category' => AchievementType::TOTAL_500_LAUNCHES->category(),
        'plan_type' => AchievementType::TOTAL_500_LAUNCHES->planType(),
        'sort_order' => 18,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_1000_LAUNCHES->value,
        'name' => AchievementType::TOTAL_1000_LAUNCHES->displayName(),
        'description' => AchievementType::TOTAL_1000_LAUNCHES->description(),
        'icon' => AchievementType::TOTAL_1000_LAUNCHES->icon(),
        'points_reward' => AchievementType::TOTAL_1000_LAUNCHES->pointsReward(),
        'category' => AchievementType::TOTAL_1000_LAUNCHES->category(),
        'plan_type' => AchievementType::TOTAL_1000_LAUNCHES->planType(),
        'sort_order' => 19,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - DIAS ATIVOS ==========
    [
        'code' => AchievementType::DAYS_50_ACTIVE->value,
        'name' => AchievementType::DAYS_50_ACTIVE->displayName(),
        'description' => AchievementType::DAYS_50_ACTIVE->description(),
        'icon' => AchievementType::DAYS_50_ACTIVE->icon(),
        'points_reward' => AchievementType::DAYS_50_ACTIVE->pointsReward(),
        'category' => AchievementType::DAYS_50_ACTIVE->category(),
        'plan_type' => AchievementType::DAYS_50_ACTIVE->planType(),
        'sort_order' => 20,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::DAYS_100_ACTIVE->value,
        'name' => AchievementType::DAYS_100_ACTIVE->displayName(),
        'description' => AchievementType::DAYS_100_ACTIVE->description(),
        'icon' => AchievementType::DAYS_100_ACTIVE->icon(),
        'points_reward' => AchievementType::DAYS_100_ACTIVE->pointsReward(),
        'category' => AchievementType::DAYS_100_ACTIVE->category(),
        'plan_type' => AchievementType::DAYS_100_ACTIVE->planType(),
        'sort_order' => 21,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::DAYS_365_ACTIVE->value,
        'name' => AchievementType::DAYS_365_ACTIVE->displayName(),
        'description' => AchievementType::DAYS_365_ACTIVE->description(),
        'icon' => AchievementType::DAYS_365_ACTIVE->icon(),
        'points_reward' => AchievementType::DAYS_365_ACTIVE->pointsReward(),
        'category' => AchievementType::DAYS_365_ACTIVE->category(),
        'plan_type' => AchievementType::DAYS_365_ACTIVE->planType(),
        'sort_order' => 22,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - ECONOMIA ==========
    [
        'code' => AchievementType::SAVER_10->value,
        'name' => AchievementType::SAVER_10->displayName(),
        'description' => AchievementType::SAVER_10->description(),
        'icon' => AchievementType::SAVER_10->icon(),
        'points_reward' => AchievementType::SAVER_10->pointsReward(),
        'category' => AchievementType::SAVER_10->category(),
        'plan_type' => AchievementType::SAVER_10->planType(),
        'sort_order' => 23,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::SAVER_20->value,
        'name' => AchievementType::SAVER_20->displayName(),
        'description' => AchievementType::SAVER_20->description(),
        'icon' => AchievementType::SAVER_20->icon(),
        'points_reward' => AchievementType::SAVER_20->pointsReward(),
        'category' => AchievementType::SAVER_20->category(),
        'plan_type' => AchievementType::SAVER_20->planType(),
        'sort_order' => 24,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::SAVER_30->value,
        'name' => AchievementType::SAVER_30->displayName(),
        'description' => AchievementType::SAVER_30->description(),
        'icon' => AchievementType::SAVER_30->icon(),
        'points_reward' => AchievementType::SAVER_30->pointsReward(),
        'category' => AchievementType::SAVER_30->category(),
        'plan_type' => AchievementType::SAVER_30->planType(),
        'sort_order' => 25,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::POSITIVE_3_MONTHS->value,
        'name' => AchievementType::POSITIVE_3_MONTHS->displayName(),
        'description' => AchievementType::POSITIVE_3_MONTHS->description(),
        'icon' => AchievementType::POSITIVE_3_MONTHS->icon(),
        'points_reward' => AchievementType::POSITIVE_3_MONTHS->pointsReward(),
        'category' => AchievementType::POSITIVE_3_MONTHS->category(),
        'plan_type' => AchievementType::POSITIVE_3_MONTHS->planType(),
        'sort_order' => 26,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::POSITIVE_6_MONTHS->value,
        'name' => AchievementType::POSITIVE_6_MONTHS->displayName(),
        'description' => AchievementType::POSITIVE_6_MONTHS->description(),
        'icon' => AchievementType::POSITIVE_6_MONTHS->icon(),
        'points_reward' => AchievementType::POSITIVE_6_MONTHS->pointsReward(),
        'category' => AchievementType::POSITIVE_6_MONTHS->category(),
        'plan_type' => AchievementType::POSITIVE_6_MONTHS->planType(),
        'sort_order' => 27,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::POSITIVE_12_MONTHS->value,
        'name' => AchievementType::POSITIVE_12_MONTHS->displayName(),
        'description' => AchievementType::POSITIVE_12_MONTHS->description(),
        'icon' => AchievementType::POSITIVE_12_MONTHS->icon(),
        'points_reward' => AchievementType::POSITIVE_12_MONTHS->pointsReward(),
        'category' => AchievementType::POSITIVE_12_MONTHS->category(),
        'plan_type' => AchievementType::POSITIVE_12_MONTHS->planType(),
        'sort_order' => 28,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - ORGANIZAÇÃO ==========
    [
        'code' => AchievementType::TOTAL_15_CATEGORIES->value,
        'name' => AchievementType::TOTAL_15_CATEGORIES->displayName(),
        'description' => AchievementType::TOTAL_15_CATEGORIES->description(),
        'icon' => AchievementType::TOTAL_15_CATEGORIES->icon(),
        'points_reward' => AchievementType::TOTAL_15_CATEGORIES->pointsReward(),
        'category' => AchievementType::TOTAL_15_CATEGORIES->category(),
        'plan_type' => AchievementType::TOTAL_15_CATEGORIES->planType(),
        'sort_order' => 29,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::TOTAL_25_CATEGORIES->value,
        'name' => AchievementType::TOTAL_25_CATEGORIES->displayName(),
        'description' => AchievementType::TOTAL_25_CATEGORIES->description(),
        'icon' => AchievementType::TOTAL_25_CATEGORIES->icon(),
        'points_reward' => AchievementType::TOTAL_25_CATEGORIES->pointsReward(),
        'category' => AchievementType::TOTAL_25_CATEGORIES->category(),
        'plan_type' => AchievementType::TOTAL_25_CATEGORIES->planType(),
        'sort_order' => 30,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::PERFECTIONIST->value,
        'name' => AchievementType::PERFECTIONIST->displayName(),
        'description' => AchievementType::PERFECTIONIST->description(),
        'icon' => AchievementType::PERFECTIONIST->icon(),
        'points_reward' => AchievementType::PERFECTIONIST->pointsReward(),
        'category' => AchievementType::PERFECTIONIST->category(),
        'plan_type' => AchievementType::PERFECTIONIST->planType(),
        'sort_order' => 31,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - CARTÕES ==========
    [
        'code' => AchievementType::FIRST_CARD->value,
        'name' => AchievementType::FIRST_CARD->displayName(),
        'description' => AchievementType::FIRST_CARD->description(),
        'icon' => AchievementType::FIRST_CARD->icon(),
        'points_reward' => AchievementType::FIRST_CARD->pointsReward(),
        'category' => AchievementType::FIRST_CARD->category(),
        'plan_type' => AchievementType::FIRST_CARD->planType(),
        'sort_order' => 32,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::FIRST_INVOICE_PAID->value,
        'name' => AchievementType::FIRST_INVOICE_PAID->displayName(),
        'description' => AchievementType::FIRST_INVOICE_PAID->description(),
        'icon' => AchievementType::FIRST_INVOICE_PAID->icon(),
        'points_reward' => AchievementType::FIRST_INVOICE_PAID->pointsReward(),
        'category' => AchievementType::FIRST_INVOICE_PAID->category(),
        'plan_type' => AchievementType::FIRST_INVOICE_PAID->planType(),
        'sort_order' => 33,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::INVOICES_12_PAID->value,
        'name' => AchievementType::INVOICES_12_PAID->displayName(),
        'description' => AchievementType::INVOICES_12_PAID->description(),
        'icon' => AchievementType::INVOICES_12_PAID->icon(),
        'points_reward' => AchievementType::INVOICES_12_PAID->pointsReward(),
        'category' => AchievementType::INVOICES_12_PAID->category(),
        'plan_type' => AchievementType::INVOICES_12_PAID->planType(),
        'sort_order' => 34,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - TEMPO DE USO ==========
    [
        'code' => AchievementType::ANNIVERSARY_1_YEAR->value,
        'name' => AchievementType::ANNIVERSARY_1_YEAR->displayName(),
        'description' => AchievementType::ANNIVERSARY_1_YEAR->description(),
        'icon' => AchievementType::ANNIVERSARY_1_YEAR->icon(),
        'points_reward' => AchievementType::ANNIVERSARY_1_YEAR->pointsReward(),
        'category' => AchievementType::ANNIVERSARY_1_YEAR->category(),
        'plan_type' => AchievementType::ANNIVERSARY_1_YEAR->planType(),
        'sort_order' => 35,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::ANNIVERSARY_2_YEARS->value,
        'name' => AchievementType::ANNIVERSARY_2_YEARS->displayName(),
        'description' => AchievementType::ANNIVERSARY_2_YEARS->description(),
        'icon' => AchievementType::ANNIVERSARY_2_YEARS->icon(),
        'points_reward' => AchievementType::ANNIVERSARY_2_YEARS->pointsReward(),
        'category' => AchievementType::ANNIVERSARY_2_YEARS->category(),
        'plan_type' => AchievementType::ANNIVERSARY_2_YEARS->planType(),
        'sort_order' => 36,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - NÍVEIS ==========
    [
        'code' => AchievementType::LEVEL_10->value,
        'name' => AchievementType::LEVEL_10->displayName(),
        'description' => AchievementType::LEVEL_10->description(),
        'icon' => AchievementType::LEVEL_10->icon(),
        'points_reward' => AchievementType::LEVEL_10->pointsReward(),
        'category' => AchievementType::LEVEL_10->category(),
        'plan_type' => AchievementType::LEVEL_10->planType(),
        'sort_order' => 37,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::LEVEL_12->value,
        'name' => AchievementType::LEVEL_12->displayName(),
        'description' => AchievementType::LEVEL_12->description(),
        'icon' => AchievementType::LEVEL_12->icon(),
        'points_reward' => AchievementType::LEVEL_12->pointsReward(),
        'category' => AchievementType::LEVEL_12->category(),
        'plan_type' => AchievementType::LEVEL_12->planType(),
        'sort_order' => 38,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::LEVEL_15->value,
        'name' => AchievementType::LEVEL_15->displayName(),
        'description' => AchievementType::LEVEL_15->description(),
        'icon' => AchievementType::LEVEL_15->icon(),
        'points_reward' => AchievementType::LEVEL_15->pointsReward(),
        'category' => AchievementType::LEVEL_15->category(),
        'plan_type' => AchievementType::LEVEL_15->planType(),
        'sort_order' => 39,
        'is_active' => true,
    ],

    // ========== NOVAS CONQUISTAS - ESPECIAIS ==========
    [
        'code' => AchievementType::EARLY_BIRD->value,
        'name' => AchievementType::EARLY_BIRD->displayName(),
        'description' => AchievementType::EARLY_BIRD->description(),
        'icon' => AchievementType::EARLY_BIRD->icon(),
        'points_reward' => AchievementType::EARLY_BIRD->pointsReward(),
        'category' => AchievementType::EARLY_BIRD->category(),
        'plan_type' => AchievementType::EARLY_BIRD->planType(),
        'sort_order' => 40,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::NIGHT_OWL->value,
        'name' => AchievementType::NIGHT_OWL->displayName(),
        'description' => AchievementType::NIGHT_OWL->description(),
        'icon' => AchievementType::NIGHT_OWL->icon(),
        'points_reward' => AchievementType::NIGHT_OWL->pointsReward(),
        'category' => AchievementType::NIGHT_OWL->category(),
        'plan_type' => AchievementType::NIGHT_OWL->planType(),
        'sort_order' => 41,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::CHRISTMAS->value,
        'name' => AchievementType::CHRISTMAS->displayName(),
        'description' => AchievementType::CHRISTMAS->description(),
        'icon' => AchievementType::CHRISTMAS->icon(),
        'points_reward' => AchievementType::CHRISTMAS->pointsReward(),
        'category' => AchievementType::CHRISTMAS->category(),
        'plan_type' => AchievementType::CHRISTMAS->planType(),
        'sort_order' => 42,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::NEW_YEAR->value,
        'name' => AchievementType::NEW_YEAR->displayName(),
        'description' => AchievementType::NEW_YEAR->description(),
        'icon' => AchievementType::NEW_YEAR->icon(),
        'points_reward' => AchievementType::NEW_YEAR->pointsReward(),
        'category' => AchievementType::NEW_YEAR->category(),
        'plan_type' => AchievementType::NEW_YEAR->planType(),
        'sort_order' => 43,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::WEEKEND_WARRIOR->value,
        'name' => AchievementType::WEEKEND_WARRIOR->displayName(),
        'description' => AchievementType::WEEKEND_WARRIOR->description(),
        'icon' => AchievementType::WEEKEND_WARRIOR->icon(),
        'points_reward' => AchievementType::WEEKEND_WARRIOR->pointsReward(),
        'category' => AchievementType::WEEKEND_WARRIOR->category(),
        'plan_type' => AchievementType::WEEKEND_WARRIOR->planType(),
        'sort_order' => 44,
        'is_active' => true,
    ],
    [
        'code' => AchievementType::SPEED_DEMON->value,
        'name' => AchievementType::SPEED_DEMON->displayName(),
        'description' => AchievementType::SPEED_DEMON->description(),
        'icon' => AchievementType::SPEED_DEMON->icon(),
        'points_reward' => AchievementType::SPEED_DEMON->pointsReward(),
        'category' => AchievementType::SPEED_DEMON->category(),
        'plan_type' => AchievementType::SPEED_DEMON->planType(),
        'sort_order' => 45,
        'is_active' => true,
    ],
];

$created = 0;
$updated = 0;
$errors = [];

foreach ($achievements as $data) {
    try {
        $existing = Achievement::where('code', $data['code'])->first();

        if ($existing) {
            // Atualizar existente
            $existing->update($data);
            $updated++;
            echo "📝 Atualizado: {$data['name']}\n";
        } else {
            // Criar novo
            Achievement::create($data);
            $created++;
            echo "✅ Criado: {$data['name']}\n";
        }
    } catch (Exception $e) {
        $errors[] = [
            'code' => $data['code'],
            'error' => $e->getMessage(),
        ];
        echo "❌ Erro: {$data['code']} - {$e->getMessage()}\n";
    }
}

echo "\n======================================\n";
echo "📊 RESUMO:\n";
echo "   ✅ Criados: {$created}\n";
echo "   📝 Atualizados: {$updated}\n";
echo "   ❌ Erros: " . count($errors) . "\n";
echo "======================================\n";

if (count($errors) > 0) {
    echo "\n🔴 ERROS DETALHADOS:\n";
    foreach ($errors as $error) {
        echo "   - {$error['code']}: {$error['error']}\n";
    }
}

// Exibir total de conquistas
$total = Achievement::count();
echo "\n📈 Total de conquistas no banco: {$total}\n";
echo "======================================\n";
