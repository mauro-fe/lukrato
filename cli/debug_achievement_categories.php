<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Services\AchievementService;

$achService = new AchievementService();
$achs = $achService->getUserAchievements(1);

echo "Conquistas e suas categorias:\n";
foreach ($achs as $a) {
    $status = $a['unlocked'] ? '✅' : '⬜';
    echo "{$status} {$a['code']} => {$a['category']}\n";
}
