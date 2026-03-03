<?php
/**
 * Fix: preenche data_pagamento para lançamentos que têm pago=1 mas data_pagamento IS NULL.
 * Usa a data do lançamento (campo `data`) como fallback.
 *
 * Seguro para rodar múltiplas vezes (idempotente).
 */
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Fix data_pagamento em lançamentos pagos ===\n\n";

// Contar registros afetados
$total = DB::table('lancamentos')
    ->where('pago', 1)
    ->whereNull('data_pagamento')
    ->count();

echo "Lançamentos com pago=1 e data_pagamento=NULL: {$total}\n";

if ($total === 0) {
    echo "Nenhum registro para corrigir. Tudo OK!\n";
    exit(0);
}

// Atualizar: usa `data` (data do lançamento) como data_pagamento
$updated = DB::table('lancamentos')
    ->where('pago', 1)
    ->whereNull('data_pagamento')
    ->update(['data_pagamento' => DB::raw('`data`')]);

echo "Corrigidos: {$updated} lançamentos\n";
echo "\nDone!\n";
