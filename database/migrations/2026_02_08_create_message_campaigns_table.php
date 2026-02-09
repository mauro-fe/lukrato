<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Cria tabela de campanhas de mensagens
 * 
 * Registra todas as campanhas de comunicação enviadas pelo sysadmin,
 * permitindo auditoria e histórico completo.
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('message_campaigns')) {
            $schema->create('message_campaigns', function (Blueprint $table) {
                $table->id();

                // Título e conteúdo da campanha
                $table->string('title', 255);
                $table->text('message');
                $table->string('link', 500)->nullable(); // CTA link
                $table->string('link_text', 100)->nullable(); // Texto do botão CTA

                // Tipo da campanha
                $table->enum('type', [
                    'info',      // Informações gerais
                    'promo',     // Promoções/Upsell
                    'update',    // Novidades/Atualizações
                    'alert',     // Alertas importantes
                    'success',   // Confirmações
                    'reminder'   // Lembretes
                ])->default('info');

                // Filtros aplicados (JSON)
                $table->json('filters')->nullable();
                /*
                 * Estrutura do JSON filters:
                 * {
                 *   "plan": "free|pro|all",
                 *   "status": "active|inactive|all",
                 *   "days_inactive": null|7|15|30|60,
                 *   "email_verified": true|false|null
                 * }
                 */

                // Canais de envio
                $table->boolean('send_notification')->default(true);
                $table->boolean('send_email')->default(false);

                // Estatísticas
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('emails_sent')->default(0);
                $table->unsignedInteger('emails_failed')->default(0);
                $table->unsignedInteger('notifications_read')->default(0);

                // Admin que criou
                $table->unsignedInteger('created_by');
                $table->foreign('created_by')->references('id')->on('usuarios');

                // Status da campanha
                $table->enum('status', [
                    'draft',      // Rascunho (para futuro)
                    'sending',    // Em processamento
                    'sent',       // Enviada
                    'failed',     // Falhou
                    'partial'     // Enviada parcialmente
                ])->default('sending');

                // Timestamps
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                // Índices
                $table->index('created_by');
                $table->index('status');
                $table->index('created_at');
            });

            echo "✅ Tabela 'message_campaigns' criada com sucesso\n";
        } else {
            echo "⚠️ Tabela 'message_campaigns' já existe\n";
        }

        // Adicionar foreign key na tabela notifications para campaign_id
        // (se a tabela notifications já existe)
        if ($schema->hasTable('notifications') && !$this->hasForeignKey('notifications', 'notifications_campaign_id_foreign')) {
            try {
                $schema->table('notifications', function (Blueprint $table) {
                    $table->foreign('campaign_id')
                        ->references('id')
                        ->on('message_campaigns')
                        ->nullOnDelete();
                });
                echo "✅ Foreign key 'campaign_id' adicionada à tabela 'notifications'\n";
            } catch (\Exception $e) {
                echo "⚠️ Não foi possível adicionar foreign key: " . $e->getMessage() . "\n";
            }
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        // Remover foreign key de notifications primeiro
        if ($schema->hasTable('notifications')) {
            try {
                $schema->table('notifications', function (Blueprint $table) {
                    $table->dropForeign(['campaign_id']);
                });
            } catch (\Exception $e) {
                // Ignorar se não existir
            }
        }

        if ($schema->hasTable('message_campaigns')) {
            $schema->dropIfExists('message_campaigns');
            echo "✅ Tabela 'message_campaigns' removida\n";
        }
    }

    private function hasForeignKey(string $table, string $keyName): bool
    {
        try {
            $connection = Capsule::connection();
            $result = $connection->select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = ? 
                 AND CONSTRAINT_NAME = ?",
                [$table, $keyName]
            );
            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
