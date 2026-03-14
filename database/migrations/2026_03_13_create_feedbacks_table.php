<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('feedbacks')) {
            echo "* Tabela feedbacks ja existe\n";
            return;
        }

        $schema->create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->enum('tipo_feedback', ['acao', 'assistente_ia', 'nps', 'sugestao']);
            $table->string('contexto', 100)->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->text('comentario')->nullable();
            $table->string('pagina', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'tipo_feedback', 'created_at'], 'idx_fb_user_tipo_date');
            $table->index(['user_id', 'tipo_feedback', 'contexto', 'created_at'], 'idx_fb_user_tipo_ctx_date');
            $table->index(['tipo_feedback', 'created_at'], 'idx_fb_tipo_date');

            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
        });

        echo "OK Tabela feedbacks criada com sucesso\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('feedbacks');
        echo "OK Tabela feedbacks removida\n";
    }
};
