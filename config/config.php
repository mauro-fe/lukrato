<?php

/**
 * Configurações da Aplicação
 *
 * Este arquivo centraliza todas as configurações principais da aplicação,
 * incluindo banco de dados, caminhos e configurações do Eloquent ORM.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

// ============================================================================
// CONSTANTES DE CONFIGURAÇÃO
// ============================================================================

defineApplicationConstants();
defineDatabaseConstants();
definePathConstants();

// ============================================================================
// CONFIGURAÇÃO DO BANCO DE DADOS
// ============================================================================

configureDatabaseConnection();

// ============================================================================
// FUNÇÕES DE CONFIGURAÇÃO
// ============================================================================

/**
 * Define constantes gerais da aplicação
 */
function defineApplicationConstants(): void
{
    if (!defined('APP_NAME')) {
        define('APP_NAME', $_ENV['APP_NAME'] ?? 'Anamnese Pro');
    }

    if (!defined('BASE_URL')) {
        define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost/lukrato/public/');
    }
}

/**
 * Define constantes de configuração do banco de dados
 */
function defineDatabaseConstants(): void
{
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'lukrato');
}

/**
 * Define constantes de caminhos da aplicação
 */
function definePathConstants(): void
{
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__));
    }

    if (!defined('VIEW_PATH')) {
        define('VIEW_PATH', BASE_PATH . '/views');
    }
}

/**
 * Configura e inicializa a conexão com o banco de dados usando Eloquent
 */
function configureDatabaseConnection(): void
{
    $capsule = new Capsule;

    // Configuração da conexão principal
    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => DB_HOST,
        'database'  => DB_NAME,
        'username'  => DB_USER,
        'password'  => DB_PASSWORD,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'options'   => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ]);


    // Inicializar o Eloquent ORM
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}
