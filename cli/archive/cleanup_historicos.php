<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== LIMPEZA DE LANÇAMENTOS HISTÓRICOS ===\n\n";

try {
    // Buscar lançamentos históricos (valor = 0 e descrição contém HISTÓRICO)
    $historicos = DB::table('lancamentos')
        ->where('valor', 0)
        ->where('descricao', 'LIKE', '%HISTÓRICO PARCELAMENTO%')
        ->get();

    echo "📋 Encontrados: " . count($historicos) . " lançamentos históricos\n\n";

    if (count($historicos) > 0) {
        foreach ($historicos as $h) {
            echo "  - ID: {$h->id} | {$h->descricao} | Data: {$h->data}\n";
        }

        echo "\n⚠️  Deseja deletar estes registros? (s/n): ";
        $resposta = trim(fgets(STDIN));

        if (strtolower($resposta) === 's') {
            $deletados = DB::table('lancamentos')
                ->where('valor', 0)
                ->where('descricao', 'LIKE', '%HISTÓRICO PARCELAMENTO%')
                ->delete();

            echo "\n✅ {$deletados} lançamentos históricos deletados!\n";
        } else {
            echo "\n❌ Operação cancelada.\n";
        }
    } else {
        echo "✅ Nenhum lançamento histórico encontrado!\n";
    }
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
