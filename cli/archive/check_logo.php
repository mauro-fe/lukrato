<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\InstituicaoFinanceira;

$inst = InstituicaoFinanceira::find(1);
echo "Logo Path: " . $inst->logo_path . "\n";
echo "Logo URL: " . $inst->logo_url . "\n";
echo "BASE_URL: " . BASE_URL . "\n";
