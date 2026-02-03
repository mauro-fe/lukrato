<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICANDO ENUM STATUS DA TABELA AGENDAMENTOS ===\n";

$result = DB::select("SHOW COLUMNS FROM agendamentos WHERE Field = 'status'");

if (!empty($result)) {
    $currentType = $result[0]->Type;
    echo "Tipo atual: {$currentType}\n";

    // Verificar se 'notificado' está presente no ENUM
    if (strpos($currentType, 'notificado') === false) {
        echo "\nALERTA: O valor 'notificado' NÃO está no ENUM atual!\n";
        echo "O código usa 'notificado' mas o banco só tem: {$currentType}\n";

        echo "\n=== CORRIGINDO ENUM ===\n";
        try {
            // Primeiro, vamos atualizar registros que possam ter valor inválido
            DB::statement("UPDATE agendamentos SET status = 'pendente' WHERE status = 'enviado'");

            // Agora alterar o ENUM
            DB::statement("ALTER TABLE agendamentos MODIFY COLUMN status ENUM('pendente','notificado','concluido','cancelado') DEFAULT 'pendente'");
            echo "ENUM atualizado com sucesso!\n";

            // Verificar novamente
            $result = DB::select("SHOW COLUMNS FROM agendamentos WHERE Field = 'status'");
            echo "Novo tipo: " . $result[0]->Type . "\n";
        } catch (\Throwable $e) {
            echo "Erro ao atualizar ENUM: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\nOK: O valor 'notificado' já está no ENUM.\n";
    }
} else {
    echo "Coluna status não encontrada!\n";
}

echo "\n=== VERIFICANDO AGENDAMENTOS COM STATUS INVÁLIDO ===\n";
$invalidos = DB::select("SELECT id, titulo, status FROM agendamentos WHERE status NOT IN ('pendente','notificado','concluido','cancelado') OR status = '' OR status IS NULL");
if (!empty($invalidos)) {
    echo "Encontrados " . count($invalidos) . " agendamentos com status inválido - corrigindo...\n";
    foreach ($invalidos as $inv) {
        echo "  ID: {$inv->id}, Titulo: {$inv->titulo}, Status: '{$inv->status}'\n";
    }
    DB::statement("UPDATE agendamentos SET status = 'pendente' WHERE status NOT IN ('pendente','notificado','concluido','cancelado') OR status = '' OR status IS NULL");
    echo "Registros corrigidos!\n";
} else {
    echo "Nenhum agendamento com status inválido.\n";
}

echo "\n=== CONCLUÍDO ===\n";
