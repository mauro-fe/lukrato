<?php

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up()
    {
        DB::schema()->create('parcelamentos', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('descricao');
            $table->decimal('valor_total', 10, 2);
            $table->integer('numero_parcelas');
            $table->integer('parcelas_pagas')->default(0);
            $table->unsignedInteger('categoria_id');
            $table->unsignedInteger('conta_id');
            $table->enum('tipo', ['entrada', 'saida']);
            $table->enum('status', ['ativo', 'cancelado', 'concluido'])->default('ativo');
            $table->date('data_criacao');
            $table->timestamps();
        });
    }

    public function down()
    {
        DB::schema()->dropIfExists('parcelamentos');
    }
};
