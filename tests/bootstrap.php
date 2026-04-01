<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$envFile = file_exists(BASE_PATH . '/.env.testing') ? '.env.testing' : '.env';
if (file_exists(BASE_PATH . '/' . $envFile)) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(BASE_PATH, $envFile);
    $dotenv->safeLoad();
}

if (!isset($_ENV['CPF_ENCRYPTION_KEY']) && !getenv('CPF_ENCRYPTION_KEY') && !isset($_ENV['APP_KEY']) && !getenv('APP_KEY')) {
    $_ENV['CPF_ENCRYPTION_KEY'] = 'base64:' . base64_encode(hash('sha256', 'lukrato-test-cpf-key', true));
}

date_default_timezone_set($_ENV['APP_TZ'] ?? 'America/Sao_Paulo');

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/lukrato/');
}

$testRuntimePath = BASE_PATH . '/tests/.runtime';
ensureDirectory($testRuntimePath);

$testStoragePath = $testRuntimePath . '/storage';
ensureDirectory($testStoragePath . '/cache');
$_ENV['STORAGE_PATH'] = $testStoragePath;
$_ENV['REDIS_ENABLED'] = 'false';

$testSessionPath = $testRuntimePath . '/sessions';
ensureDirectory($testSessionPath);
ini_set('session.save_path', $testSessionPath);

use Application\Lib\Auth;
use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

$connectionConfig = resolveTestConnectionConfig($testRuntimePath);

$capsule = new Capsule();
$capsule->addConnection($connectionConfig);
$capsule->setAsGlobal();
$capsule->bootEloquent();

if (($connectionConfig['driver'] ?? '') === 'sqlite') {
    ensureSqliteTestSchema($capsule);
}

Auth::setDefaultUserResolver(static function (int $userId): ?Usuario {
    $cached = $_SESSION['usuario_cache'] ?? null;

    if (($cached['id'] ?? null) === $userId && (($cached['data'] ?? null) instanceof Usuario)) {
        return $cached['data'];
    }

    return Usuario::find($userId);
});

/**
 * @return array<string, mixed>
 */
function resolveTestConnectionConfig(string $runtimePath): array
{
    $driver = strtolower(trim((string) ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'sqlite')));

    if ($driver === 'mysql') {
        return [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'database' => $_ENV['DB_NAME'] ?? '',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ];
    }

    $dbPath = $_ENV['TEST_DB_PATH'] ?? ($runtimePath . '/phpunit-db-' . getmypid() . '.sqlite');

    if (is_file($dbPath)) {
        @unlink($dbPath);
    }

    ensureDirectory(dirname($dbPath));
    touch($dbPath);

    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = $dbPath;

    return [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ];
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function ensureSqliteTestSchema(Capsule $capsule): void
{
    $schema = $capsule->schema();

    if (!$schema->hasTable('usuarios')) {
        $schema->create('usuarios', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->string('pending_email')->nullable();
            $table->string('avatar')->nullable();
            $table->decimal('avatar_focus_x', 5, 2)->nullable();
            $table->decimal('avatar_focus_y', 5, 2)->nullable();
            $table->decimal('avatar_zoom', 5, 2)->nullable();
            $table->string('senha')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->unsignedInteger('id_sexo')->nullable();
            $table->string('theme_preference')->nullable();
            $table->text('dashboard_preferences')->nullable();
            $table->string('external_customer_id')->nullable();
            $table->string('gateway')->nullable();
            $table->string('google_id')->nullable();
            $table->unsignedTinyInteger('is_admin')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->text('email_verification_token')->nullable();
            $table->string('email_verification_selector')->nullable();
            $table->string('email_verification_token_hash')->nullable();
            $table->timestamp('email_verification_expires_at')->nullable();
            $table->timestamp('email_verification_sent_at')->nullable();
            $table->timestamp('email_verification_reminder_sent_at')->nullable();
            $table->string('original_email_hash')->nullable();
            $table->string('registration_ip')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->string('support_code')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->boolean('telegram_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (!$schema->hasTable('sexos')) {
        $schema->create('sexos', static function (Blueprint $table): void {
            $table->increments('id_sexo');
            $table->string('nm_sexo')->unique();
        });
    }

    if (!$schema->hasTable('ddd')) {
        $schema->create('ddd', static function (Blueprint $table): void {
            $table->increments('id_ddd');
            $table->string('codigo')->unique();
        });
    }

    if (!$schema->hasTable('telefones')) {
        $schema->create('telefones', static function (Blueprint $table): void {
            $table->increments('id_telefone');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_ddd')->nullable();
            $table->string('numero');
            $table->string('tipo')->default('celular');
        });
    }

    if (!$schema->hasTable('tipos_documento')) {
        $schema->create('tipos_documento', static function (Blueprint $table): void {
            $table->increments('id_tipo');
            $table->string('ds_tipo')->unique();
        });
    }

    if (!$schema->hasTable('documentos')) {
        $schema->create('documentos', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_tipo');
            $table->string('numero')->nullable();
            $table->string('cpf_hash')->nullable();
            $table->text('cpf_encrypted')->nullable();
        });
    }

    if (!$schema->hasTable('planos')) {
        $schema->create('planos', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('code')->nullable();
            $table->string('nome')->nullable();
            $table->integer('preco_centavos')->nullable();
            $table->string('intervalo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->text('metadados')->nullable();
            $table->timestamps();
        });
    }

    if (!$schema->hasTable('assinaturas_usuarios')) {
        $schema->create('assinaturas_usuarios', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('plano_id')->nullable();
            $table->string('gateway')->nullable();
            $table->string('external_customer_id')->nullable();
            $table->string('external_subscription_id')->nullable();
            $table->string('external_payment_id')->nullable();
            $table->string('billing_type')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('renova_em')->nullable();
            $table->timestamp('cancelada_em')->nullable();
            $table->timestamps();
        });
    }

    if (!$schema->hasTable('password_resets')) {
        $schema->create('password_resets', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email');
            $table->string('selector')->nullable();
            $table->text('token')->nullable();
            $table->string('token_hash')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
        });
    }

    if (!$schema->hasTable('cupons')) {
        $schema->create('cupons', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('codigo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('limite_uso')->default(0);
            $table->integer('uso_atual')->default(0);
            $table->timestamp('valido_ate')->nullable();
            $table->timestamps();
        });
    }

    if (!$schema->hasTable('contas')) {
        $schema->create('contas', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('nome');
            $table->string('cor')->nullable();
            $table->string('instituicao')->nullable();
            $table->unsignedInteger('instituicao_financeira_id')->nullable();
            $table->string('tipo_conta')->nullable();
            $table->decimal('saldo_inicial', 14, 2)->default(0);
            $table->string('moeda')->nullable();
            $table->unsignedInteger('tipo_id')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (!$schema->hasTable('lancamentos')) {
        $schema->create('lancamentos', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('tipo')->nullable();
            $table->date('data')->nullable();
            $table->string('hora_lancamento')->nullable();
            $table->unsignedInteger('categoria_id')->nullable();
            $table->unsignedInteger('subcategoria_id')->nullable();
            $table->unsignedInteger('meta_id')->nullable();
            $table->string('meta_operacao')->nullable();
            $table->string('meta_valor')->nullable();
            $table->unsignedInteger('conta_id')->nullable();
            $table->unsignedInteger('conta_id_destino')->nullable();
            $table->string('descricao')->nullable();
            $table->text('observacao')->nullable();
            $table->string('valor')->default('0.00');
            $table->boolean('eh_transferencia')->default(false);
            $table->boolean('eh_saldo_inicial')->default(false);
            $table->unsignedInteger('cartao_credito_id')->nullable();
            $table->string('forma_pagamento')->nullable();
            $table->boolean('eh_parcelado')->default(false);
            $table->unsignedInteger('parcela_atual')->nullable();
            $table->unsignedInteger('total_parcelas')->nullable();
            $table->boolean('pago')->default(false);
            $table->date('data_pagamento')->nullable();
            $table->unsignedInteger('parcelamento_id')->nullable();
            $table->unsignedInteger('numero_parcela')->nullable();
            $table->date('data_competencia')->nullable();
            $table->boolean('afeta_competencia')->default(true);
            $table->boolean('afeta_caixa')->default(true);
            $table->string('origem_tipo')->nullable();
            $table->boolean('recorrente')->default(false);
            $table->string('recorrencia_freq')->nullable();
            $table->date('recorrencia_fim')->nullable();
            $table->unsignedInteger('recorrencia_total')->nullable();
            $table->unsignedInteger('recorrencia_pai_id')->nullable();
            $table->timestamp('cancelado_em')->nullable();
            $table->unsignedInteger('lembrar_antes_segundos')->nullable();
            $table->boolean('canal_email')->default(false);
            $table->boolean('canal_inapp')->default(true);
            $table->timestamp('notificado_em')->nullable();
            $table->timestamp('lembrete_antecedencia_em')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (!$schema->hasTable('pending_ai_actions')) {
        $schema->create('pending_ai_actions', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conversation_id')->nullable();
            $table->string('action_type');
            $table->text('payload')->nullable();
            $table->string('status')->default('awaiting_confirm');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (!$schema->hasTable('blog_categorias')) {
        $schema->create('blog_categorias', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nome');
            $table->string('slug');
            $table->string('icone')->nullable();
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    if (!$schema->hasTable('ai_logs')) {
        $schema->create('ai_logs', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('channel', 20)->nullable();
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('tokens_prompt')->nullable();
            $table->unsignedInteger('tokens_completion')->nullable();
            $table->unsignedInteger('tokens_total')->nullable();
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->string('source', 20)->nullable();
            $table->float('confidence')->nullable();
            $table->string('prompt_version', 20)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}
