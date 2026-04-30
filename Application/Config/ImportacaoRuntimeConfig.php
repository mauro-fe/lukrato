<?php

declare(strict_types=1);

namespace Application\Config;

final class ImportacaoRuntimeConfig
{
    private const DEFAULT_MAX_UPLOAD_SIZE_BYTES = 5 * 1024 * 1024;
    private const DEFAULT_MAX_ROWS_PER_FILE = 1000;
    private const DEFAULT_IMPORT_RATE_LIMIT_ATTEMPTS = 5;
    private const DEFAULT_IMPORT_RATE_LIMIT_WINDOW = 60;
    private const DEFAULT_QUEUE_MAX_ATTEMPTS = 3;
    private const DEFAULT_QUEUE_STALE_TTL_SECONDS = 900;
    private const DEFAULT_QUEUE_SLEEP_SECONDS = 2;

    public function maxUploadSizeBytes(): int
    {
        return max(1024, $this->int('IMPORTACOES_MAX_FILE_SIZE_BYTES', self::DEFAULT_MAX_UPLOAD_SIZE_BYTES));
    }

    public function maxRowsPerFile(): int
    {
        return max(1, $this->int('IMPORTACOES_MAX_ROWS', self::DEFAULT_MAX_ROWS_PER_FILE));
    }

    public function shouldQueueConfirmByDefault(): bool
    {
        return filter_var($this->value('IMPORTACOES_CONFIRM_ASYNC_DEFAULT', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function importRateLimitAttempts(): int
    {
        return max(1, $this->int('IMPORTACOES_RATE_LIMIT_MAX_ATTEMPTS', self::DEFAULT_IMPORT_RATE_LIMIT_ATTEMPTS));
    }

    public function importRateLimitWindow(): int
    {
        return max(30, $this->int('IMPORTACOES_RATE_LIMIT_TIME_WINDOW', self::DEFAULT_IMPORT_RATE_LIMIT_WINDOW));
    }

    public function queueMaxAttempts(): int
    {
        return max(1, $this->int('IMPORTACOES_QUEUE_MAX_ATTEMPTS', self::DEFAULT_QUEUE_MAX_ATTEMPTS));
    }

    public function queueStaleTtlSeconds(): int
    {
        return max(30, $this->int('IMPORTACOES_QUEUE_STALE_TTL', self::DEFAULT_QUEUE_STALE_TTL_SECONDS));
    }

    public function queueStoragePath(): string
    {
        return trim((string) $this->value('IMPORTACOES_QUEUE_STORAGE_PATH', ''));
    }

    public function storagePath(): string
    {
        return trim((string) $this->value('STORAGE_PATH', ''));
    }

    public function queueSleepSeconds(): int
    {
        return max(1, $this->int('IMPORTACOES_QUEUE_SLEEP', self::DEFAULT_QUEUE_SLEEP_SECONDS));
    }

    private function int(string $key, int $default): int
    {
        return (int) $this->value($key, $default);
    }

    private function value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== null) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value !== false && $value !== null) {
            return $value;
        }

        return $default;
    }
}
