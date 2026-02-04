<?php

/**
 * CLI: Testar Sistema de Notificação de Conquistas
 * 
 * Uso: php cli/test_achievement_notification.php <user_id>
 * 
 * Este script:
 * 1. Cria uma conquista pendente para o usuário
 * 2. Permite verificar se a notificação aparece ao acessar o sistema
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Carbon\Carbon;

echo "===========================================\n";
echo "🏆 TESTE DE NOTIFICAÇÃO DE CONQUISTAS\n";
echo "===========================================\n\n";

// Verificar argumentos
$userId = $argv[1] ?? null;

if (!$userId) {
    echo "Uso: php cli/test_achievement_notification.php <user_id>\n\n";
    echo "Usuários disponíveis:\n";
    
    $users = Usuario::orderBy('id')->limit(20)->get(['id', 'nome', 'email']);
    foreach ($users as $user) {
        echo "  ID: {$user->id} - {$user->nome} ({$user->email})\n";
    }
    
    echo "\nExemplo: php cli/test_achievement_notification.php 1\n";
    exit(1);
}

$user = Usuario::find($userId);
if (!$user) {
    echo "❌ Usuário ID {$userId} não encontrado!\n";
    exit(1);
}

echo "👤 Usuário: {$user->nome} ({$user->email})\n\n";

// Listar conquistas disponíveis
echo "Conquistas disponíveis:\n";
$achievements = Achievement::orderBy('id')->get(['id', 'code', 'name', 'icon']);
foreach ($achievements as $ach) {
    $unlocked = UserAchievement::where('user_id', $userId)
        ->where('achievement_id', $ach->id)
        ->exists();
    
    $status = $unlocked ? '✅' : '❌';
    echo "  {$status} [{$ach->id}] {$ach->icon} {$ach->name} ({$ach->code})\n";
}

echo "\n";

// Opções
echo "Opções:\n";
echo "  1. Criar conquista pendente (notification_seen = false)\n";
echo "  2. Resetar todas as conquistas do usuário\n";
echo "  3. Ver conquistas pendentes (não notificadas)\n";
echo "  4. Sair\n\n";

$option = readline("Escolha uma opção (1-4): ");

switch ($option) {
    case '1':
        // Listar conquistas não desbloqueadas
        $unlockedIds = UserAchievement::where('user_id', $userId)
            ->pluck('achievement_id')
            ->toArray();
        
        $available = Achievement::whereNotIn('id', $unlockedIds)->get(['id', 'name', 'icon']);
        
        if ($available->isEmpty()) {
            echo "\n⚠️ Usuário já desbloqueou todas as conquistas!\n";
            
            // Oferecer resetar uma conquista específica para teste
            echo "\nDeseja resetar uma conquista para poder testar? (s/n): ";
            $reset = strtolower(trim(readline()));
            
            if ($reset === 's') {
                $allAchs = Achievement::orderBy('id')->get(['id', 'name', 'icon']);
                foreach ($allAchs as $a) {
                    echo "  [{$a->id}] {$a->icon} {$a->name}\n";
                }
                
                $resetId = readline("\nID da conquista para resetar: ");
                
                UserAchievement::where('user_id', $userId)
                    ->where('achievement_id', $resetId)
                    ->delete();
                
                echo "\n✅ Conquista removida. Agora você pode recriá-la.\n";
                
                // Recriar com notification_seen = false
                $achievement = Achievement::find($resetId);
                if ($achievement) {
                    UserAchievement::create([
                        'user_id' => $userId,
                        'achievement_id' => $resetId,
                        'unlocked_at' => Carbon::now(),
                        'notification_seen' => false,
                    ]);
                    
                    echo "\n🏆 Conquista '{$achievement->name}' criada como PENDENTE!\n";
                    echo "➡️  Agora acesse o sistema como este usuário.\n";
                    echo "➡️  Em 1.5 segundos deve aparecer o modal de parabéns!\n";
                }
            }
        } else {
            echo "\nConquistas disponíveis para desbloquear:\n";
            foreach ($available as $a) {
                echo "  [{$a->id}] {$a->icon} {$a->name}\n";
            }
            
            $achId = readline("\nID da conquista para criar como pendente: ");
            
            $achievement = Achievement::find($achId);
            if (!$achievement) {
                echo "❌ Conquista não encontrada!\n";
                break;
            }
            
            UserAchievement::create([
                'user_id' => $userId,
                'achievement_id' => $achId,
                'unlocked_at' => Carbon::now(),
                'notification_seen' => false,
            ]);
            
            echo "\n🏆 Conquista '{$achievement->name}' criada como PENDENTE!\n";
            echo "➡️  Agora acesse o sistema como este usuário.\n";
            echo "➡️  Em 1.5 segundos deve aparecer o modal de parabéns!\n";
        }
        break;
        
    case '2':
        echo "\n⚠️  ATENÇÃO: Isso removerá todas as conquistas do usuário!\n";
        $confirm = readline("Confirma? (digite 'SIM' para confirmar): ");
        
        if ($confirm === 'SIM') {
            $deleted = UserAchievement::where('user_id', $userId)->delete();
            echo "\n✅ {$deleted} conquista(s) removida(s).\n";
        } else {
            echo "\n❌ Operação cancelada.\n";
        }
        break;
        
    case '3':
        $pending = UserAchievement::with('achievement')
            ->where('user_id', $userId)
            ->where('notification_seen', false)
            ->get();
        
        if ($pending->isEmpty()) {
            echo "\n✅ Nenhuma conquista pendente de notificação.\n";
        } else {
            echo "\n🔔 Conquistas PENDENTES de notificação:\n";
            foreach ($pending as $ua) {
                $ach = $ua->achievement;
                echo "  {$ach->icon} {$ach->name} - desbloqueada em {$ua->unlocked_at}\n";
            }
            echo "\n➡️  Ao acessar o sistema, o modal deve aparecer para estas conquistas!\n";
        }
        break;
        
    case '4':
        echo "\n👋 Saindo...\n";
        break;
        
    default:
        echo "\n❌ Opção inválida!\n";
}

echo "\n";
