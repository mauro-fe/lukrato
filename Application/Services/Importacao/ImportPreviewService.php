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

            $warnings = array_merge(
                $warnings,
                $this->buildOfxProfileWarnings($contents, $parserProfile, $importTarget, $detectedImportTarget)
            );
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

        if ($rows !== [] && $userId !== null && $userId > 0) {
            $rows = $this->rowCategorizationService->resolveNamedCategories($rows, $userId, $importTarget);
        }

        if (
            $rows !== []
            && !$targetMismatch
            && $categorizeRows
            && $this->shouldCategorizeRows($sourceType, $importTarget, $userId)
        ) {
            $rows = $this->rowCategorizationService->enrichRows($rows, $userId);
        }

        if ($warnings !== []) {
            $warnings = array_values(array_unique($warnings));
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

    /**
     * @return array<int, string>
     */
    private function buildOfxProfileWarnings(
        string $contents,
        ImportProfileConfigDTO $profile,
        string $importTarget,
        ?string $detectedImportTarget
    ): array {
        if ($this->normalizeImportTarget($importTarget) !== 'conta') {
            return [];
        }

        if ($this->normalizeImportTarget((string) $detectedImportTarget) === 'cartao') {
            return [];
        }

        $profileAgencia = $this->normalizeAccountToken($profile->agencia);
        $profileConta = $this->normalizeAccountToken($profile->numeroConta);
        if ($profileAgencia === '' && $profileConta === '') {
            return [];
        }

        $ofxAgencia = $this->normalizeAccountToken($this->extractOfxTagValue($contents, ['BRANCHID', 'AGENCYID']));
        $ofxConta = $this->normalizeAccountToken($this->extractOfxTagValue($contents, ['ACCTID']));

        $warnings = [];
        if ($profileAgencia !== '' && $ofxAgencia !== '' && $profileAgencia !== $ofxAgencia) {
            $warnings[] = 'A agência informada no perfil não corresponde ao BRANCHID do OFX. Revise antes de confirmar.';
        }

        if ($profileConta !== '' && $ofxConta !== '' && $profileConta !== $ofxConta) {
            $warnings[] = 'O número da conta informado no perfil não corresponde ao ACCTID do OFX. Revise antes de confirmar.';
        }

        return $warnings;
    }

    /**
     * @param array<int, string> $tags
     */
    private function extractOfxTagValue(string $contents, array $tags): ?string
    {
        foreach ($tags as $tag) {
            $value = $this->extractSingleOfxTagValue($contents, $tag);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function extractSingleOfxTagValue(string $contents, string $tag): ?string
    {
        $inlinePattern = '/<\s*' . preg_quote($tag, '/') . '\s*>\s*([^\r\n<]+)/i';
        if (preg_match($inlinePattern, $contents, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $wrappedPattern = '/<\s*' . preg_quote($tag, '/') . '\s*>\s*(.*?)\s*<\s*\/\s*' . preg_quote($tag, '/') . '\s*>/is';
        if (preg_match($wrappedPattern, $contents, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeAccountToken(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));
        return preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';
    }

    private function shouldCategorizeRows(string $sourceType, string $importTarget, ?int $userId): bool
    {
        $normalizedTarget = $this->normalizeImportTarget($importTarget);

        return $userId !== null
            && $userId > 0
            && strtolower(trim($sourceType)) === 'ofx'
            && in_array($normalizedTarget, ['conta', 'cartao'], true);
    }
}
