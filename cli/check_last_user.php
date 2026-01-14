#!/usr/bin/env php
<?php
/**
 * Script para verificar o último usuário cadastrado
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\UserProgress;

try {
    $progress = UserProgress::orderBy('created_at', 'desc')->first();

    if ($progress) {
        echo "Último usuário:\n";
        echo "ID: {$progress->user_id}\n";
        echo "Total de pontos: {$progress->total_points}\n";
        echo "Nível atual: {$progress->current_level}\n";
        echo "Pontos para próximo nível: {$progress->points_to_next_level}\n";
    } else {
        echo "Nenhum registro encontrado.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
