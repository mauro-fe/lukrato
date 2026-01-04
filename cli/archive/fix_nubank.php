<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Conta;
use Application\Models\InstituicaoFinanceira;

echo "=== Corrigir Conta NUBANK PF ===\n\n";

// Buscar Nubank na tabela de instituições
$nubank = InstituicaoFinanceira::where('codigo', 'nubank')
    ->orWhere('nome', 'LIKE', '%Nubank%')
    ->first();

if (!$nubank) {
    echo "❌ Instituição Nubank não encontrada!\n";
    exit;
}

echo "✓ Nubank encontrado (ID: {$nubank->id})\n";
echo "  Nome: {$nubank->nome}\n";
echo "  Código: {$nubank->codigo}\n";
echo "  Cor: {$nubank->cor_primaria}\n\n";

// Buscar conta NUBANK PF
$conta = Conta::where('nome', 'LIKE', '%NUBANK%')->first();

if (!$conta) {
    echo "❌ Conta NUBANK PF não encontrada!\n";
    exit;
}

echo "Conta encontrada:\n";
echo "  ID: {$conta->id}\n";
echo "  Nome: {$conta->nome}\n";
echo "  Instituição ID atual: " . ($conta->instituicao_financeira_id ?? 'NULL') . "\n\n";

// Atualizar
$conta->instituicao_financeira_id = $nubank->id;
$conta->save();

echo "✅ Conta atualizada!\n\n";

// Verificar
$conta = $conta->fresh();
$conta->load('instituicaoFinanceira');

echo "Verificação:\n";
echo "  Instituição ID: {$conta->instituicao_financeira_id}\n";
echo "  Instituição: " . ($conta->instituicaoFinanceira ? $conta->instituicaoFinanceira->nome : 'NULL') . "\n";

if ($conta->instituicaoFinanceira) {
    echo "\n✓ Tudo certo! A conta agora está vinculada ao Nubank.\n";
}
