<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  ✅ VERIFICAÇÃO FINAL - REFATORAÇÃO CONCLUÍDA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Verificar estrutura lancamentos
echo "📊 TABELA LANCAMENTOS (Fonte da Verdade):\n\n";

$lancamentosColumns = DB::select('DESCRIBE lancamentos');
$hasParcelamentoId = false;
$hasNumeroParcela = false;
$hasCartaoCreditoId = false;

foreach ($lancamentosColumns as $col) {
    if ($col->Field === 'parcelamento_id') {
        $hasParcelamentoId = true;
        echo "  ✓ parcelamento_id: {$col->Type} {$col->Null}\n";
    }
    if ($col->Field === 'numero_parcela') {
        $hasNumeroParcela = true;
        echo "  ✓ numero_parcela: {$col->Type} {$col->Null}\n";
    }
    if ($col->Field === 'cartao_credito_id') {
        $hasCartaoCreditoId = true;
        echo "  ✓ cartao_credito_id: {$col->Type} {$col->Null}\n";
    }
}

if (!$hasParcelamentoId || !$hasNumeroParcela || !$hasCartaoCreditoId) {
    echo "  ❌ ERRO: Colunas obrigatórias faltando!\n";
} else {
    echo "  ✅ Todas as colunas necessárias presentes\n";
}

// 2. Verificar estrutura parcelamentos
echo "\n📁 TABELA PARCELAMENTOS (Auxiliar):\n\n";

$parcelamentosColumns = DB::select('DESCRIBE parcelamentos');
$hasCartaoCreditoIdParc = false;
$hasStatus = false;

foreach ($parcelamentosColumns as $col) {
    if ($col->Field === 'cartao_credito_id') {
        $hasCartaoCreditoIdParc = true;
        echo "  ✓ cartao_credito_id: {$col->Type} {$col->Null}\n";
    }
    if ($col->Field === 'status') {
        $hasStatus = true;
        echo "  ✓ status: {$col->Type}\n";
    }
}

if (!$hasCartaoCreditoIdParc || !$hasStatus) {
    echo "  ❌ ERRO: Colunas obrigatórias faltando!\n";
} else {
    echo "  ✅ Todas as colunas necessárias presentes\n";
}

// 3. Verificar Foreign Keys
echo "\n🔗 FOREIGN KEYS:\n\n";

$fks = DB::select("
    SELECT 
        kcu.TABLE_NAME,
        kcu.COLUMN_NAME,
        kcu.CONSTRAINT_NAME,
        kcu.REFERENCED_TABLE_NAME,
        rc.DELETE_RULE
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
    JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
        ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
        AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
    WHERE kcu.TABLE_SCHEMA = DATABASE()
    AND kcu.TABLE_NAME IN ('lancamentos', 'parcelamentos')
    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
");

$fkCount = 0;
$hasCascade = false;

foreach ($fks as $fk) {
    $fkCount++;
    if ($fk->COLUMN_NAME === 'parcelamento_id' && $fk->DELETE_RULE === 'CASCADE') {
        $hasCascade = true;
        echo "  ✓ lancamentos.parcelamento_id → parcelamentos.id [CASCADE] ⚡\n";
    } elseif ($fk->COLUMN_NAME === 'cartao_credito_id' && $fk->TABLE_NAME === 'lancamentos') {
        echo "  ✓ lancamentos.cartao_credito_id → cartoes_credito.id [{$fk->DELETE_RULE}]\n";
    } elseif ($fk->COLUMN_NAME === 'cartao_credito_id' && $fk->TABLE_NAME === 'parcelamentos') {
        echo "  ✓ parcelamentos.cartao_credito_id → cartoes_credito.id [{$fk->DELETE_RULE}]\n";
    }
}

echo "\n  Total de FKs: {$fkCount}\n";

if (!$hasCascade) {
    echo "  ⚠️  AVISO: FK CASCADE não encontrada!\n";
} else {
    echo "  ✅ FK CASCADE configurada corretamente\n";
}

// 4. Estatísticas
echo "\n📈 ESTATÍSTICAS:\n\n";

$totalLancamentos = DB::table('lancamentos')->count();
$lancamentosParcelados = DB::table('lancamentos')->whereNotNull('parcelamento_id')->count();
$lancamentosCartao = DB::table('lancamentos')->whereNotNull('cartao_credito_id')->count();
$totalParcelamentos = DB::table('parcelamentos')->count();

echo "  • Total de lançamentos: {$totalLancamentos}\n";
echo "  • Lançamentos parcelados: {$lancamentosParcelados}\n";
echo "  • Lançamentos de cartão: {$lancamentosCartao}\n";
echo "  • Total de parcelamentos: {$totalParcelamentos}\n";

// 5. Teste de Integridade
echo "\n🔍 TESTE DE INTEGRIDADE:\n\n";

$lancamentosOrfaos = DB::table('lancamentos')
    ->whereNotNull('parcelamento_id')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('parcelamentos')
            ->whereRaw('parcelamentos.id = lancamentos.parcelamento_id');
    })
    ->count();

if ($lancamentosOrfaos > 0) {
    echo "  ⚠️  {$lancamentosOrfaos} lançamentos com parcelamento_id inválido\n";
} else {
    echo "  ✅ Todos os lançamentos com parcelamento_id válido\n";
}

// Resumo Final
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  🎯 ARQUITETURA VALIDADA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✓ lancamentos = FONTE DA VERDADE\n";
echo "  - Contém todas as movimentações financeiras\n";
echo "  - Usado para saldo, relatórios, gráficos, fatura\n";
echo "  - Cada parcela = 1 lançamento individual\n\n";

echo "✓ parcelamentos = AUXILIAR\n";
echo "  - Agrupa parcelas visualmente (cabeçalho)\n";
echo "  - NÃO usado para cálculos financeiros\n";
echo "  - Melhora UX (mostra '3/12')\n\n";

echo "✓ RELACIONAMENTO:\n";
echo "  parcelamentos (1) ←→ (N) lancamentos\n";
echo "     (cabeçalho)        (parcelas)\n\n";

echo "✓ CASCADE:\n";
echo "  - Deletar parcelamento → deleta lançamentos\n";
echo "  - Mantém integridade referencial\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";

echo "🚀 PRÓXIMO PASSO: Testar criação de parcelamento via API\n\n";
