<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Migração para adicionar campos de verificação de email na tabela usuarios
 * 
 * Campos adicionados:
 * - email_verified_at: timestamp de quando o email foi verificado
 * - email_verification_token: token único para verificação do email
 * - email_verification_sent_at: timestamp do último envio do email de verificação
 */

return new class {
    public function up(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (!$schema->hasColumn('usuarios', 'email_verified_at')) {
            DB::statement('ALTER TABLE usuarios ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER email');
        }

        if (!$schema->hasColumn('usuarios', 'email_verification_token')) {
            DB::statement('ALTER TABLE usuarios ADD COLUMN email_verification_token VARCHAR(64) NULL DEFAULT NULL AFTER email_verified_at');
        }

        if (!$schema->hasColumn('usuarios', 'email_verification_sent_at')) {
            DB::statement('ALTER TABLE usuarios ADD COLUMN email_verification_sent_at TIMESTAMP NULL DEFAULT NULL AFTER email_verification_token');
        }

        // Índice para busca por token
        try {
            DB::statement('CREATE INDEX idx_usuarios_email_verification_token ON usuarios(email_verification_token)');
        } catch (\Exception $e) {
            // Índice pode já existir
        }

        echo "✅ Campos de verificação de email adicionados à tabela usuarios\n";
    }

    public function down(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        try {
            DB::statement('DROP INDEX idx_usuarios_email_verification_token ON usuarios');
        } catch (\Exception $e) {
            // Índice pode não existir
        }

        if ($schema->hasColumn('usuarios', 'email_verification_sent_at')) {
            DB::statement('ALTER TABLE usuarios DROP COLUMN email_verification_sent_at');
        }

        if ($schema->hasColumn('usuarios', 'email_verification_token')) {
            DB::statement('ALTER TABLE usuarios DROP COLUMN email_verification_token');
        }

        if ($schema->hasColumn('usuarios', 'email_verified_at')) {
            DB::statement('ALTER TABLE usuarios DROP COLUMN email_verified_at');
        }

        echo "✅ Campos de verificação de email removidos da tabela usuarios\n";
    }
};
