#!/usr/bin/env php
<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\UserProgress;

echo "🔍 Todos os registros de UserProgress:\n";
echo str_repeat("=", 60) . "\n\n";

$allProgress = UserProgress::all();

foreach ($allProgress as $progress) {
    echo "User ID: {$progress->user_id}\n";
    echo "  Pontos: {$progress->total_points}\n";
    echo "  Nível: {$progress->current_level}\n";
    echo "  Pontos para próximo: {$progress->points_to_next_level}\n";
    echo "  Created: {$progress->created_at}\n";
    echo "\n";
}
