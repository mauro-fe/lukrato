<?php

/**
 * Script CLI para verificar cobranças duplicadas
 * Executar via cron a cada 5 minutos
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\DuplicateChargeMonitor;

echo "🔍 Iniciando verificação de cobranças duplicadas...\n\n";

try {
    // Executar monitor principal
    $results = DuplicateChargeMonitor::run();

    echo "✅ Monitor executado com sucesso:\n";
    echo "   - Usuários verificados: {$results['checked_users']}\n";
    echo "   - Duplicatas encontradas: {$results['duplicates_found']}\n";
    echo "   - Alertas enviados: {$results['alerts_sent']}\n\n";

    // Verificar não resolvidas
    $unresolved = DuplicateChargeMonitor::checkUnresolvedDuplicates();

    if (!empty($unresolved)) {
        echo "⚠️ Cobranças duplicadas não resolvidas: " . count($unresolved) . "\n";
        foreach ($unresolved as $item) {
            echo "   - ID {$item['id']}: Usuário {$item['user_id']} - R$ {$item['valor']} (há {$item['detectado_ha']})\n";
        }
    } else {
        echo "✅ Nenhuma cobrança duplicada pendente\n";
    }

    echo "\n✅ Verificação concluída em " . date('Y-m-d H:i:s') . "\n";
    exit(0);
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
