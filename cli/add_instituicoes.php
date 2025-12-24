<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Configurar Eloquent
require_once dirname(__DIR__) . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== ADICIONANDO INSTITUIÇÕES FINANCEIRAS ===\n\n";

try {
    // Verificar se já existem
    $nomadExists = DB::table('instituicoes_financeiras')->where('codigo', 'nomad')->exists();
    $binanceExists = DB::table('instituicoes_financeiras')->where('codigo', 'binance')->exists();

    $added = 0;

    if (!$nomadExists) {
        DB::table('instituicoes_financeiras')->insert([
            'nome' => 'Nomad',
            'codigo' => 'nomad',
            'tipo' => 'fintech',
            'cor_primaria' => '#6366f1',
            'logo_path' => '/assets/img/banks/nomad.svg',
            'ativo' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        echo "✓ Nomad adicionada\n";
        $added++;
    } else {
        // Atualizar logo_path se já existe
        DB::table('instituicoes_financeiras')
            ->where('codigo', 'nomad')
            ->update(['logo_path' => '/assets/img/banks/nomad.svg']);
        echo "- Nomad já existe (logo atualizado)\n";
    }

    if (!$binanceExists) {
        DB::table('instituicoes_financeiras')->insert([
            'nome' => 'Binance',
            'codigo' => 'binance',
            'tipo' => 'carteira_digital',
            'cor_primaria' => '#f3ba2f',
            'logo_path' => '/assets/img/banks/binance.svg',
            'ativo' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        echo "✓ Binance adicionada\n";
        $added++;
    } else {
        // Atualizar logo_path se já existe
        DB::table('instituicoes_financeiras')
            ->where('codigo', 'binance')
            ->update(['logo_path' => '/assets/img/banks/binance.svg']);
        echo "- Binance já existe (logo atualizado)\n";
    }

    echo "\n=== CONCLUÍDO ===\n";
    echo "Total adicionado: $added\n";

    // Listar todas
    echo "\n=== INSTITUIÇÕES DISPONÍVEIS ===\n";
    $todas = DB::table('instituicoes_financeiras')->orderBy('nome')->get();
    foreach ($todas as $inst) {
        echo "- {$inst->nome} (código: {$inst->codigo})\n";
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
