<?php

/**
 * CLI Script: Gerar códigos de suporte para usuários existentes
 * 
 * Gera o support_code (LUK-XXXX-XXXX) para todos os usuários que ainda não possuem.
 * Seguro para rodar múltiplas vezes — só gera para quem não tem.
 * 
 * Uso: php cli/generate_support_codes.php
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Services\LogService;

echo "=== Geração de Códigos de Suporte ===" . PHP_EOL;
echo "Data/Hora: " . date('Y-m-d H:i:s') . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

try {
    $users = Usuario::whereNull('support_code')
        ->orWhere('support_code', '')
        ->get();

    $total = $users->count();
    $generated = 0;
    $failed = 0;

    echo "Usuários sem código: {$total}" . PHP_EOL;

    if ($total === 0) {
        echo "Todos os usuários já possuem código de suporte." . PHP_EOL;
        exit(0);
    }

    foreach ($users as $user) {
        try {
            $code = Usuario::generateSupportCode();
            $user->support_code = $code;
            $user->saveQuietly(); // Salva sem disparar eventos

            echo "  [{$user->id}] {$user->email} → {$code}" . PHP_EOL;
            $generated++;
        } catch (Throwable $e) {
            echo "  [{$user->id}] FALHOU: {$e->getMessage()}" . PHP_EOL;
            $failed++;
        }
    }

    echo PHP_EOL . "Resultado:" . PHP_EOL;
    echo "  - Total processados: {$total}" . PHP_EOL;
    echo "  - Códigos gerados: {$generated}" . PHP_EOL;
    echo "  - Falhas: {$failed}" . PHP_EOL;

    LogService::info('[generate_support_codes] Finalizado', [
        'total' => $total,
        'generated' => $generated,
        'failed' => $failed,
    ]);
} catch (Throwable $e) {
    echo "ERRO: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "Concluído!" . PHP_EOL;
