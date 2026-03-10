<?php

/**
 * Atualiza os 3 planos (Free, Pro, Ultra) no banco de dados.
 * Atualiza preços e metadados (features, descrição, etc).
 *
 * Uso: php cli/seed_update_planos.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Application\Models\Plano;

echo "=================================================\n";
echo "  SEED: Atualização dos Planos (Free/Pro/Ultra)\n";
echo "=================================================\n\n";

$updates = [
    'free' => [
        'nome'           => 'Gratuito',
        'preco_centavos' => 0,
        'intervalo'      => 'month',
        'ativo'          => true,
        'metadados'      => [
            'icon'             => 'wallet',
            'descricao'        => 'Ideal para começar a organizar suas finanças pessoais',
            'features'         => [
                'Até 100 lançamentos/mês',
                'Até 2 contas bancárias',
                '1 cartão de crédito',
                '15 categorias personalizadas',
                '3 metas financeiras',
                '3 orçamentos por categoria',
                '3 meses de histórico',
                '5 sugestões IA/mês',
            ],
            'missing_features' => [
                'Relatórios avançados',
                'Exportação PDF/Excel',
                'Dashboard avançado',
                'Análise financeira com IA',
            ],
            'cta_label'        => 'Começar grátis',
        ],
    ],

    'pro' => [
        'nome'           => 'Pro',
        'preco_centavos' => 1490, // R$ 14,90
        'intervalo'      => 'month',
        'ativo'          => true,
        'metadados'      => [
            'icon'             => 'star',
            'descricao'        => 'Controle financeiro completo sem limites',
            'destaque'         => false,
            'features'         => [
                'Lançamentos ilimitados',
                'Contas e cartões ilimitados',
                'Categorias ilimitadas',
                'Metas e orçamentos ilimitados',
                'Histórico completo',
                'IA ilimitada',
                'Relatórios avançados',
                'Exportação PDF/Excel',
                'Dashboard avançado',
                'Suporte prioritário',
            ],
            'missing_features' => [
                'Análise financeira com IA',
                'Insights automáticos',
                'Previsão de saldo',
            ],
            'cta_label'        => 'Assinar Pro',
        ],
    ],

    'ultra' => [
        'nome'           => 'Ultra',
        'preco_centavos' => 3990, // R$ 39,90
        'intervalo'      => 'month',
        'ativo'          => true,
        'metadados'      => [
            'icon'             => 'zap',
            'descricao'        => 'Tudo do Pro + inteligência artificial financeira avançada',
            'destaque'         => true,
            'features'         => [
                'Tudo do plano Pro',
                'Análise financeira com IA',
                'Insights automáticos personalizados',
                'Previsão de saldo inteligente',
                'Chat financeiro com IA avançada',
                'Suporte VIP',
            ],
            'missing_features' => [],
            'cta_label'        => 'Assinar Ultra',
        ],
    ],
];

$success = 0;
$errors  = 0;

foreach ($updates as $code => $data) {
    echo "▶ Atualizando plano: {$code}\n";

    $plano = Plano::where('code', $code)->first();

    if (!$plano) {
        echo "  ⚠️  Plano '{$code}' não encontrado no banco. Criando...\n";
        try {
            $plano = Plano::create(array_merge(['code' => $code], $data));
            echo "  ✅ Criado com sucesso (id={$plano->id})\n";
            $success++;
        } catch (\Exception $e) {
            echo "  ❌ Erro ao criar: " . $e->getMessage() . "\n";
            $errors++;
        }
        echo "\n";
        continue;
    }

    try {
        $plano->update($data);
        echo "  ✅ Atualizado com sucesso (id={$plano->id})\n";
        echo "     Nome: {$plano->nome}\n";
        echo "     Preço: R$ " . number_format($plano->preco_centavos / 100, 2, ',', '.') . "\n";
        echo "     Features: " . count($plano->metadados['features'] ?? []) . " itens\n";
        $success++;
    } catch (\Exception $e) {
        echo "  ❌ Erro ao atualizar: " . $e->getMessage() . "\n";
        $errors++;
    }
    echo "\n";
}

echo "=================================================\n";
echo "  Resultado: {$success} sucesso(s), {$errors} erro(s)\n";
echo "=================================================\n";

if ($errors > 0) {
    exit(1);
}
