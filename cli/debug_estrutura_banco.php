<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Verificando estrutura das tabelas ===\n\n";

// Verificar estrutura da tabela lancamentos
echo "📋 Estrutura da tabela 'lancamentos':\n";
$columns = DB::select("SHOW COLUMNS FROM lancamentos");
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type}) {$col->Key} {$col->Null} {$col->Extra}\n";
}

echo "\n📋 Foreign Keys da tabela 'lancamentos':\n";
$fks = DB::select("
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'lancamentos'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if (empty($fks)) {
    echo "  ⚠️  Nenhuma foreign key encontrada!\n";
} else {
    foreach ($fks as $fk) {
        echo "  - {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
    }
}

echo "\n📋 Estrutura da tabela 'cartoes_credito':\n";
$columns = DB::select("SHOW COLUMNS FROM cartoes_credito");
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type}) {$col->Key}\n";
}

// Verificar relacionamento na prática
echo "\n\n=== Testando relacionamento ===\n";

use Application\Models\CartaoCredito;

$cartao = CartaoCredito::find(28);
if ($cartao) {
    echo "Cartão ID: {$cartao->id}\n";
    echo "Nome: {$cartao->nome_cartao}\n";
    echo "Arquivado: " . ($cartao->arquivado ? 'Sim' : 'Não') . "\n";

    $lancamentos = $cartao->lancamentos()->get();
    echo "Lançamentos encontrados via relacionamento: {$lancamentos->count()}\n";

    // Verificar diretamente no banco
    $totalDireto = DB::table('lancamentos')->where('cartao_credito_id', 28)->count();
    echo "Lançamentos encontrados via query direta: {$totalDireto}\n";

    if ($lancamentos->count() > 0) {
        echo "\nLançamentos:\n";
        foreach ($lancamentos as $lanc) {
            echo "  - ID {$lanc->id}: {$lanc->descricao} (R$ {$lanc->valor})\n";
        }
    }
}
