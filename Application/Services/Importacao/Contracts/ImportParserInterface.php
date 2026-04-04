<?php

declare(strict_types=1);

namespace Application\Services\Importacao\Contracts;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\Importacao\NormalizedImportRowDTO;

interface ImportParserInterface
{
    public function supports(string $sourceType): bool;

    /**
     * @return array<int, NormalizedImportRowDTO>
     */
    public function parse(string $contents, ImportProfileConfigDTO $profile): array;
}
