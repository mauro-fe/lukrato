<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasColumn('usuarios', 'telegram_chat_id')) {
            echo "• Coluna telegram_chat_id já existe em usuarios\n";
            return;
        }

        Capsule::schema()->table('usuarios', function ($table) {
            $table->string('telegram_chat_id', 50)->nullable()->after('whatsapp_verified');
            $table->boolean('telegram_verified')->default(false)->after('telegram_chat_id');

            $table->unique('telegram_chat_id');
            $table->index('telegram_verified');
        });

        echo "✔ Colunas telegram_chat_id e telegram_verified adicionadas em usuarios\n";
    }

    public function down(): void
    {
        Capsule::schema()->table('usuarios', function ($table) {
            $table->dropUnique(['telegram_chat_id']);
            $table->dropColumn(['telegram_chat_id', 'telegram_verified']);
        });
    }
};
