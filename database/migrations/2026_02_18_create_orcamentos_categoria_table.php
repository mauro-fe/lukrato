<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('orcamentos_categoria')) {
            $schema->create('orcamentos_categoria', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('categoria_id');
                $table->decimal('valor_limite', 12, 2);
                $table->tinyInteger('mes');
                $table->smallInteger('ano');
                $table->boolean('rollover')->default(false);
                $table->boolean('alerta_80')->default(true);
                $table->boolean('alerta_100')->default(true);
                $table->boolean('notificado_80')->default(false);
                $table->boolean('notificado_100')->default(false);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('usuarios')->cascadeOnDelete();
                $table->foreign('categoria_id')->references('id')->on('categorias')->cascadeOnDelete();
                $table->unique(['user_id', 'categoria_id', 'mes', 'ano']);
                $table->index(['user_id', 'mes', 'ano']);
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('orcamentos_categoria');
    }
};
