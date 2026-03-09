<?php

/**
 * Seed do plano Ultra no banco de dados.
 * 
 * Uso: php cli/seed_plano_ultra.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Application\Models\Plano;

echo "=================================================\n";
echo "  SEED: Plano Ultra\n";
echo "=================================================\n\n";

// Verificar se já existe
$existing = Plano::where('code', 'ultra')->first();
if ($existing) {
    echo "⏭️  Plano Ultra já existe (id={$existing->id})\n";
    echo "   Nome: {$existing->nome}\n";
    echo "   Preço: R$ " . number_format($existing->preco_centavos / 100, 2, ',', '.') . "\n\n";
    exit(0);
}

try {
    $plano = Plano::create([
        'code'           => 'ultra',
        'nome'           => 'Ultra',
        'preco_centavos' => 3990, // R$ 39,90 — ajustar conforme necessário
        'intervalo'      => 'month',
        'ativo'          => true,
        'metadados'      => [
            'icon'        => 'zap',
            'description' => 'Tudo do Pro + Assistente IA ilimitado',
            'features'    => [
                'Tudo do plano Pro',
                'Assistente IA ilimitado',
                'Análises financeiras com IA',
                'Suporte prioritário',
            ],
        ],
    ]);

    echo "✅ Plano Ultra criado com sucesso!\n";
    echo "   ID: {$plano->id}\n";
    echo "   Code: {$plano->code}\n";
    echo "   Preço: R$ " . number_format($plano->preco_centavos / 100, 2, ',', '.') . "/mês\n\n";
} catch (\Exception $e) {
    echo "❌ Erro ao criar plano: " . $e->getMessage() . "\n";
    exit(1);
}
