<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Cria tabela de rastreamento anti-fraude para indicações
 * 
 * Protege contra:
 * - Usuários que excluem conta e recriam para ganhar indicação novamente
 * - Múltiplas contas do mesmo IP para auto-indicação
 * - Abuso do sistema de indicações
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        // Tabela para rastrear emails e IPs de contas excluídas
        if (!$schema->hasTable('referral_antifraud_tracking')) {
            $schema->create('referral_antifraud_tracking', function (Blueprint $table) {
                $table->id();

                // Email original antes da anonimização
                $table->string('email_hash', 64)->index(); // SHA256 do email
                $table->string('email_domain', 100)->nullable(); // Domínio para detectar padrões

                // IP tracking
                $table->string('ip_address', 45)->nullable()->index(); // IPv4 ou IPv6
                $table->string('ip_hash', 64)->nullable(); // Hash do IP para privacidade

                // Fingerprint do browser (opcional)
                $table->string('fingerprint_hash', 64)->nullable()->index();

                // Identificador original do usuário
                $table->unsignedInteger('original_user_id');

                // Tipo de evento
                $table->enum('event_type', [
                    'account_created',
                    'account_deleted',
                    'referral_used',
                    'referral_given'
                ])->default('account_created');

                // Se foi indicado por alguém
                $table->unsignedInteger('referred_by')->nullable();

                // Quarentena - data até quando está bloqueado
                $table->timestamp('quarantine_until')->nullable();

                // Metadados adicionais
                $table->json('metadata')->nullable();

                // Timestamps
                $table->timestamps();

                // Índices compostos para buscas rápidas
                $table->index(['email_hash', 'event_type']);
                $table->index(['ip_address', 'created_at']);
                $table->index(['quarantine_until']);
            });
        }

        // Adiciona campo para guardar email original hash na tabela usuarios
        if (!$schema->hasColumn('usuarios', 'original_email_hash')) {
            $schema->table('usuarios', function (Blueprint $table) {
                $table->string('original_email_hash', 64)->nullable()->after('email');
                $table->string('registration_ip', 45)->nullable()->after('original_email_hash');
                $table->string('last_login_ip', 45)->nullable()->after('registration_ip');
            });
        }

        // Adiciona limite de indicações na tabela indicacoes
        if (!$schema->hasColumn('indicacoes', 'blocked_reason')) {
            $schema->table('indicacoes', function (Blueprint $table) {
                $table->string('blocked_reason', 255)->nullable()->after('status');
                $table->string('ip_address', 45)->nullable()->after('blocked_reason');
            });
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        $schema->dropIfExists('referral_antifraud_tracking');

        if ($schema->hasColumn('usuarios', 'original_email_hash')) {
            $schema->table('usuarios', function (Blueprint $table) {
                $table->dropColumn(['original_email_hash', 'registration_ip', 'last_login_ip']);
            });
        }

        if ($schema->hasColumn('indicacoes', 'blocked_reason')) {
            $schema->table('indicacoes', function (Blueprint $table) {
                $table->dropColumn(['blocked_reason', 'ip_address']);
            });
        }
    }
};
