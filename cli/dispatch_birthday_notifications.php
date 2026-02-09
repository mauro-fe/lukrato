<?php

/**
 * Script para enviar notificações de aniversário
 * 
 * Este script deve ser executado diariamente (via cron/scheduler)
 * para enviar notificações e emails de parabéns aos usuários
 * que fazem aniversário no dia.
 * 
 * Uso:
 *   php cli/dispatch_birthday_notifications.php
 *   php cli/dispatch_birthday_notifications.php --no-email   # Apenas notificação interna
 *   php cli/dispatch_birthday_notifications.php --preview    # Apenas mostra aniversariantes
 * 
 * Cron recomendado (executar às 8h):
 *   0 8 * * * /usr/bin/php /path/to/cli/dispatch_birthday_notifications.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Application\Services\NotificationService;
use Carbon\Carbon;

echo "=================================================\n";
echo "  🎂 NOTIFICAÇÕES DE ANIVERSÁRIO\n";
echo "=================================================\n";
echo "  Data: " . Carbon::today()->format('d/m/Y (l)') . "\n";
echo "=================================================\n\n";

// Processar argumentos
$noEmail = in_array('--no-email', $argv);
$previewOnly = in_array('--preview', $argv);

try {
    $service = new NotificationService();

    // Modo preview - apenas mostra aniversariantes
    if ($previewOnly) {
        echo "📋 MODO PREVIEW - Aniversariantes de hoje:\n\n";
        
        $birthdayUsers = $service->getBirthdayUsers();
        
        if (empty($birthdayUsers)) {
            echo "   Nenhum aniversariante hoje.\n\n";
        } else {
            foreach ($birthdayUsers as $user) {
                echo "   🎂 {$user['nome']} ({$user['email']})\n";
                echo "      Nascimento: {$user['data_nascimento']} - Completa {$user['idade']} anos\n\n";
            }
            echo "   Total: " . count($birthdayUsers) . " aniversariante(s)\n\n";
        }

        // Mostrar próximos aniversariantes
        echo "📅 Próximos 7 dias:\n\n";
        $upcoming = $service->getUpcomingBirthdays(7);
        
        if (empty($upcoming)) {
            echo "   Nenhum aniversariante nos próximos 7 dias.\n\n";
        } else {
            foreach ($upcoming as $user) {
                $label = $user['dias_restantes'] === 0 ? 'HOJE' : "em {$user['dias_restantes']} dia(s)";
                echo "   📆 {$user['data_aniversario']} - {$user['nome']} ({$label})\n";
            }
            echo "\n   Total: " . count($upcoming) . " aniversariante(s)\n\n";
        }

        exit(0);
    }

    // Modo execução - envia notificações
    echo "🚀 Processando aniversariantes...\n\n";

    $sendEmail = !$noEmail;
    $result = $service->processBirthdayNotifications($sendEmail);

    // Exibir resultados
    echo "📊 RESULTADO:\n";
    echo "   • Aniversariantes do dia: {$result['birthday_users']}\n";
    echo "   • Notificações enviadas: {$result['notifications_sent']}\n";
    
    if ($sendEmail) {
        echo "   • E-mails enviados: {$result['emails_sent']}\n";
        if ($result['emails_failed'] > 0) {
            echo "   • E-mails falharam: {$result['emails_failed']} ⚠️\n";
        }
    } else {
        echo "   • E-mails: desabilitado (--no-email)\n";
    }
    
    if ($result['already_notified'] > 0) {
        echo "   • Já notificados hoje: {$result['already_notified']}\n";
    }

    echo "\n=================================================\n";
    
    if ($result['notifications_sent'] > 0) {
        echo "  ✅ Concluído! {$result['notifications_sent']} parabéns enviados!\n";
    } else if ($result['birthday_users'] === 0) {
        echo "  ℹ️ Nenhum aniversariante hoje.\n";
    } else {
        echo "  ℹ️ Todos já foram notificados anteriormente.\n";
    }
    
    echo "=================================================\n\n";

    // Log para auditoria
    if ($result['notifications_sent'] > 0) {
        error_log("🎂 [BIRTHDAY] {$result['notifications_sent']} notificações de aniversário enviadas em {$result['date']}");
    }

} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    error_log("🎂 [BIRTHDAY] Erro: " . $e->getMessage());
    exit(1);
}
