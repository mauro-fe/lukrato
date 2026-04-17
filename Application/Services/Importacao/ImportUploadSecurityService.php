<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

class ImportUploadSecurityService
{
    private const SIGNATURE_SAMPLE_BYTES = 65536;

    /**
     * @param array<string, mixed> $file
     * @return array{tmp_name:string,filename:string,mime_type:string,size:int,contents?:string}
     */
    public function extractValidatedUpload(string $sourceType, array $file, bool $loadContents = false): array
    {
        $normalizedSourceType = strtolower(trim($sourceType));
        if (!in_array($normalizedSourceType, ['ofx', 'csv'], true)) {
            throw new \InvalidArgumentException('Formato de importação inválido.');
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Arquivo obrigatório para importação.');
        }

        $tmpName = trim((string) ($file['tmp_name'] ?? ''));
        if ($tmpName === '' || !is_file($tmpName)) {
            throw new \InvalidArgumentException('Arquivo temporário inválido para importação.');
        }

        $filename = ImportSanitizer::sanitizeFilename(
            (string) ($file['name'] ?? ''),
            'importacao.' . $normalizedSourceType
        );
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($normalizedSourceType === 'ofx' && !in_array($extension, ['ofx', 'qfx'], true)) {
            throw new \InvalidArgumentException('Para OFX, envie arquivo com extensão .ofx ou .qfx.');
        }

        if ($normalizedSourceType === 'csv' && $extension !== 'csv') {
            throw new \InvalidArgumentException('Para CSV, envie arquivo com extensão .csv.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new \InvalidArgumentException('Arquivo vazio não é suportado nesta etapa.');
        }

        $maxUploadSizeBytes = ImportSecurityPolicy::maxUploadSizeBytes();
        if ($size > $maxUploadSizeBytes) {
            $maxUploadSizeMb = max(1, (int) ceil($maxUploadSizeBytes / (1024 * 1024)));
            throw new \InvalidArgumentException(
                sprintf('Arquivo acima de %dMB não é suportado nesta etapa.', $maxUploadSizeMb)
            );
        }

        $mimeType = $this->detectMimeType($tmpName);
        if ($mimeType === '') {
            throw new \InvalidArgumentException('Não foi possível validar o tipo real do arquivo enviado.');
        }

        if (!$this->isAllowedMimeType($normalizedSourceType, $mimeType)) {
            throw new \InvalidArgumentException(
                sprintf('Tipo MIME real do arquivo não é compatível com importação %s.', strtoupper($normalizedSourceType))
            );
        }

        $sample = $this->readSignatureSample($tmpName, $size);
        $this->assertExpectedSignature($normalizedSourceType, $sample);

        $result = [
            'tmp_name' => $tmpName,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
        ];

        if ($loadContents) {
            $contents = file_get_contents($tmpName);
            if ($contents === false) {
                throw new \InvalidArgumentException('Não foi possível ler o arquivo enviado.');
            }

            $result['contents'] = $contents;
        }

        return $result;
    }

    private function detectMimeType(string $tmpName): string
    {
        $mimeType = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (is_string($detected)) {
                    $mimeType = strtolower(trim($detected));
                }
            }
        }

        if ($mimeType === '' && function_exists('mime_content_type')) {
            $detected = mime_content_type($tmpName);
            if (is_string($detected)) {
                $mimeType = strtolower(trim($detected));
            }
        }

        return $mimeType;
    }

    private function isAllowedMimeType(string $sourceType, string $mimeType): bool
    {
        $allowedMimeTypes = match ($sourceType) {
            'ofx' => [
                'application/ofx',
                'application/x-ofx',
                'application/vnd.intu.qfx',
                'application/xml',
                'text/plain',
                'text/xml',
                'application/octet-stream',
            ],
            'csv' => [
                'text/csv',
                'text/plain',
                'text/x-csv',
                'text/comma-separated-values',
                'application/csv',
                'application/vnd.ms-excel',
                'application/octet-stream',
            ],
            default => [],
        };

        return in_array($mimeType, $allowedMimeTypes, true);
    }

    private function readSignatureSample(string $tmpName, int $size): string
    {
        $length = min($size, self::SIGNATURE_SAMPLE_BYTES);
        $sample = file_get_contents($tmpName, false, null, 0, $length);

        if ($sample === false) {
            throw new \InvalidArgumentException('Não foi possível inspecionar o arquivo enviado.');
        }

        return $sample;
    }

    private function assertExpectedSignature(string $sourceType, string $sample): void
    {
        $normalizedSample = ltrim($sample, "\xEF\xBB\xBF");

        if ($normalizedSample === '' || str_contains($normalizedSample, "\0")) {
            throw new \InvalidArgumentException(
                sprintf('Arquivo %s sem assinatura mínima válida.', strtoupper($sourceType))
            );
        }

        if ($sourceType === 'ofx') {
            $upperSample = strtoupper($normalizedSample);
            if (
                !str_contains($upperSample, '<OFX')
                && !str_contains($upperSample, 'OFXHEADER:')
                && !str_contains($upperSample, '<STMTTRN')
            ) {
                throw new \InvalidArgumentException('Arquivo OFX sem assinatura mínima válida.');
            }

            return;
        }

        if (!$this->looksLikeCsv($normalizedSample)) {
            throw new \InvalidArgumentException('Arquivo CSV sem assinatura mínima válida.');
        }
    }

    private function looksLikeCsv(string $sample): bool
    {
        $lines = preg_split('/\r\n|\r|\n/', $sample) ?: [];
        $nonEmptyLines = [];

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '') {
                continue;
            }

            $nonEmptyLines[] = $trimmed;
            if (count($nonEmptyLines) >= 5) {
                break;
            }
        }

        foreach ($nonEmptyLines as $line) {
            foreach ([',', ';', "\t"] as $delimiter) {
                $columns = str_getcsv($line, $delimiter);
                if (is_array($columns) && count($columns) >= 2) {
                    return true;
                }
            }
        }

        return false;
    }
}
