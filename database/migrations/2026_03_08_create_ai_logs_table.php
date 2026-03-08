<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('ai_logs')) {
            echo "• Tabela ai_logs já existe\n";
            return;
        }

        Capsule::schema()->create('ai_logs', function ($table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->enum('type', ['chat', 'suggest_category', 'analyze_spending'])->index();

            $table->text('prompt');
            $table->mediumText('response')->nullable();

            $table->string('provider', 20)->default('openai');
            $table->string('model', 50)->nullable();

            $table->unsignedInteger('tokens_prompt')->nullable();
            $table->unsignedInteger('tokens_completion')->nullable();
            $table->unsignedInteger('tokens_total')->nullable();

            $table->unsignedInteger('response_time_ms')->default(0);

            $table->boolean('success')->default(true);
            $table->string('error_message', 500)->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            // Índices compostos
            $table->index(['type', 'created_at']);
            $table->index(['success', 'created_at']);
        });

        echo "✅ Tabela ai_logs criada com sucesso\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('ai_logs');
        echo "✅ Tabela ai_logs removida\n";
    }
};
