<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (!$schema->hasColumn('password_resets', 'selector')) {
            DB::statement('ALTER TABLE password_resets ADD COLUMN selector VARCHAR(32) NULL DEFAULT NULL AFTER email');
        }

        if (!$schema->hasColumn('password_resets', 'token_hash')) {
            DB::statement('ALTER TABLE password_resets ADD COLUMN token_hash CHAR(64) NULL DEFAULT NULL AFTER selector');
        }

        // Schema legado mantinha token como NOT NULL + UNIQUE.
        // Primeiro abrimos esse contrato para permitir limpar o plaintext sem violar a constraint antiga.
        try {
            DB::statement('DROP INDEX uq_password_resets_token ON password_resets');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE password_resets MODIFY token VARCHAR(64) NULL DEFAULT NULL');
        } catch (\Throwable $e) {
        }

        DB::statement("
            UPDATE password_resets
            SET token_hash = COALESCE(token_hash, SHA2(token, 256))
            WHERE token IS NOT NULL AND token <> ''
        ");

        DB::statement("
            UPDATE password_resets
            SET token = NULL
            WHERE token_hash IS NOT NULL
        ");

        try {
            DB::statement('CREATE UNIQUE INDEX idx_password_resets_selector ON password_resets(selector)');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('CREATE INDEX idx_password_resets_token_hash ON password_resets(token_hash)');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        try {
            DB::statement('DROP INDEX idx_password_resets_selector ON password_resets');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('DROP INDEX idx_password_resets_token_hash ON password_resets');
        } catch (\Throwable $e) {
        }

        if ($schema->hasColumn('password_resets', 'token_hash')) {
            DB::statement('ALTER TABLE password_resets DROP COLUMN token_hash');
        }

        if ($schema->hasColumn('password_resets', 'selector')) {
            DB::statement('ALTER TABLE password_resets DROP COLUMN selector');
        }
    }
};
