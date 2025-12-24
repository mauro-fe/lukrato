<?php

/**
 * Script para limpar lançamentos de saldo inicial antigos
 * Agora o saldo inicial é armazenado no campo contas.saldo_inicial
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "\n=== Limpeza de Lançamentos de Saldo Inicial ===\n\n";

try {
    // Contar lançamentos de saldo inicial
    $total = Lancamento::where('eh_saldo_inicial', 1)->count();

    echo "📊 Encontrados {$total} lançamentos de saldo inicial\n";

    if ($total === 0) {
        echo "✅ Nenhum lançamento de saldo inicial para limpar!\n\n";
        exit(0);
    }

    // Buscar alguns exemplos para mostrar
    $exemplos = Lancamento::where('eh_saldo_inicial', 1)
        ->limit(5)
        ->get(['id', 'descricao', 'valor', 'tipo', 'data', 'conta_id']);

    echo "\n📋 Exemplos de lançamentos que serão removidos:\n";
    foreach ($exemplos as $lanc) {
        echo "  - ID {$lanc->id}: {$lanc->descricao} (R$ {$lanc->valor}) - {$lanc->tipo} - Conta {$lanc->conta_id}\n";
    }

    echo "\n⚠️  ATENÇÃO: Esta operação irá DELETAR permanentemente estes lançamentos!\n";
    echo "Os dados já foram migrados para o campo contas.saldo_inicial\n\n";
    echo "Digite 'SIM' para confirmar a exclusão: ";

    $handle = fopen("php://stdin", "r");
    $confirmacao = trim(fgets($handle));
    fclose($handle);

    if (strtoupper($confirmacao) !== 'SIM') {
        echo "\n❌ Operação cancelada pelo usuário.\n\n";
        exit(0);
    }

    echo "\n🗑️  Removendo lançamentos...\n";

    DB::beginTransaction();

    $deletados = Lancamento::where('eh_saldo_inicial', 1)->delete();

    DB::commit();

    echo "✅ {$deletados} lançamentos de saldo inicial removidos com sucesso!\n";
    echo "\n✓ Limpeza concluída!\n\n";
} catch (\Throwable $e) {
    DB::rollBack();
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    exit(1);
}
