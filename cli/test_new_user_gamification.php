#!/usr/bin/env php
<?php
/**
 * Script para testar criação de novo usuário com gamificação
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Services\GamificationService;
use Application\Models\UserProgress;

echo "🧪 Testando criação de novo progresso de gamificação...\n";
echo str_repeat("=", 60) . "\n\n";

// ID de teste (usuário que não deve ter progresso)
$testUserId = 9999;

// Limpar qualquer progresso de teste anterior
UserProgress::where('user_id', $testUserId)->delete();
echo "✅ Limpeza de testes anteriores concluída\n\n";

// Criar progresso diretamente (isso acontece no primeiro login/cadastro)
echo "📝 Criando progresso para usuário ID {$testUserId}...\n";
$progress = UserProgress::firstOrCreate(
    ['user_id' => $testUserId],
    [
        'total_points' => 0,
        'current_level' => 1,
        'points_to_next_level' => 300,
        'current_streak' => 0,
        'best_streak' => 0,
        'last_activity_date' => null,
    ]
);

echo "\n📊 Resultado:\n";
echo "Total de pontos: {$progress->total_points}\n";
echo "Nível atual: {$progress->current_level}\n";
echo "Pontos para próximo nível: {$progress->points_to_next_level}\n";

// Verificar se está correto
if ($progress->points_to_next_level === 300 && $progress->total_points === 0) {
    echo "\n✅ TESTE PASSOU! Valores corretos:\n";
    echo "   0 / 300 pontos para próximo nível ✓\n";
} else {
    echo "\n❌ TESTE FALHOU! Valores incorretos:\n";
    echo "   Esperado: 0 / 300\n";
    echo "   Recebido: {$progress->total_points} / {$progress->points_to_next_level}\n";
}

// Limpar teste
UserProgress::where('user_id', $testUserId)->delete();
echo "\n🧹 Limpeza concluída\n";
