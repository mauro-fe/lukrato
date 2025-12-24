<?php

declare(strict_types=1);

/**
 * Bootstrap para testes PHPUnit
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Carrega variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Configura banco de dados em memória para testes
$capsule = new Capsule;

$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'prefix'   => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Cria schema das tabelas necessárias
Capsule::schema()->create('usuarios', function ($table) {
    $table->id();
    $table->string('nome');
    $table->string('email')->unique();
    $table->string('senha');
    $table->timestamps();
});

Capsule::schema()->create('contas', function ($table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->string('nome');
    $table->string('instituicao')->nullable();
    $table->string('moeda')->default('BRL');
    $table->decimal('saldo_inicial', 15, 2)->default(0);
    $table->boolean('ativo')->default(1);
    $table->timestamp('deleted_at')->nullable();
    $table->timestamps();
    
    $table->index('user_id');
});

Capsule::schema()->create('categorias', function ($table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('nome');
    $table->string('tipo'); // receita, despesa, ambas
    $table->string('icone')->nullable();
    $table->timestamps();
    
    $table->index('user_id');
    $table->index('tipo');
});

Capsule::schema()->create('lancamentos', function ($table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->date('data');
    $table->string('tipo'); // receita, despesa, transferencia
    $table->decimal('valor', 15, 2);
    $table->string('descricao', 190)->nullable();
    $table->text('observacao')->nullable();
    $table->unsignedBigInteger('categoria_id')->nullable();
    $table->unsignedBigInteger('conta_id')->nullable();
    $table->unsignedBigInteger('conta_id_destino')->nullable();
    $table->boolean('eh_transferencia')->default(0);
    $table->boolean('eh_saldo_inicial')->default(0);
    $table->timestamps();
    
    $table->index('user_id');
    $table->index('data');
    $table->index('tipo');
    $table->index('categoria_id');
    $table->index('conta_id');
});

Capsule::schema()->create('planos', function ($table) {
    $table->id();
    $table->string('code')->unique();
    $table->string('name');
    $table->decimal('price', 10, 2);
    $table->timestamps();
});

Capsule::schema()->create('assinaturas_usuarios', function ($table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('plano_id');
    $table->string('status');
    $table->timestamps();
    
    $table->index('user_id');
    $table->index('status');
});
