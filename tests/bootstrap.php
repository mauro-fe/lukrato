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

    if (!$schema->hasTable('cartoes_credito')) {
        $schema->create('cartoes_credito', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conta_id')->nullable();
            $table->string('nome_cartao');
            $table->string('bandeira')->nullable();
            $table->string('ultimos_digitos', 8)->nullable();
            $table->decimal('limite_total', 14, 2)->default(0);
            $table->decimal('limite_disponivel', 14, 2)->default(0);
            $table->unsignedTinyInteger('dia_vencimento')->nullable();
            $table->unsignedTinyInteger('dia_fechamento')->nullable();
            $table->string('cor_cartao', 20)->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('arquivado')->default(false);
            $table->unsignedInteger('lembrar_fatura_antes_segundos')->nullable();
            $table->boolean('fatura_canal_email')->default(false);
            $table->boolean('fatura_canal_inapp')->default(true);
            $table->string('fatura_notificado_mes', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (!$schema->hasTable('faturas')) {
        $schema->create('faturas', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('cartao_credito_id');
            $table->string('descricao', 120)->nullable();
            $table->decimal('valor_total', 14, 2)->default(0);
            $table->unsignedInteger('numero_parcelas')->default(0);
            $table->date('data_compra')->nullable();
            $table->string('status', 30)->default('pendente');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (!$schema->hasTable('faturas_cartao_itens')) {
        $schema->create('faturas_cartao_itens', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('cartao_credito_id');
            $table->unsignedInteger('fatura_id')->nullable();
            $table->unsignedInteger('lancamento_id')->nullable();
            $table->string('descricao', 190);
            $table->decimal('valor', 14, 2)->default(0);
            $table->string('tipo', 20)->default('despesa');
            $table->date('data_compra')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->unsignedTinyInteger('mes_referencia')->nullable();
            $table->unsignedSmallInteger('ano_referencia')->nullable();
            $table->unsignedInteger('categoria_id')->nullable();
            $table->boolean('eh_parcelado')->default(false);
            $table->unsignedInteger('parcela_atual')->default(1);
            $table->unsignedInteger('total_parcelas')->default(1);
            $table->unsignedInteger('item_pai_id')->nullable();
            $table->boolean('pago')->default(false);
            $table->date('data_pagamento')->nullable();
            $table->boolean('recorrente')->default(false);
            $table->string('recorrencia_freq', 20)->nullable();
            $table->date('recorrencia_fim')->nullable();
            $table->unsignedInteger('recorrencia_pai_id')->nullable();
            $table->timestamp('cancelado_em')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (!$schema->hasTable('importacao_perfis')) {
        $schema->create('importacao_perfis', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conta_id');
            $table->string('source_type', 20)->default('ofx');
            $table->string('label', 100)->nullable();
            $table->string('agencia', 40)->nullable();
            $table->string('numero_conta', 60)->nullable();
            $table->text('options_json')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'conta_id'], 'uq_importacao_perfil_user_conta');
            $table->index(['user_id', 'source_type'], 'idx_importacao_perfil_user_source');
        });
    }

    if (!$schema->hasTable('importacao_lotes')) {
        $schema->create('importacao_lotes', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conta_id');
            $table->string('source_type', 20);
            $table->string('filename', 255)->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->string('status', 40)->default('processing');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->text('error_summary')->nullable();
            $table->text('meta_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_importacao_lote_user_created');
            $table->index(['user_id', 'status'], 'idx_importacao_lote_user_status');
            $table->index(['conta_id'], 'idx_importacao_lote_conta');
        });
    }

    if (!$schema->hasTable('importacao_itens')) {
        $schema->create('importacao_itens', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('lote_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conta_id');
            $table->unsignedInteger('lancamento_id')->nullable();
            $table->string('row_hash', 64);
            $table->string('status', 30)->default('imported');
            $table->string('external_id', 120)->nullable();
            $table->date('data');
            $table->decimal('amount', 14, 2);
            $table->string('tipo', 20);
            $table->string('description', 190);
            $table->text('memo')->nullable();
            $table->text('raw_json')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['lote_id'], 'idx_importacao_item_lote');
            $table->index(['user_id', 'conta_id'], 'idx_importacao_item_user_conta');
            $table->index(['lancamento_id'], 'idx_importacao_item_lancamento');
            $table->unique(['user_id', 'conta_id', 'row_hash'], 'uq_importacao_item_user_conta_hash');
        });
    }

    if (!$schema->hasTable('importacao_jobs')) {
        $schema->create('importacao_jobs', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conta_id');
            $table->unsignedInteger('cartao_id')->nullable();
            $table->string('source_type', 20)->default('ofx');
            $table->string('import_target', 20)->default('conta');
            $table->string('filename', 255)->nullable();
            $table->string('temp_file_path', 500);
            $table->string('status', 30)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('result_batch_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->text('meta_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_importacao_job_user_status');
            $table->index(['status', 'id'], 'idx_importacao_job_status_id');
            $table->index(['result_batch_id'], 'idx_importacao_job_result_batch');
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
