<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Categoria;

$userId = 23;

// Categorias de receita
$receitasDefault = [
    'Salário',
    'Freelance',
    'Investimentos',
    'Aluguel',
    'Bônus',
    'Venda',
    'Outros'
];

// Categorias de despesa
$despesasDefault = [
    'Alimentação',
    'Transporte',
    'Moradia',
    'Saúde',
    'Educação',
    'Lazer',
    'Compras',
    'Contas',
    'Assinaturas',
    'Investimentos',
    'Outros'
];

echo "Criando categorias padrão...\n\n";

// Criar receitas
echo "RECEITAS:\n";
foreach ($receitasDefault as $nome) {
    $cat = Categoria::create([
        'user_id' => $userId,
        'nome' => $nome,
        'tipo' => 'receita'
    ]);
    echo "✅ {$nome} (ID: {$cat->id})\n";
}

echo "\nDESPESAS:\n";
// Criar despesas
foreach ($despesasDefault as $nome) {
    $cat = Categoria::create([
        'user_id' => $userId,
        'nome' => $nome,
        'tipo' => 'despesa'
    ]);
    echo "✅ {$nome} (ID: {$cat->id})\n";
}

$total = Categoria::where('user_id', $userId)->count();
echo "\n✅ Total de categorias criadas: {$total}\n";
