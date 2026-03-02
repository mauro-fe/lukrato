<?php

/**
 * CLI Script: Limpar contas não verificadas
 * 
 * Remove contas que foram criadas há mais de 7 dias sem verificação de email.
 * Envia um email de aviso antes de remover.
 * 
 * Recomendação de cron (rodar 1x por dia, de madrugada):
 * 0 3 * * * php /path/to/lukrato/cli/cleanup_unverified_accounts.php
 * 
 * Use --dry-run para simular sem excluir:
 * php cli/cleanup_unverified_accounts.php --dry-run
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Services\Auth\EmailVerificationService;
use Application\Services\Infrastructure\LogService;

$isDryRun = in_array('--dry-run', $argv ?? [], true);

echo "=== Limpeza de Contas Não Verificadas ===" . PHP_EOL;
if ($isDryRun) {
    echo "*** MODO SIMULAÇÃO (--dry-run) — nenhuma conta será removida ***" . PHP_EOL;
}
echo "Data/Hora: " . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

LogService::info('=== [cleanup_unverified] Início ===', ['dry_run' => $isDryRun]);

try {
    $service = new EmailVerificationService();
    $users = $service->getExpiredUnverifiedAccounts();

    $total = $users->count();
    $removed = 0;
    $failed = 0;

    echo "Contas expiradas encontradas: {$total}" . PHP_EOL;

    if ($total === 0) {
        echo "Nenhuma conta para remover." . PHP_EOL;
        LogService::info('[cleanup_unverified] Nenhuma conta expirada encontrada');
        exit(0);
    }

    foreach ($users as $user) {
        $daysAgo = $user->created_at ? $user->created_at->diffInDays(now()) : '?';
        echo "  [{$user->id}] {$user->email} (criada há {$daysAgo} dias)";

        if ($isDryRun) {
            echo " — SIMULAÇÃO (não removida)" . PHP_EOL;
            $removed++;
            continue;
        }

        $result = $service->removeUnverifiedAccount($user);

        if ($result) {
            echo " — REMOVIDA" . PHP_EOL;
            $removed++;
        } else {
            echo " — FALHOU" . PHP_EOL;
            $failed++;
        }

        // Pausa entre operações
        usleep(300000); // 300ms
    }

    echo PHP_EOL . "Resultado:" . PHP_EOL;
    echo "  - Total encontradas: {$total}" . PHP_EOL;
    echo "  - Contas removidas: {$removed}" . PHP_EOL;
    echo "  - Falhas: {$failed}" . PHP_EOL;

    LogService::info('[cleanup_unverified] Finalizado', [
        'dry_run' => $isDryRun,
        'total' => $total,
        'removed' => $removed,
        'failed' => $failed,
    ]);
} catch (Throwable $e) {
    $msg = "ERRO: " . $e->getMessage();
    echo $msg . PHP_EOL;
    LogService::error('[cleanup_unverified] Exceção', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    exit(1);
}

echo PHP_EOL . "Concluído!" . PHP_EOL;
