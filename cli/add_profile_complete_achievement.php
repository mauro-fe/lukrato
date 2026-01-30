<?php

/**
 * Script para adicionar a conquista "Perfil Completo"
 * Execução: php cli/add_profile_complete_achievement.php
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Achievement;

echo "🏆 Adicionando conquista 'Perfil Completo'...\n";

// Verificar se já existe
$existing = Achievement::where('code', 'PROFILE_COMPLETE')->first();
if ($existing) {
    echo "⚠️  Conquista 'PROFILE_COMPLETE' já existe (ID: {$existing->id})\n";
    exit(0);
}

// Descobrir o maior sort_order atual
$maxSortOrder = Achievement::max('sort_order') ?? 0;

// Criar a conquista
$achievement = Achievement::create([
    'code' => 'PROFILE_COMPLETE',
    'name' => 'Perfil Completo',
    'description' => 'Preencha todos os dados do seu perfil',
    'icon' => '👤',
    'points_reward' => 50,
    'category' => 'perfil',
    'plan_type' => 'free', // Disponível para todos
    'is_active' => true,
    'sort_order' => $maxSortOrder + 1,
]);

echo "✅ Conquista criada com sucesso!\n";
echo "   ID: {$achievement->id}\n";
echo "   Código: {$achievement->code}\n";
echo "   Nome: {$achievement->name}\n";
echo "   Pontos: {$achievement->points_reward}\n";
echo "   Tipo: {$achievement->plan_type}\n";
