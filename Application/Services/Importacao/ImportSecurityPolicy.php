<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

final class ImportSecurityPolicy
{
    private const DEFAULT_MAX_UPLOAD_SIZE_BYTES = 5 * 1024 * 1024;
    private const DEFAULT_MAX_ROWS_PER_FILE = 1000;
    private const DEFAULT_IMPORT_RATE_LIMIT_ATTEMPTS = 5;
    private const DEFAULT_IMPORT_RATE_LIMIT_WINDOW = 60;

    public static function maxUploadSizeBytes(): int
    {
        return max(1024, (int) ($_ENV['IMPORTACOES_MAX_FILE_SIZE_BYTES'] ?? self::DEFAULT_MAX_UPLOAD_SIZE_BYTES));
    }

    public static function maxRowsPerFile(): int
    {
        return max(1, (int) ($_ENV['IMPORTACOES_MAX_ROWS'] ?? self::DEFAULT_MAX_ROWS_PER_FILE));
    }

    public static function rowsLimitMessage(?int $limit = null): string
    {
        $resolvedLimit = $limit ?? self::maxRowsPerFile();

        return sprintf('Arquivo excede o limite de %d linhas/transações por importação.', $resolvedLimit);
    }

    public static function clientProcessingErrorMessage(): string
    {
        return 'Não foi possível processar a importação agora. Tente novamente em instantes.';
    }

    public static function shouldQueueConfirmByDefault(): bool
    {
        $configured = $_ENV['IMPORTACOES_CONFIRM_ASYNC_DEFAULT']
            ?? getenv('IMPORTACOES_CONFIRM_ASYNC_DEFAULT');

        return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
    }

    public static function importRateLimitAttempts(): int
    {
        return max(1, (int) ($_ENV['IMPORTACOES_RATE_LIMIT_MAX_ATTEMPTS'] ?? self::DEFAULT_IMPORT_RATE_LIMIT_ATTEMPTS));
    }

    public static function importRateLimitWindow(): int
    {
        return max(30, (int) ($_ENV['IMPORTACOES_RATE_LIMIT_TIME_WINDOW'] ?? self::DEFAULT_IMPORT_RATE_LIMIT_WINDOW));
    }
}
