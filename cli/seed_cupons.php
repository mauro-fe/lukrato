<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\Cupom;

echo "Criando cupons de exemplo...\n\n";

$cupons = [
    [
        'codigo' => 'BEMVINDO10',
        'tipo_desconto' => 'percentual',
        'valor_desconto' => 10.00,
        'valido_ate' => date('Y-m-d', strtotime('+30 days')),
        'limite_uso' => 100,
        'ativo' => true,
        'descricao' => 'Cupom de boas-vindas - 10% de desconto'
    ],
    [
        'codigo' => 'PROMO20',
        'tipo_desconto' => 'percentual',
        'valor_desconto' => 20.00,
        'valido_ate' => date('Y-m-d', strtotime('+15 days')),
        'limite_uso' => 50,
        'ativo' => true,
        'descricao' => 'Promoção especial - 20% de desconto'
    ],
    [
        'codigo' => 'FIXO5',
        'tipo_desconto' => 'fixo',
        'valor_desconto' => 5.00,
        'valido_ate' => date('Y-m-d', strtotime('+60 days')),
        'limite_uso' => 0, // ilimitado
        'ativo' => true,
        'descricao' => 'Desconto fixo de R$ 5,00'
    ]
];

foreach ($cupons as $cupomData) {
    // Verificar se já existe
    $existe = Cupom::where('codigo', $cupomData['codigo'])->first();
    
    if ($existe) {
        echo "⏭️  Cupom {$cupomData['codigo']} já existe\n";
        continue;
    }
    
    $cupom = Cupom::create($cupomData);
    echo "✅ Cupom {$cupom->codigo} criado: {$cupom->getDescontoFormatado()} - Válido até {$cupom->valido_ate->format('d/m/Y')}\n";
}

echo "\n✨ Cupons de exemplo criados com sucesso!\n";
