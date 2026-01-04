<?php

/**
 * Script CLI para validar integridade dos limites de cartões de crédito
 * 
 * Uso:
 * php cli/validar_integridade_cartoes.php [user_id] [--corrigir]
 * 
 * Exemplos:
 * php cli/validar_integridade_cartoes.php 1            # Apenas validar
 * php cli/validar_integridade_cartoes.php 1 --corrigir # Validar e corrigir
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\CartaoCreditoService;

// Parsear argumentos
$userId = isset($argv[1]) ? (int) $argv[1] : null;
$corrigir = in_array('--corrigir', $argv);

if (!$userId) {
    echo "❌ Erro: ID do usuário é obrigatório\n";
    echo "\nUso: php cli/validar_integridade_cartoes.php [user_id] [--corrigir]\n";
    echo "\nExemplos:\n";
    echo "  php cli/validar_integridade_cartoes.php 1            # Apenas validar\n";
    echo "  php cli/validar_integridade_cartoes.php 1 --corrigir # Validar e corrigir\n\n";
    exit(1);
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  VALIDAÇÃO DE INTEGRIDADE - CARTÕES DE CRÉDITO              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "👤 Usuário ID: {$userId}\n";
echo "🔧 Modo: " . ($corrigir ? "CORREÇÃO AUTOMÁTICA" : "APENAS VALIDAÇÃO") . "\n";
echo str_repeat("─", 64) . "\n\n";

try {
    $service = new CartaoCreditoService();
    $relatorio = $service->validarIntegridadeLimites($userId, $corrigir);

    echo "📊 RESULTADOS:\n\n";
    echo "  • Total de cartões: {$relatorio['total_cartoes']}\n";
    echo "  • Cartões OK: {$relatorio['cartoes_ok']} ✅\n";
    echo "  • Cartões com divergência: {$relatorio['cartoes_com_divergencia']} ⚠️\n";

    if ($corrigir) {
        echo "  • Cartões corrigidos: {$relatorio['corrigidos']} 🔧\n";
    }

    echo "\n";

    if (!empty($relatorio['divergencias'])) {
        echo str_repeat("─", 64) . "\n";
        echo "⚠️  DIVERGÊNCIAS ENCONTRADAS:\n";
        echo str_repeat("─", 64) . "\n\n";

        foreach ($relatorio['divergencias'] as $idx => $div) {
            $num = $idx + 1;
            echo "#{$num} - {$div['nome_cartao']} (ID: {$div['cartao_id']})\n";
            echo "    Limite Total: R$ " . number_format($div['limite_total'], 2, ',', '.') . "\n";
            echo "    Limite Disponível Registrado: R$ " . number_format($div['limite_disponivel_atual'], 2, ',', '.') . "\n";
            echo "    Limite Utilizado Registrado: R$ " . number_format($div['limite_utilizado_registrado'], 2, ',', '.') . "\n";
            echo "    Limite Utilizado Real: R$ " . number_format($div['limite_utilizado_real'], 2, ',', '.') . "\n";
            echo "    ⚠️  Diferença: R$ " . number_format($div['diferenca'], 2, ',', '.') . "\n";
            echo "    ✅ Limite Disponível Correto: R$ " . number_format($div['limite_disponivel_correto'], 2, ',', '.') . "\n";

            if (isset($div['corrigido']) && $div['corrigido']) {
                echo "    🔧 STATUS: CORRIGIDO\n";
            } elseif (isset($div['erro_correcao'])) {
                echo "    ❌ ERRO AO CORRIGIR: {$div['erro_correcao']}\n";
            }

            echo "\n";
        }

        if (!$corrigir) {
            echo str_repeat("─", 64) . "\n";
            echo "💡 Para corrigir automaticamente, execute:\n";
            echo "   php cli/validar_integridade_cartoes.php {$userId} --corrigir\n\n";
        }
    } else {
        echo "✅ Nenhuma divergência encontrada! Todos os cartões estão com limites corretos.\n\n";
    }

    echo str_repeat("═", 64) . "\n";
    echo "✅ Validação concluída com sucesso!\n";
    echo str_repeat("═", 64) . "\n\n";

    exit(0);
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}
