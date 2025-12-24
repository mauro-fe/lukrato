<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
require_once dirname(__DIR__) . '/config/config.php';

use Application\Models\Achievement;
use Application\Enums\AchievementType;

echo "=== SEED: CONQUISTAS DE GAMIFICAÇÃO ===\n\n";

$achievements = [];

// Criar achievements baseados no enum
foreach (AchievementType::cases() as $type) {
    $achievements[] = [
        'code' => $type->value,
        'name' => $type->displayName(),
        'description' => $type->description(),
        'icon' => $type->icon(),
        'points_reward' => $type->pointsReward(),
        'category' => $type->category(),
        'active' => true,
    ];
}

$created = 0;
$skipped = 0;

foreach ($achievements as $data) {
    $exists = Achievement::where('code', $data['code'])->exists();

    if ($exists) {
        echo "⏭️  {$data['name']} - já existe\n";
        $skipped++;
    } else {
        Achievement::create($data);
        echo "✅ {$data['name']} - criada (+{$data['points_reward']} pontos)\n";
        $created++;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "✅ Seed concluído!\n";
echo "   Criadas: {$created}\n";
echo "   Puladas: {$skipped}\n";
echo "   Total: " . Achievement::count() . " conquistas no sistema\n";
