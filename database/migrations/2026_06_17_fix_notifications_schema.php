<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('notifications')) {
            return;
        }

        if (!$schema->hasColumn('notifications', 'read_at')) {
            Capsule::statement('ALTER TABLE notifications ADD COLUMN read_at TIMESTAMP NULL AFTER is_read');
        }

        Capsule::statement("
            UPDATE notifications
            SET read_at = COALESCE(read_at, updated_at, created_at, CURRENT_TIMESTAMP)
            WHERE is_read = 1 AND read_at IS NULL
        ");

        if (!$this->hasIndex('notifications', 'idx_notifications_user_is_read')) {
            Capsule::statement('CREATE INDEX idx_notifications_user_is_read ON notifications(user_id, is_read)');
        }

        if ($schema->hasColumn('notifications', 'campaign_id') && !$this->hasIndex('notifications', 'idx_notifications_campaign')) {
            Capsule::statement('CREATE INDEX idx_notifications_campaign ON notifications(campaign_id)');
        }

        if ($schema->hasColumn('notifications', 'created_at') && !$this->hasIndex('notifications', 'idx_notifications_created_at')) {
            Capsule::statement('CREATE INDEX idx_notifications_created_at ON notifications(created_at)');
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('notifications')) {
            return;
        }

        if ($this->hasIndex('notifications', 'idx_notifications_created_at')) {
            Capsule::statement('DROP INDEX idx_notifications_created_at ON notifications');
        }

        if ($this->hasIndex('notifications', 'idx_notifications_campaign')) {
            Capsule::statement('DROP INDEX idx_notifications_campaign ON notifications');
        }

        if ($this->hasIndex('notifications', 'idx_notifications_user_is_read')) {
            Capsule::statement('DROP INDEX idx_notifications_user_is_read ON notifications');
        }

        if ($schema->hasColumn('notifications', 'read_at')) {
            Capsule::statement('ALTER TABLE notifications DROP COLUMN read_at');
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $result = Capsule::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $indexName]
        );

        return $result !== [];
    }
};
