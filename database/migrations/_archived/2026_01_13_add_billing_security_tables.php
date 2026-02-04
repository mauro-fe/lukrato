<?php

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    /**
     * Migration para adicionar segurança em cobrança:
     * - Tabela de idempotência de webhooks
     * - Índices para performance
     * - Lock otimista para assinaturas
     */
    public function up(): void
    {
        $schema = DB::schema();

        // ✅ Tabela para idempotência de webhooks (evitar processamento duplicado)
        if (!$schema->hasTable('webhook_idempotencia')) {
            $schema->create('webhook_idempotencia', function ($table) {
                $table->id();
                $table->string('idempotency_key', 64)->unique()->comment('Chave única MD5 do evento');
                $table->string('event_type', 100)->comment('Tipo do evento (PAYMENT_RECEIVED, etc)');
                $table->string('payload_hash', 64)->comment('Hash SHA256 do payload');
                $table->timestamp('processed_at')->comment('Quando foi processado');
                $table->timestamps();

                $table->index('event_type');
                $table->index('processed_at');
            });

            echo "✅ Tabela webhook_idempotencia criada\n";
        }

        // ✅ Adicionar índices críticos para performance
        if ($schema->hasTable('assinaturas_usuarios')) {
            $schema->table('assinaturas_usuarios', function ($table) {
                // Índice composto para busca de assinaturas ativas
                if (!$this->indexExists('assinaturas_usuarios', 'idx_user_status_gateway')) {
                    $table->index(['user_id', 'status', 'gateway'], 'idx_user_status_gateway');
                }

                // Índice para busca por external IDs
                if (!$this->indexExists('assinaturas_usuarios', 'idx_external_subscription')) {
                    $table->index('external_subscription_id', 'idx_external_subscription');
                }

                if (!$this->indexExists('assinaturas_usuarios', 'idx_external_customer')) {
                    $table->index('external_customer_id', 'idx_external_customer');
                }

                // Índice para busca por status
                if (!$this->indexExists('assinaturas_usuarios', 'idx_status_renova')) {
                    $table->index(['status', 'renova_em'], 'idx_status_renova');
                }
            });

            echo "✅ Índices adicionados em assinaturas_usuarios\n";
        }

        // ✅ Adicionar version para lock otimista
        if ($schema->hasTable('assinaturas_usuarios') && !$schema->hasColumn('assinaturas_usuarios', 'version')) {
            $schema->table('assinaturas_usuarios', function ($table) {
                $table->integer('version')->default(0)->comment('Versão para lock otimista');
            });

            echo "✅ Coluna version adicionada para lock otimista\n";
        }

        // ✅ Índices na tabela de usuários
        if ($schema->hasTable('usuarios')) {
            $schema->table('usuarios', function ($table) {
                if (!$this->indexExists('usuarios', 'idx_email_unique')) {
                    $table->unique('email', 'idx_email_unique');
                }

                if (!$this->indexExists('usuarios', 'idx_external_customer')) {
                    $table->index('external_customer_id', 'idx_external_customer');
                }
            });

            echo "✅ Índices adicionados em usuarios\n";
        }

        // ✅ Tabela de auditoria financeira
        if (!$schema->hasTable('auditoria_cobrancas')) {
            $schema->create('auditoria_cobrancas', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('assinatura_id')->nullable()->index();
                $table->string('action', 50)->comment('checkout, cancel, update, webhook');
                $table->string('status_anterior', 50)->nullable();
                $table->string('status_novo', 50)->nullable();
                $table->string('external_id', 100)->nullable()->comment('ID do Asaas');
                $table->decimal('valor', 10, 2)->nullable();
                $table->json('metadata')->nullable()->comment('Dados adicionais');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at');

                $table->index(['user_id', 'created_at']);
                $table->index('action');
                $table->index('external_id');
            });

            echo "✅ Tabela auditoria_cobrancas criada\n";
        }

        // ✅ Tabela para detectar cobranças duplicadas
        if (!$schema->hasTable('cobrancas_duplicadas')) {
            $schema->create('cobrancas_duplicadas', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('external_id', 100)->index()->comment('ID da cobrança duplicada');
                $table->decimal('valor', 10, 2);
                $table->string('status', 50);
                $table->text('detalhes')->nullable();
                $table->boolean('estornado')->default(false);
                $table->timestamp('detectado_em');
                $table->timestamp('resolvido_em')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'detectado_em']);
                $table->index('estornado');
            });

            echo "✅ Tabela cobrancas_duplicadas criada\n";
        }

        echo "✅ Migration de segurança de cobrança concluída!\n";
    }

    public function down(): void
    {
        $schema = DB::schema();

        $schema->dropIfExists('cobrancas_duplicadas');
        $schema->dropIfExists('auditoria_cobrancas');
        $schema->dropIfExists('webhook_idempotencia');

        if ($schema->hasTable('assinaturas_usuarios')) {
            $schema->table('assinaturas_usuarios', function ($table) {
                $table->dropColumn('version');
            });
        }

        echo "✅ Rollback concluído\n";
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
