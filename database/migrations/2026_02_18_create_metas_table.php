<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('metas')) {
            $schema->create('metas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('titulo', 150);
                $table->text('descricao')->nullable();
                $table->string('tipo', 30)->default('economia');
                $table->decimal('valor_alvo', 12, 2);
                $table->decimal('valor_atual', 12, 2)->default(0);
                $table->date('data_inicio');
                $table->date('data_prazo')->nullable();
                $table->string('icone', 50)->default('fa-bullseye');
                $table->string('cor', 20)->default('#6366f1');
                $table->unsignedInteger('conta_id')->nullable();
                $table->string('prioridade', 15)->default('media');
                $table->string('status', 20)->default('ativa');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('usuarios')->cascadeOnDelete();
                $table->foreign('conta_id')->references('id')->on('contas')->nullOnDelete();
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('metas');
    }
};
