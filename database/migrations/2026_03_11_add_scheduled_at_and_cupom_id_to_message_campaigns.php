<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Adiciona campos scheduled_at e cupom_id à tabela message_campaigns
 * 
 * - scheduled_at: permite agendar campanhas para envio futuro
 * - cupom_id: permite vincular um cupom promocional à campanha
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('message_campaigns')) {
            echo "⚠️ Tabela 'message_campaigns' não existe\n";
            return;
        }

        $schema->table('message_campaigns', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('message_campaigns', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            }

            if (!$schema->hasColumn('message_campaigns', 'cupom_id')) {
                $table->unsignedInteger('cupom_id')->nullable()->after('created_by');
            }
        });

        // Adicionar foreign key para cupom_id separadamente (evitar erro se já existe)
        if ($schema->hasColumn('message_campaigns', 'cupom_id') && $schema->hasTable('cupons')) {
            try {
                $schema->table('message_campaigns', function (Blueprint $table) {
                    $table->foreign('cupom_id')
                        ->references('id')
                        ->on('cupons')
                        ->nullOnDelete();
                });
                echo "✅ Foreign key 'cupom_id' adicionada\n";
            } catch (\Exception $e) {
                echo "⚠️ Foreign key cupom_id já existe ou erro: " . $e->getMessage() . "\n";
            }
        }

        // Adicionar índice para scheduled_at (usado pelo scheduler)
        try {
            $schema->table('message_campaigns', function (Blueprint $table) {
                $table->index(['status', 'scheduled_at'], 'idx_campaigns_scheduled');
            });
            echo "✅ Índice 'idx_campaigns_scheduled' criado\n";
        } catch (\Exception $e) {
            echo "⚠️ Índice já existe: " . $e->getMessage() . "\n";
        }

        echo "✅ Campos 'scheduled_at' e 'cupom_id' adicionados à tabela 'message_campaigns'\n";
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('message_campaigns')) {
            return;
        }

        $schema->table('message_campaigns', function (Blueprint $table) use ($schema) {
            // Remover foreign key e índice antes das colunas
            try {
                $table->dropForeign(['cupom_id']);
            } catch (\Exception $e) {
                // Ignora se não existe
            }

            try {
                $table->dropIndex('idx_campaigns_scheduled');
            } catch (\Exception $e) {
                // Ignora se não existe
            }

            if ($schema->hasColumn('message_campaigns', 'scheduled_at')) {
                $table->dropColumn('scheduled_at');
            }

            if ($schema->hasColumn('message_campaigns', 'cupom_id')) {
                $table->dropColumn('cupom_id');
            }
        });

        echo "✅ Campos 'scheduled_at' e 'cupom_id' removidos\n";
    }
};
