<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Criar tabela de cupons de desconto
 * Data: 2026-01-28
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        // Tabela de cupons
        if (!$schema->hasTable('cupons')) {
            $schema->create('cupons', function ($table) {
                $table->increments('id');
                $table->string('codigo', 50)->unique()->comment('Código do cupom');
                $table->enum('tipo_desconto', ['percentual', 'fixo'])->default('percentual')
                    ->comment('Tipo de desconto: percentual ou fixo (R$)');
                $table->decimal('valor_desconto', 10, 2)->comment('Valor do desconto (% ou R$)');
                $table->date('valido_ate')->nullable()->comment('Data de validade do cupom');
                $table->integer('limite_uso')->default(0)->comment('Limite de usos (0 = ilimitado)');
                $table->integer('uso_atual')->default(0)->comment('Quantidade de vezes que foi usado');
                $table->boolean('ativo')->default(true)->comment('Cupom ativo/inativo');
                $table->text('descricao')->nullable()->comment('Descrição do cupom');
                $table->timestamps();

                $table->index('codigo');
                $table->index('ativo');
                $table->index('valido_ate');
            });

            echo "✅ Tabela 'cupons' criada com sucesso.\n";
        }

        // Tabela de histórico de uso de cupons
        if (!$schema->hasTable('cupons_usados')) {
            $schema->create('cupons_usados', function ($table) {
                $table->increments('id');
                $table->integer('cupom_id')->unsigned();
                $table->integer('usuario_id')->unsigned();
                $table->integer('assinatura_id')->unsigned()->nullable();
                $table->decimal('desconto_aplicado', 10, 2)->comment('Valor do desconto aplicado');
                $table->decimal('valor_original', 10, 2)->comment('Valor original antes do desconto');
                $table->decimal('valor_final', 10, 2)->comment('Valor final após o desconto');
                $table->timestamp('usado_em')->useCurrent();

                $table->foreign('cupom_id')->references('id')->on('cupons')->onDelete('cascade');
                $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
                $table->foreign('assinatura_id')->references('id')->on('assinatura_usuarios')->onDelete('set null');

                $table->index('cupom_id');
                $table->index('usuario_id');
                $table->index('usado_em');
            });

            echo "✅ Tabela 'cupons_usados' criada com sucesso.\n";
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        $schema->dropIfExists('cupons_usados');
        $schema->dropIfExists('cupons');

        echo "✅ Tabelas de cupons removidas.\n";
    }
};
