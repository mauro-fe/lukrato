<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (!$schema->hasColumn('usuarios', 'email_verification_selector')) {
            DB::statement('ALTER TABLE usuarios ADD COLUMN email_verification_selector VARCHAR(32) NULL DEFAULT NULL AFTER email_verification_token');
        }

        if (!$schema->hasColumn('usuarios', 'email_verification_token_hash')) {
            DB::statement('ALTER TABLE usuarios ADD COLUMN email_verification_token_hash CHAR(64) NULL DEFAULT NULL AFTER email_verification_selector');
        }

        if (!$schema->hasColumn('usuarios', 'email_verification_expires_at')) {
            DB::statement('ALTER TABLE usuarios ADD COLUMN email_verification_expires_at TIMESTAMP NULL DEFAULT NULL AFTER email_verification_token_hash');
        }

        DB::statement("
            UPDATE usuarios
            SET email_verification_token_hash = COALESCE(email_verification_token_hash, SHA2(email_verification_token, 256))
            WHERE email_verification_token IS NOT NULL AND email_verification_token <> ''
        ");

        DB::statement("
            UPDATE usuarios
            SET email_verification_expires_at = COALESCE(
                email_verification_expires_at,
                DATE_ADD(email_verification_sent_at, INTERVAL 24 HOUR)
            )
            WHERE email_verification_sent_at IS NOT NULL
        ");

        DB::statement("
            UPDATE usuarios
            SET email_verification_token = NULL
            WHERE email_verification_token_hash IS NOT NULL
        ");

        try {
            DB::statement('CREATE UNIQUE INDEX idx_usuarios_email_verification_selector ON usuarios(email_verification_selector)');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('CREATE INDEX idx_usuarios_email_verification_token_hash ON usuarios(email_verification_token_hash)');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        try {
            DB::statement('DROP INDEX idx_usuarios_email_verification_selector ON usuarios');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('DROP INDEX idx_usuarios_email_verification_token_hash ON usuarios');
        } catch (\Throwable $e) {
        }

        if ($schema->hasColumn('usuarios', 'email_verification_expires_at')) {
            DB::statement('ALTER TABLE usuarios DROP COLUMN email_verification_expires_at');
        }

        if ($schema->hasColumn('usuarios', 'email_verification_token_hash')) {
            DB::statement('ALTER TABLE usuarios DROP COLUMN email_verification_token_hash');
        }

        if ($schema->hasColumn('usuarios', 'email_verification_selector')) {
            DB::statement('ALTER TABLE usuarios DROP COLUMN email_verification_selector');
        }
    }
};
