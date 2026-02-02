<?php

require __DIR__ . '/../bootstrap.php';

echo "Executando migration: add_forma_pagamento_lancamentos\n\n";

$migration = require __DIR__ . '/../database/migrations/2026_02_01_add_forma_pagamento_lancamentos.php';
$migration->up();

echo "\n✅ Pronto!\n";
