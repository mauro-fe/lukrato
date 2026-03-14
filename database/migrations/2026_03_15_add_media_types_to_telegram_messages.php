<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        // 1. Expandir enum type com voice, audio, photo
        Capsule::connection()->statement(
            "ALTER TABLE telegram_messages MODIFY COLUMN `type`
             ENUM('text', 'callback_query', 'command', 'voice', 'audio', 'photo', 'unknown')
             NOT NULL DEFAULT 'text'"
        );
        echo "✔ Enum type expandido com voice, audio, photo\n";

        // 2. Adicionar colunas de media
        Capsule::schema()->table('telegram_messages', function ($table) {
            if (!Capsule::schema()->hasColumn('telegram_messages', 'media_file_id')) {
                $table->string('media_file_id', 200)->nullable()->after('body')
                    ->comment('Telegram file_id para voice/photo/document');
            }
            if (!Capsule::schema()->hasColumn('telegram_messages', 'media_mime_type')) {
                $table->string('media_mime_type', 50)->nullable()->after('media_file_id');
            }
            if (!Capsule::schema()->hasColumn('telegram_messages', 'media_file_size')) {
                $table->unsignedInteger('media_file_size')->nullable()->after('media_mime_type')
                    ->comment('Tamanho do arquivo em bytes');
            }
            if (!Capsule::schema()->hasColumn('telegram_messages', 'transcription')) {
                $table->text('transcription')->nullable()->after('media_file_size')
                    ->comment('Texto transcrito do audio ou dados extraidos da imagem');
            }
        });
        echo "✔ Colunas media_file_id, media_mime_type, media_file_size, transcription adicionadas\n";

        // 3. Expandir enum ai_logs.type com audio_transcription e image_analysis
        if (Capsule::schema()->hasTable('ai_logs')) {
            Capsule::connection()->statement(
                "ALTER TABLE ai_logs MODIFY COLUMN `type`
                 ENUM('chat', 'suggest_category', 'analyze_spending', 'extract_transaction',
                      'quick_query', 'create_entity', 'confirm_action',
                      'audio_transcription', 'image_analysis')
                 NOT NULL"
            );
            echo "✔ Enum ai_logs.type expandido com audio_transcription, image_analysis\n";
        }
    }

    public function down(): void
    {
        // Reverter enum telegram_messages
        Capsule::connection()->statement(
            "ALTER TABLE telegram_messages MODIFY COLUMN `type`
             ENUM('text', 'callback_query', 'command', 'unknown')
             NOT NULL DEFAULT 'text'"
        );

        // Remover colunas de media
        Capsule::schema()->table('telegram_messages', function ($table) {
            $columns = ['transcription', 'media_file_size', 'media_mime_type', 'media_file_id'];
            foreach ($columns as $col) {
                if (Capsule::schema()->hasColumn('telegram_messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Reverter enum ai_logs
        if (Capsule::schema()->hasTable('ai_logs')) {
            Capsule::connection()->statement(
                "ALTER TABLE ai_logs MODIFY COLUMN `type`
                 ENUM('chat', 'suggest_category', 'analyze_spending', 'extract_transaction',
                      'quick_query', 'create_entity', 'confirm_action')
                 NOT NULL"
            );
        }
    }
};
