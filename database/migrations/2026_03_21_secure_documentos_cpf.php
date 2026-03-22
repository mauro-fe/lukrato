<?php

declare(strict_types=1);

use Application\Services\Infrastructure\CpfProtectionService;
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (!$schema->hasColumn('documentos', 'cpf_hash')) {
            DB::statement('ALTER TABLE documentos ADD COLUMN cpf_hash CHAR(64) NULL DEFAULT NULL AFTER numero');
        }

        if (!$schema->hasColumn('documentos', 'cpf_encrypted')) {
            DB::statement('ALTER TABLE documentos ADD COLUMN cpf_encrypted TEXT NULL DEFAULT NULL AFTER cpf_hash');
        }

        DB::statement('ALTER TABLE documentos MODIFY numero VARCHAR(32) NULL');

        try {
            DB::statement('CREATE INDEX idx_documentos_tipo_cpf_hash ON documentos(id_tipo, cpf_hash)');
        } catch (\Throwable $e) {
        }

        $tipoCpfIds = DB::table('tipos_documento')
            ->where('ds_tipo', 'CPF')
            ->pluck('id_tipo')
            ->all();

        if ($tipoCpfIds === []) {
            return;
        }

        $protection = new CpfProtectionService();

        DB::table('documentos')
            ->select(['id', 'numero', 'cpf_hash', 'cpf_encrypted'])
            ->whereIn('id_tipo', $tipoCpfIds)
            ->orderBy('id')
            ->get()
            ->each(function ($documento) use ($protection): void {
                $legacyCpf = $protection->normalize((string) ($documento->numero ?? ''));
                $existingEncrypted = (string) ($documento->cpf_encrypted ?? '');
                $existingHash = (string) ($documento->cpf_hash ?? '');

                if ($existingEncrypted !== '' && $existingHash !== '') {
                    if ($legacyCpf !== '') {
                        DB::table('documentos')
                            ->where('id', $documento->id)
                            ->update(['numero' => null]);
                    }

                    return;
                }

                if ($legacyCpf === '') {
                    return;
                }

                DB::table('documentos')
                    ->where('id', $documento->id)
                    ->update([
                        'cpf_hash' => $protection->hash($legacyCpf),
                        'cpf_encrypted' => $protection->encrypt($legacyCpf),
                        'numero' => null,
                    ]);
            });
    }

    public function down(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        try {
            DB::statement('DROP INDEX idx_documentos_tipo_cpf_hash ON documentos');
        } catch (\Throwable $e) {
        }

        if ($schema->hasColumn('documentos', 'cpf_encrypted')) {
            DB::statement('ALTER TABLE documentos DROP COLUMN cpf_encrypted');
        }

        if ($schema->hasColumn('documentos', 'cpf_hash')) {
            DB::statement('ALTER TABLE documentos DROP COLUMN cpf_hash');
        }

        DB::statement("UPDATE documentos SET numero = '' WHERE numero IS NULL");
        DB::statement('ALTER TABLE documentos MODIFY numero VARCHAR(32) NOT NULL');
    }
};
