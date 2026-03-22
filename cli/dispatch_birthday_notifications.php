<?php

require dirname(__DIR__) . '/bootstrap.php';

use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Infrastructure\SchedulerTaskRunner;
use Carbon\Carbon;

echo "=================================================\n";
echo "  NOTIFICACOES DE ANIVERSARIO\n";
echo "=================================================\n";
echo "  Data: " . Carbon::today()->format('d/m/Y (l)') . "\n";
echo "=================================================\n\n";

$noEmail = in_array('--no-email', $argv, true);
$previewOnly = in_array('--preview', $argv, true);

$lock = new SchedulerExecutionLock();
$runner = new SchedulerTaskRunner();

try {
    if ($previewOnly) {
        echo "MODO PREVIEW - Aniversariantes de hoje:\n\n";

        $preview = $runner->runTask(SchedulerTaskRunner::TASK_DISPATCH_BIRTHDAYS, [
            'preview' => true,
        ]);
        $birthdayUsers = $preview['result']['birthday_users'] ?? [];

        if (empty($birthdayUsers)) {
            echo "   Nenhum aniversariante hoje.\n\n";
        } else {
            foreach ($birthdayUsers as $user) {
                echo "   {$user['nome']} ({$user['email']})\n";
                echo "      Nascimento: {$user['data_nascimento']} - Completa {$user['idade']} anos\n\n";
            }
            echo "   Total: " . count($birthdayUsers) . " aniversariante(s)\n\n";
        }

        echo "Proximos 7 dias:\n\n";
        $upcoming = $preview['result']['upcoming_birthdays'] ?? [];

        if (empty($upcoming)) {
            echo "   Nenhum aniversariante nos proximos 7 dias.\n\n";
        } else {
            foreach ($upcoming as $user) {
                $label = $user['dias_restantes'] === 0 ? 'HOJE' : "em {$user['dias_restantes']} dia(s)";
                echo "   {$user['data_aniversario']} - {$user['nome']} ({$label})\n";
            }
            echo "\n   Total: " . count($upcoming) . " aniversariante(s)\n\n";
        }

        exit(0);
    }

    echo "Processando aniversariantes...\n\n";

    $lock->acquire('scheduler');
    $run = $runner->runTask(SchedulerTaskRunner::TASK_DISPATCH_BIRTHDAYS, [
        'no_email' => $noEmail,
    ]);

    if (($run['success'] ?? false) !== true) {
        throw new RuntimeException((string) ($run['error'] ?? 'Falha ao processar aniversarios.'));
    }

    $result = $run['result'];
    $sendEmail = !$noEmail;

    echo "RESULTADO:\n";
    echo "   - Aniversariantes do dia: {$result['birthday_users']}\n";
    echo "   - Notificacoes enviadas: {$result['notifications_sent']}\n";

    if ($sendEmail) {
        echo "   - E-mails enviados: {$result['emails_sent']}\n";
        if ($result['emails_failed'] > 0) {
            echo "   - E-mails falharam: {$result['emails_failed']}\n";
        }
    } else {
        echo "   - E-mails: desabilitado (--no-email)\n";
    }

    if ($result['already_notified'] > 0) {
        echo "   - Ja notificados hoje: {$result['already_notified']}\n";
    }

    echo "\n=================================================\n";

    if ($result['notifications_sent'] > 0) {
        echo "  Concluido! {$result['notifications_sent']} parabens enviados!\n";
    } elseif ($result['birthday_users'] === 0) {
        echo "  Nenhum aniversariante hoje.\n";
    } else {
        echo "  Todos ja foram notificados anteriormente.\n";
    }

    echo "=================================================\n\n";
} catch (\Exception $e) {
    echo "\nERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    exit(1);
} finally {
    $lock->release();
}
