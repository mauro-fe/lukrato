<?php
/**
 * Script de teste do sistema de aniversários
 * 
 * Simula um aniversário para testar notificação e email
 */

require dirname(__DIR__) . '/bootstrap.php';

use Application\Models\Usuario;
use Application\Models\Notification;
use Application\Services\Communication\NotificationService;
use Carbon\Carbon;

echo "=================================================\n";
echo "  🧪 TESTE: Sistema de Aniversários\n";
echo "=================================================\n\n";

// Buscar um usuário para teste (o admin logado ou primeiro usuário)
$testUser = Usuario::where('is_admin', 1)->first() ?? Usuario::first();

if (!$testUser) {
    echo "❌ Nenhum usuário encontrado para teste.\n";
    exit(1);
}

echo "👤 Usuário de teste: {$testUser->nome} ({$testUser->email})\n";
echo "   ID: {$testUser->id}\n";

// Salvar data original
$originalDate = $testUser->data_nascimento;
echo "   Data nascimento original: " . ($originalDate ? Carbon::parse($originalDate)->format('d/m/Y') : 'não definida') . "\n\n";

// Atualizar para hoje (simular aniversário)
$today = Carbon::today();
$fakeDate = $today->copy()->subYears(30)->format('Y-m-d'); // Simula 30 anos

echo "🔄 Simulando aniversário...\n";
echo "   Definindo data de nascimento para: " . Carbon::parse($fakeDate)->format('d/m/Y') . "\n";

$testUser->data_nascimento = $fakeDate;
$testUser->save();

// Limpar notificações de teste anteriores (do mesmo ano)
$deleted = Notification::where('user_id', $testUser->id)
    ->where('type', 'birthday')
    ->whereYear('created_at', $today->year)
    ->delete();

if ($deleted > 0) {
    echo "   🗑️ Removidas $deleted notificações de teste anteriores\n";
}

echo "\n🚀 Executando processamento de aniversários...\n\n";

// Processar aniversários (sem email para não enviar de verdade)
$sendEmail = false; // Mude para true se quiser testar o email também
$service = new NotificationService();
$result = $service->processBirthdayNotifications($sendEmail);

echo "📊 RESULTADO:\n";
echo "   • Aniversariantes encontrados: {$result['birthday_users']}\n";
echo "   • Notificações enviadas: {$result['notifications_sent']}\n";
echo "   • Emails enviados: {$result['emails_sent']}\n\n";

// Verificar se a notificação foi criada
$notification = Notification::where('user_id', $testUser->id)
    ->where('type', 'birthday')
    ->orderBy('created_at', 'desc')
    ->first();

if ($notification) {
    echo "✅ NOTIFICAÇÃO CRIADA COM SUCESSO!\n";
    echo "   Título: {$notification->title}\n";
    echo "   Mensagem: " . substr($notification->message, 0, 100) . "...\n";
    echo "   Criada em: {$notification->created_at}\n\n";
} else {
    echo "❌ Notificação NÃO foi criada.\n\n";
}

// Restaurar data original
echo "🔄 Restaurando data de nascimento original...\n";
$testUser->data_nascimento = $originalDate;
$testUser->save();

// Perguntar se quer manter a notificação
echo "\n=================================================\n";
if ($notification) {
    echo "  ✅ TESTE CONCLUÍDO COM SUCESSO!\n";
    echo "  A notificação aparecerá no sino 🔔 do usuário.\n";
    echo "\n  Para remover a notificação de teste, execute:\n";
    echo "  php cli/test_birthday.php --cleanup\n";
} else {
    echo "  ❌ TESTE FALHOU - Verifique os logs.\n";
}
echo "=================================================\n\n";

// Cleanup se solicitado
if (in_array('--cleanup', $argv) && $notification) {
    $notification->delete();
    echo "🗑️ Notificação de teste removida.\n\n";
}

// Opção para testar com email real
if (in_array('--with-email', $argv)) {
    echo "📧 Testando envio de email...\n";
    
    // Resetar para permitir novo envio
    if ($notification) {
        $notification->delete();
    }
    
    $testUser->data_nascimento = $fakeDate;
    $testUser->save();
    
    $result = $service->processBirthdayNotifications(true);
    
    echo "   Emails enviados: {$result['emails_sent']}\n";
    echo "   Emails falharam: {$result['emails_failed']}\n";
    
    $testUser->data_nascimento = $originalDate;
    $testUser->save();
}
