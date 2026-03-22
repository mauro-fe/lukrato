<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('user_onboarding_progress')) {
            return;
        }

        $schema->create('user_onboarding_progress', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            $table->boolean('has_conta')->default(false);
            $table->boolean('has_lancamento')->default(false);
            $table->timestamp('first_lancamento_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('usuarios')
                ->cascadeOnDelete();

            $table->index(['has_conta', 'has_lancamento'], 'idx_onboarding_progress_flags');
            $table->index('onboarding_completed_at', 'idx_onboarding_progress_completed_at');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('user_onboarding_progress');
    }
};
