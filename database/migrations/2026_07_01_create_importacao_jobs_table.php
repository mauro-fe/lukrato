<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('importacao_jobs')) {
            return;
        }

        $schema->create('importacao_jobs', static function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('conta_id');
            $table->unsignedInteger('cartao_id')->nullable();
            $table->string('source_type', 20)->default('ofx');
            $table->string('import_target', 20)->default('conta');
            $table->string('filename', 255)->nullable();
            $table->string('temp_file_path', 500);
            $table->string('status', 30)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('result_batch_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->text('meta_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_importacao_job_user_status');
            $table->index(['status', 'id'], 'idx_importacao_job_status_id');
            $table->index(['result_batch_id'], 'idx_importacao_job_result_batch');
        });

        echo "  - Tabela importacao_jobs criada\n";
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('importacao_jobs')) {
            $schema->drop('importacao_jobs');
        }
    }
};
