<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Cria tabela de notificações do sistema
 * 
 * Armazena notificações individuais por usuário, permitindo
 * comunicações direcionadas do sysadmin.
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('notifications')) {
            $schema->create('notifications', function (Blueprint $table) {
                $table->id();

                // Usuário destinatário
                $table->unsignedInteger('user_id');
                $table->foreign('user_id')->references('id')->on('usuarios')->cascadeOnDelete();

                // Conteúdo da notificação
                $table->string('title', 255);
                $table->text('message');
                $table->string('link', 500)->nullable(); // Link opcional (CTA)

                // Tipo de notificação para ícones/estilos diferentes
                $table->enum('type', [
                    'info',      // Informações gerais
                    'promo',     // Promoções/Upsell
                    'update',    // Novidades/Atualizações
                    'alert',     // Alertas importantes
                    'success',   // Confirmações/Sucessos
                    'reminder'   // Lembretes
                ])->default('info');

                // Status de leitura
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();

                // Referência à campanha (se veio de uma campanha)
                $table->unsignedBigInteger('campaign_id')->nullable();

                // Timestamps
                $table->timestamps();

                // Índices para performance
                $table->index('user_id');
                $table->index(['user_id', 'is_read']);
                $table->index('campaign_id');
                $table->index('created_at');
            });

            echo "✅ Tabela 'notifications' criada com sucesso\n";
        } else {
            echo "⚠️ Tabela 'notifications' já existe\n";
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('notifications')) {
            $schema->dropIfExists('notifications');
            echo "✅ Tabela 'notifications' removida\n";
        }
    }
};
