<?php
/**
 * Script para executar a migration de parcelamento
 */

require_once __DIR__ . '/../bootstrap.php';

echo "Executando migration: add_parcelamento_to_agendamentos\n\n";

$migration = require __DIR__ . '/../database/migrations/2026_02_05_add_parcelamento_to_agendamentos.php';
$migration->up();
