<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('telegram_messages')) {
            Capsule::connection()->statement(
                "ALTER TABLE telegram_messages MODIFY COLUMN `type`
                 ENUM('text', 'callback_query', 'command', 'voice', 'audio', 'photo', 'document', 'video', 'unknown')
                 NOT NULL DEFAULT 'text'"
            );

            Capsule::schema()->table('telegram_messages', function ($table) {
                if (!Capsule::schema()->hasColumn('telegram_messages', 'media_filename')) {
                    $table->string('media_filename', 255)->nullable()->after('media_file_size');
                }
            });
        }

        if (Capsule::schema()->hasTable('whatsapp_messages')) {
            Capsule::connection()->statement(
                "ALTER TABLE whatsapp_messages MODIFY COLUMN `type`
                 ENUM('text', 'interactive', 'audio', 'image', 'document', 'video', 'status', 'unknown')
                 NOT NULL DEFAULT 'text'"
            );

            Capsule::schema()->table('whatsapp_messages', function ($table) {
                if (!Capsule::schema()->hasColumn('whatsapp_messages', 'media_file_id')) {
                    $table->string('media_file_id', 200)->nullable()->after('body');
                }
                if (!Capsule::schema()->hasColumn('whatsapp_messages', 'media_mime_type')) {
                    $table->string('media_mime_type', 100)->nullable()->after('media_file_id');
                }
                if (!Capsule::schema()->hasColumn('whatsapp_messages', 'media_file_size')) {
                    $table->unsignedInteger('media_file_size')->nullable()->after('media_mime_type');
                }
                if (!Capsule::schema()->hasColumn('whatsapp_messages', 'media_filename')) {
                    $table->string('media_filename', 255)->nullable()->after('media_file_size');
                }
                if (!Capsule::schema()->hasColumn('whatsapp_messages', 'transcription')) {
                    $table->text('transcription')->nullable()->after('media_filename');
                }
            });
        }
    }

    public function down(): void
    {
        if (Capsule::schema()->hasTable('telegram_messages')) {
            Capsule::connection()->statement(
                "ALTER TABLE telegram_messages MODIFY COLUMN `type`
                 ENUM('text', 'callback_query', 'command', 'voice', 'audio', 'photo', 'unknown')
                 NOT NULL DEFAULT 'text'"
            );

            Capsule::schema()->table('telegram_messages', function ($table) {
                if (Capsule::schema()->hasColumn('telegram_messages', 'media_filename')) {
                    $table->dropColumn('media_filename');
                }
            });
        }

        if (Capsule::schema()->hasTable('whatsapp_messages')) {
            Capsule::connection()->statement(
                "ALTER TABLE whatsapp_messages MODIFY COLUMN `type`
                 ENUM('text', 'interactive', 'status', 'unknown')
                 NOT NULL DEFAULT 'text'"
            );

            Capsule::schema()->table('whatsapp_messages', function ($table) {
                foreach (['transcription', 'media_filename', 'media_file_size', 'media_mime_type', 'media_file_id'] as $column) {
                    if (Capsule::schema()->hasColumn('whatsapp_messages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
