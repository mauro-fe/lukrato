<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Services\Importacao\Contracts\ImportParserInterface;
use Application\Services\Importacao\Parsers\CsvImportParser;
use Application\Services\Importacao\Parsers\OfxImportParser;

class ImportPreviewService
{
    /**
     * @var array<int, ImportParserInterface>
     */
    private array $parsers;

    /**
     * @param array<int, ImportParserInterface> $parsers
     */
    public function __construct(array $parsers = [])
    {
        $this->parsers = $parsers !== [] ? $parsers : [
            new OfxImportParser(),
            new CsvImportParser(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(
        string $sourceType,
        string $contents,
        ImportProfileConfigDTO $profile,
        string $filename = '',
        string $importTarget = 'conta',
        ?int $cartaoId = null
    ): array {
        $sourceType = strtolower(trim($sourceType));
        $importTarget = $this->normalizeImportTarget($importTarget);
        $parser = $this->resolveParser($sourceType);
        $rows = [];
        $warnings = [];
        $errors = [];

        try {
            $rows = $parser->parse($contents, $profile);
        } catch (\InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        if ($rows !== [] && count($rows) > ImportSecurityPolicy::maxRowsPerFile()) {
            $rows = [];
            $errors[] = ImportSecurityPolicy::rowsLimitMessage();
        }

        if ($rows === [] && $errors === []) {
            $warnings[] = 'Nenhuma transação válida encontrada no arquivo informado.';
        }

        return [
            'source_type' => $sourceType,
            'import_target' => $importTarget,
            'conta_id' => $profile->contaId,
            'cartao_id' => $importTarget === 'cartao' ? $cartaoId : null,
            'filename' => $this->normalizeFilename($filename),
            'total_rows' => count($rows),
            'rows' => array_map(
                static fn($row): array => $row->toArray(),
                $rows
            ),
            'warnings' => $warnings,
            'errors' => $errors,
            'can_confirm' => $rows !== [] && $errors === [],
        ];
    }

    private function resolveParser(string $sourceType): ImportParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($sourceType)) {
                return $parser;
            }
        }

        throw new \DomainException("Nenhum parser de importação registrado para o tipo: {$sourceType}");
    }

    private function normalizeFilename(string $filename): string
    {
        return ImportSanitizer::sanitizeFilename($filename, 'importacao.dat');
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }
}
