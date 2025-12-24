<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
require_once dirname(__DIR__) . '/config/config.php';

use Application\Models\Categoria;
use Application\Models\Usuario;

echo "=== CRIANDO CATEGORIAS PADRÃO ===\n\n";

// Pegar primeiro usuário
$user = Usuario::first();
if (!$user) {
    echo "✗ Nenhum usuário encontrado\n";
    exit(1);
}

echo "Usuário: {$user->nome} (ID: {$user->id})\n\n";

// Categorias de despesa
$despesas = [
    'Alimentação',
    'Transporte',
    'Moradia',
    'Saúde',
    'Educação',
    'Lazer',
    'Vestuário',
    'Contas e Serviços',
    'Impostos',
    'Outros Gastos'
];

// Categorias de receita
$receitas = [
    'Salário',
    'Freelance',
    'Investimentos',
    'Aluguel',
    'Vendas',
    'Outras Receitas'
];

$created = 0;

echo "Criando categorias de DESPESA:\n";
foreach ($despesas as $nome) {
    $exists = Categoria::where('user_id', $user->id)
        ->where('nome', $nome)
        ->where('tipo', 'despesa')
        ->exists();

    if (!$exists) {
        Categoria::create([
            'user_id' => $user->id,
            'nome' => $nome,
            'tipo' => 'despesa',
            'ativa' => true
        ]);
        echo "  ✓ {$nome}\n";
        $created++;
    } else {
        echo "  - {$nome} (já existe)\n";
    }
}

echo "\nCriando categorias de RECEITA:\n";
foreach ($receitas as $nome) {
    $exists = Categoria::where('user_id', $user->id)
        ->where('nome', $nome)
        ->where('tipo', 'receita')
        ->exists();

    if (!$exists) {
        Categoria::create([
            'user_id' => $user->id,
            'nome' => $nome,
            'tipo' => 'receita',
            'ativa' => true
        ]);
        echo "  ✓ {$nome}\n";
        $created++;
    } else {
        echo "  - {$nome} (já existe)\n";
    }
}

echo "\n✅ CONCLUÍDO!\n";
echo "Total criado: {$created} categorias\n";
echo "Total no sistema: " . Categoria::where('user_id', $user->id)->count() . " categorias\n";
