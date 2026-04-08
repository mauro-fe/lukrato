<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Container\ApplicationContainer;
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
    private OfxImportTargetDetector $ofxImportTargetDetector;
    private ImportRowCategorizationService $rowCategorizationService;

    /**
     * @param array<int, ImportParserInterface> $parsers
     */
    public function __construct(
        array $parsers = [],
        ?OfxImportTargetDetector $ofxImportTargetDetector = null,
        ?ImportRowCategorizationService $rowCategorizationService = null
    ) {
        $this->parsers = $parsers !== [] ? $parsers : [
            new OfxImportParser(),
            new CsvImportParser(),
        ];
        $this->ofxImportTargetDetector = ApplicationContainer::resolveOrNew($ofxImportTargetDetector, OfxImportTargetDetector::class);
        $this->rowCategorizationService = ApplicationContainer::resolveOrNew($rowCategorizationService, ImportRowCategorizationService::class);
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
        ?int $cartaoId = null,
        ?int $userId = null,
        bool $categorizeRows = false
    ): array {
        $sourceType = strtolower(trim($sourceType));
        $importTarget = $this->normalizeImportTarget($importTarget);
        $parser = $this->resolveParser($sourceType);
        $parserProfile = $this->buildParserProfile($profile, $sourceType, $importTarget);
        $rows = [];
        $warnings = [];
        $errors = [];
        $detectedImportTarget = null;
        $targetMismatch = false;

        try {
            $rows = $parser->parse($contents, $parserProfile);
        } catch (\InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        if ($sourceType === 'ofx') {
            $inspection = $this->ofxImportTargetDetector->inspect($contents);
            $detectedImportTarget = is_string($inspection['detected_import_target'] ?? null)
                ? (string) $inspection['detected_import_target']
                : null;

            $mismatchMessage = $this->ofxImportTargetDetector->buildMismatchMessage($detectedImportTarget, $importTarget);
            if ($mismatchMessage !== null) {
                $errors[] = $mismatchMessage;
                $targetMismatch = true;
            }
        }

        if ($rows !== [] && count($rows) > ImportSecurityPolicy::maxRowsPerFile()) {
            $rows = [];
            $errors[] = ImportSecurityPolicy::rowsLimitMessage();
        }

        if ($rows === [] && $errors === []) {
            $warnings[] = 'Nenhuma transação válida encontrada no arquivo informado.';
        }

        if ($rows !== []) {
            $rows = $this->rowCategorizationService->assignRowKeys($rows);
        }

        if (
            $rows !== []
            && !$targetMismatch
            && $categorizeRows
            && $this->shouldCategorizeRows($sourceType, $importTarget, $userId)
        ) {
            $rows = $this->rowCategorizationService->enrichRows($rows, $userId);
        }

        return [
            'source_type' => $sourceType,
            'import_target' => $importTarget,
            'conta_id' => $profile->contaId,
            'cartao_id' => $importTarget === 'cartao' ? $cartaoId : null,
            'filename' => $this->normalizeFilename($filename),
            'detected_import_target' => $detectedImportTarget,
            'target_mismatch' => $targetMismatch,
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

    private function buildParserProfile(
        ImportProfileConfigDTO $profile,
        string $sourceType,
        string $importTarget
    ): ImportProfileConfigDTO {
        $payload = $profile->toArray();
        $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];
        $options['import_target'] = $importTarget;
        $payload['options'] = $options;
        $payload['source_type'] = $sourceType;

        return ImportProfileConfigDTO::fromArray($payload);
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

    private function shouldCategorizeRows(string $sourceType, string $importTarget, ?int $userId): bool
    {
        return $userId !== null
            && $userId > 0
            && strtolower(trim($sourceType)) === 'ofx'
            && $this->normalizeImportTarget($importTarget) === 'conta';
    }
}
