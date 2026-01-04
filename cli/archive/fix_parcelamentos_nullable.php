<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== TORNANDO conta_id NULLABLE EM parcelamentos ===\n\n";

try {
    DB::statement('ALTER TABLE parcelamentos MODIFY COLUMN conta_id INT(10) UNSIGNED NULL');
    echo "✅ Coluna conta_id agora é NULLABLE!\n";

    DB::statement('ALTER TABLE parcelamentos MODIFY COLUMN categoria_id INT(10) UNSIGNED NULL');
    echo "✅ Coluna categoria_id agora é NULLABLE!\n";
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
