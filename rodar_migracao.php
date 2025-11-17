<?php

/*
|--------------------------------------------------------------------------
| SCRIPT PARA RODAR MIGRAÇÃO (USO ÚNICO)
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/vendor/autoload.php';

// 1. Carrega sua configuração principal (que inicia o Eloquent)
// (Ele já deve ter o 'vendor/autoload.php' dentro dele)
require_once __DIR__ . '/config/config.php';
// 2. Carrega o arquivo da migration que queremos rodar
// (Confira se o nome do arquivo está correto)
require_once __DIR__ . '/database/migrations/2025_11_17_100000_create_enderecos_table.php';

// 3. Importa o Schema do Capsule
use Illuminate\Database\Capsule\Manager as DB;

// 4. Instancia e executa a migração
try {
    // Garante que a classe existe antes de usá-la
    if (!class_exists('CreateEnderecosTable')) {
        throw new Exception('A classe da migração "CreateEnderecosTable" não foi encontrada.');
    }

    $migracao = new CreateEnderecosTable();
    $migracao->up(); // Executa o método up()
    
    echo "<h1>Sucesso!</h1>";
    echo "<p>A tabela 'enderecos' foi criada no banco de dados.</p>";
    echo "<p style='color:red; font-weight:bold;'>IMPORTANTE: Delete este arquivo (rodar_migracao.php) agora!</p>";

} catch (\Exception $e) {
    echo "<h1>Erro ao rodar migração</h1>";
    echo "<p>Ocorreu um problema:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    
    // Se o erro for de "tabela já existe", damos uma mensagem amigável
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<p><b>Parece que a tabela 'enderecos' já existe no banco.</b></p>";
        echo "<p>Se você estava tentando recriá-la, precisa rodar o método down() primeiro.</p>";
    }
}