<?php

/**
 * Migration: Criar tabelas do Blog (Aprenda)
 *
 * Tabelas: blog_categorias, blog_posts
 * Inclui seed das 5 categorias padrão.
 *
 * Uso: php cli/manage_migrations.php
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        // ─── blog_categorias ────────────────────────────────
        if (!DB::schema()->hasTable('blog_categorias')) {
            DB::schema()->create('blog_categorias', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100);
                $table->string('slug', 120)->unique();
                $table->string('icone', 50)->nullable()->comment('Nome do ícone Lucide');
                $table->integer('ordem')->default(0);
                $table->timestamps();
            });
            echo "  ✓ Tabela blog_categorias criada\n";

            // Seed categorias padrão
            DB::table('blog_categorias')->insert([
                [
                    'nome'       => 'Começar com Finanças',
                    'slug'       => 'comecar-com-financas',
                    'icone'      => 'book-open',
                    'ordem'      => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'nome'       => 'Economizar Dinheiro',
                    'slug'       => 'economizar-dinheiro',
                    'icone'      => 'piggy-bank',
                    'ordem'      => 2,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'nome'       => 'Investimentos',
                    'slug'       => 'investimentos',
                    'icone'      => 'trending-up',
                    'ordem'      => 3,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'nome'       => 'Dívidas',
                    'slug'       => 'dividas',
                    'icone'      => 'alert-triangle',
                    'ordem'      => 4,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'nome'       => 'Ferramentas',
                    'slug'       => 'ferramentas',
                    'icone'      => 'calculator',
                    'ordem'      => 5,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ]);
            echo "  ✓ 5 categorias padrão inseridas\n";
        } else {
            echo "  ⊘ Tabela blog_categorias já existe\n";
        }

        // ─── blog_posts ─────────────────────────────────────
        if (!DB::schema()->hasTable('blog_posts')) {
            DB::schema()->create('blog_posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('blog_categoria_id')->nullable();
                $table->string('titulo', 255);
                $table->string('slug', 255)->unique();
                $table->text('resumo')->nullable();
                $table->longText('conteudo');
                $table->string('imagem_capa', 500)->nullable()->comment('Path relativo da imagem');
                $table->string('meta_title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->unsignedSmallInteger('tempo_leitura')->nullable()->comment('Minutos estimados');
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->dateTime('published_at')->nullable();
                $table->timestamps();

                $table->foreign('blog_categoria_id')
                    ->references('id')
                    ->on('blog_categorias')
                    ->onDelete('set null');

                $table->index('status');
                $table->index('published_at');
                $table->index(['status', 'published_at']);
            });
            echo "  ✓ Tabela blog_posts criada\n";
        } else {
            echo "  ⊘ Tabela blog_posts já existe\n";
        }
    }

    public function down(): void
    {
        DB::schema()->dropIfExists('blog_posts');
        echo "  ✓ Tabela blog_posts removida\n";

        DB::schema()->dropIfExists('blog_categorias');
        echo "  ✓ Tabela blog_categorias removida\n";
    }
};
