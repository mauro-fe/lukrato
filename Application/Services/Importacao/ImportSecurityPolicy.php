<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Config\ImportacaoRuntimeConfig;
use Application\Container\ApplicationContainer;

final class ImportSecurityPolicy
{
    public static function maxUploadSizeBytes(): int
    {
        return self::runtimeConfig()->maxUploadSizeBytes();
    }

    public static function maxRowsPerFile(): int
    {
        return self::runtimeConfig()->maxRowsPerFile();
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
        return self::runtimeConfig()->shouldQueueConfirmByDefault();
    }

    public static function importRateLimitAttempts(): int
    {
        return self::runtimeConfig()->importRateLimitAttempts();
    }

    public static function importRateLimitWindow(): int
    {
        return self::runtimeConfig()->importRateLimitWindow();
    }

    private static function runtimeConfig(): ImportacaoRuntimeConfig
    {
        return ApplicationContainer::resolveOrNew(null, ImportacaoRuntimeConfig::class);
    }
}
