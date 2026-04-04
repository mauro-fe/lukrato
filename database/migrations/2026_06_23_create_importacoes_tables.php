<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('importacao_perfis')) {
            $schema->create('importacao_perfis', static function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('conta_id');
                $table->string('source_type', 20)->default('ofx');
                $table->string('label', 100)->nullable();
                $table->string('agencia', 40)->nullable();
                $table->string('numero_conta', 60)->nullable();
                $table->text('options_json')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'conta_id'], 'uq_importacao_perfil_user_conta');
                $table->index(['user_id', 'source_type'], 'idx_importacao_perfil_user_source');
            });

            echo "  - Tabela importacao_perfis criada\n";
        }

        if (!$schema->hasTable('importacao_lotes')) {
            $schema->create('importacao_lotes', static function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('conta_id');
                $table->string('source_type', 20);
                $table->string('filename', 255)->nullable();
                $table->string('file_hash', 64)->nullable();
                $table->string('status', 40)->default('processing');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('imported_rows')->default(0);
                $table->unsignedInteger('duplicate_rows')->default(0);
                $table->unsignedInteger('error_rows')->default(0);
                $table->text('error_summary')->nullable();
                $table->text('meta_json')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at'], 'idx_importacao_lote_user_created');
                $table->index(['user_id', 'status'], 'idx_importacao_lote_user_status');
                $table->index(['conta_id'], 'idx_importacao_lote_conta');
            });

            echo "  - Tabela importacao_lotes criada\n";
        }

        if (!$schema->hasTable('importacao_itens')) {
            $schema->create('importacao_itens', static function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('lote_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('conta_id');
                $table->unsignedInteger('lancamento_id')->nullable();
                $table->string('row_hash', 64);
                $table->string('status', 30)->default('imported');
                $table->string('external_id', 120)->nullable();
                $table->date('data');
                $table->decimal('amount', 14, 2);
                $table->string('tipo', 20);
                $table->string('description', 190);
                $table->text('memo')->nullable();
                $table->text('raw_json')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();

                $table->index(['lote_id'], 'idx_importacao_item_lote');
                $table->index(['user_id', 'conta_id'], 'idx_importacao_item_user_conta');
                $table->index(['lancamento_id'], 'idx_importacao_item_lancamento');
                $table->unique(['user_id', 'conta_id', 'row_hash'], 'uq_importacao_item_user_conta_hash');
            });

            echo "  - Tabela importacao_itens criada\n";
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('importacao_itens')) {
            $schema->drop('importacao_itens');
        }

        if ($schema->hasTable('importacao_lotes')) {
            $schema->drop('importacao_lotes');
        }

        if ($schema->hasTable('importacao_perfis')) {
            $schema->drop('importacao_perfis');
        }
    }
};

