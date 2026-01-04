<?php

/**
 * Script de teste para validar conversão de limite de cartão
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap.php';

use Application\DTO\CreateCartaoCreditoDTO;

echo "\n=== TESTE DE CONVERSÃO DE LIMITE DE CARTÃO ===\n\n";

// Casos de teste
$casos = [
    ['valor' => 5000, 'descricao' => 'Número inteiro'],
    ['valor' => 5000.50, 'descricao' => 'Float'],
    ['valor' => '5000.50', 'descricao' => 'String com ponto decimal'],
    ['valor' => '5.000,50', 'descricao' => 'String formato brasileiro com milhar'],
    ['valor' => '5000,50', 'descricao' => 'String formato brasileiro sem milhar'],
    ['valor' => '10.250,75', 'descricao' => 'String formato brasileiro 10 mil'],
    ['valor' => 'R$ 5.000,50', 'descricao' => 'String com R$ e formato brasileiro'],
];

foreach ($casos as $caso) {
    echo "Teste: {$caso['descricao']}\n";
    echo "Valor entrada: " . var_export($caso['valor'], true) . "\n";

    // Criar DTO
    $data = [
        'conta_id' => 1,
        'nome_cartao' => 'Teste',
        'bandeira' => 'visa',
        'ultimos_digitos' => '1234',
        'limite_total' => $caso['valor'],
    ];

    $dto = CreateCartaoCreditoDTO::fromArray($data, 1);

    echo "Valor DTO: " . $dto->limiteTotal . "\n";
    echo "Tipo: " . gettype($dto->limiteTotal) . "\n";
    echo str_repeat('-', 60) . "\n\n";
}

echo "=== FIM DOS TESTES ===\n\n";
