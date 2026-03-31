<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (!$schema->hasColumn('usuarios', 'pending_email')) {
            DB::statement('ALTER TABLE usuarios ADD COLUMN pending_email VARCHAR(255) NULL DEFAULT NULL AFTER email');
        }

        try {
            DB::statement('CREATE UNIQUE INDEX idx_usuarios_pending_email_unique ON usuarios(pending_email)');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        try {
            DB::statement('DROP INDEX idx_usuarios_pending_email_unique ON usuarios');
        } catch (\Throwable $e) {
        }

        if ($schema->hasColumn('usuarios', 'pending_email')) {
            DB::statement('ALTER TABLE usuarios DROP COLUMN pending_email');
        }
    }
};
