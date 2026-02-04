<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Adiciona código de indicação único para cada usuário
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        // Adiciona campo referral_code na tabela usuarios
        if (!$schema->hasColumn('usuarios', 'referral_code')) {
            $schema->table('usuarios', function (Blueprint $table) {
                $table->string('referral_code', 12)->nullable()->unique()->after('google_id');
            });
        }

        // Adiciona campo referred_by (quem indicou este usuário)
        if (!$schema->hasColumn('usuarios', 'referred_by')) {
            $schema->table('usuarios', function (Blueprint $table) {
                $table->unsignedInteger('referred_by')->nullable()->after('referral_code');
                $table->foreign('referred_by')->references('id')->on('usuarios')->nullOnDelete();
            });
        }

        // Gera códigos para usuários existentes que não têm
        $this->generateCodesForExistingUsers();
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasColumn('usuarios', 'referred_by')) {
            $schema->table('usuarios', function (Blueprint $table) {
                $table->dropForeign(['referred_by']);
                $table->dropColumn('referred_by');
            });
        }

        if ($schema->hasColumn('usuarios', 'referral_code')) {
            $schema->table('usuarios', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }

    /**
     * Gera códigos de indicação para usuários existentes
     */
    private function generateCodesForExistingUsers(): void
    {
        $usuarios = Capsule::table('usuarios')
            ->whereNull('referral_code')
            ->select('id')
            ->get();

        foreach ($usuarios as $usuario) {
            $code = $this->generateUniqueCode();
            Capsule::table('usuarios')
                ->where('id', $usuario->id)
                ->update(['referral_code' => $code]);
        }
    }

    /**
     * Gera um código único de 8 caracteres
     */
    private function generateUniqueCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sem I, O, 0, 1 para evitar confusão

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (Capsule::table('usuarios')->where('referral_code', $code)->exists());

        return $code;
    }
};
