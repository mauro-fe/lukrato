<?php
/**
 * Migração: Marcar lançamentos antigos como pagos.
 * 
 * No sistema antigo, o campo `pago` não era utilizado nos cálculos.
 * Agora que saldos/dashboard/relatórios filtram por pago=1,
 * precisamos marcar todos os lançamentos com data no passado como pagos.
 * 
 * Lógica:
 * - pago=0 AND data <= hoje AND não cancelado → pago=1, data_pagamento=data
 * - Lançamentos futuros permanecem como pendentes (pago=0)
 * - Lançamentos cancelados permanecem inalterados
 * 
 * IMPORTANTE: Executar ANTES de ativar os filtros de pago nas queries.
 */

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Migração: Status de Pagamento dos Lançamentos ===\n\n";

$hoje = date('Y-m-d');

// 1. Diagnóstico
$totalLancamentos = DB::table('lancamentos')->count();
$totalPago0 = DB::table('lancamentos')->where('pago', 0)->whereNull('cancelado_em')->count();
$totalPago0Passado = DB::table('lancamentos')
    ->where('pago', 0)
    ->where('data', '<=', $hoje)
    ->whereNull('cancelado_em')
    ->count();
$totalPago0Futuro = DB::table('lancamentos')
    ->where('pago', 0)
    ->where('data', '>', $hoje)
    ->whereNull('cancelado_em')
    ->count();
$totalJaPago1 = DB::table('lancamentos')->where('pago', 1)->count();

echo "Diagnóstico:\n";
echo "  Total de lançamentos:          {$totalLancamentos}\n";
echo "  Já marcados como pago=1:       {$totalJaPago1}\n";
echo "  Marcados como pago=0 (ativos): {$totalPago0}\n";
echo "    - Com data no passado/hoje:  {$totalPago0Passado} (serão migrados)\n";
echo "    - Com data no futuro:        {$totalPago0Futuro} (permanecem pendentes)\n\n";

if ($totalPago0Passado === 0) {
    echo "✅ Nenhum lançamento para migrar. Tudo já está correto.\n";
    exit(0);
}

// 2. Confirmar execução
echo "Deseja continuar? (s/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 's') {
    echo "Operação cancelada.\n";
    exit(0);
}

// 3. Executar migração em lotes (para não travar em tabelas grandes)
echo "\nExecutando migração...\n";

$batchSize = 1000;
$totalMigrado = 0;

do {
    $affected = DB::table('lancamentos')
        ->where('pago', 0)
        ->where('data', '<=', $hoje)
        ->whereNull('cancelado_em')
        ->limit($batchSize)
        ->update([
            'pago' => 1,
            'data_pagamento' => DB::raw('data'), // usa a data do lançamento como data de pagamento
        ]);

    $totalMigrado += $affected;

    if ($affected > 0) {
        echo "  Migrados: {$totalMigrado}...\n";
    }
} while ($affected > 0);

echo "\n✅ Migração concluída!\n";
echo "  Total migrado: {$totalMigrado} lançamentos\n";

// 4. Verificação pós-migração
$restantes = DB::table('lancamentos')
    ->where('pago', 0)
    ->where('data', '<=', $hoje)
    ->whereNull('cancelado_em')
    ->count();

$pendentes = DB::table('lancamentos')
    ->where('pago', 0)
    ->where('data', '>', $hoje)
    ->whereNull('cancelado_em')
    ->count();

$pagos = DB::table('lancamentos')->where('pago', 1)->count();

echo "\nVerificação pós-migração:\n";
echo "  Lançamentos pagos (pago=1):        {$pagos}\n";
echo "  Pendentes futuros (pago=0):         {$pendentes}\n";
echo "  Não migrados (pago=0, passado):     {$restantes}\n";

if ($restantes > 0) {
    echo "\n⚠️  Ainda há {$restantes} lançamentos não migrados (provavelmente cancelados).\n";
} else {
    echo "\n✅ Todos os lançamentos do passado estão marcados como pagos.\n";
}
