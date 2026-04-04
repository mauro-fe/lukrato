<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Enums\LogCategory;
use Application\Models\ImportacaoJob;
use Application\Models\ImportacaoLote;
use Application\Services\Infrastructure\LogService;
use Illuminate\Database\Capsule\Manager as DB;

class ImportQueueService
{
    private const STATUS_QUEUED = 'queued';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';
    private const DEFAULT_MAX_ATTEMPTS = 3;
    private const DEFAULT_STALE_TTL_SECONDS = 900;

    public function __construct(
        private readonly ImportExecutionService $executionService = new ImportExecutionService(),
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function enqueueFromUpload(
        int $userId,
        string $sourceType,
        ImportProfileConfigDTO $profile,
        string $tmpName,
        string $filename,
        string $importTarget = 'conta',
        ?int $cartaoId = null,
        array $rowOverrides = []
    ): array {
        $sourceType = strtolower(trim($sourceType));
        $importTarget = $this->normalizeImportTarget($importTarget);
        $filename = $this->normalizeFilename($filename);

        if ($tmpName === '' || !is_file($tmpName)) {
            throw new \InvalidArgumentException('Arquivo temporário inválido para enfileiramento.');
        }

        $targetPath = $this->moveToQueueStorage($tmpName, $filename, $sourceType);

        $job = ImportacaoJob::query()->create([
            'user_id' => $userId,
            'conta_id' => $profile->contaId,
            'cartao_id' => $cartaoId,
            'source_type' => $sourceType !== '' ? $sourceType : 'ofx',
            'import_target' => $importTarget,
            'filename' => $filename,
            'temp_file_path' => $targetPath,
            'status' => self::STATUS_QUEUED,
            'attempts' => 0,
            'started_at' => null,
            'finished_at' => null,
            'total_rows' => 0,
            'processed_rows' => 0,
            'imported_rows' => 0,
            'duplicate_rows' => 0,
            'error_rows' => 0,
            'result_batch_id' => null,
            'error_summary' => null,
            'meta_json' => json_encode([
                'profile' => $profile->toArray(),
                'import_target' => $importTarget,
                'cartao_id' => $cartaoId,
                'row_overrides' => $rowOverrides,
                'queued_at' => date('c'),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ]);

        return $this->formatJob($job);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function processNext(): ?array
    {
        $job = $this->claimNextJob();
        if (!$job) {
            return null;
        }

        $message = 'Job processado com sucesso.';
        $shouldCleanupFile = false;

        try {
            $meta = $this->decodeMeta((string) ($job->meta_json ?? ''));
            $profilePayload = is_array($meta['profile'] ?? null) ? $meta['profile'] : [];
            $profile = ImportProfileConfigDTO::fromArray($profilePayload);

            $contents = file_get_contents((string) ($job->temp_file_path ?? ''));
            if ($contents === false) {
                throw new \RuntimeException('Falha ao ler arquivo temporário da fila de importação.');
            }

            $importTarget = $this->normalizeImportTarget((string) ($job->import_target ?? 'conta'));
            $cartaoId = is_numeric($job->cartao_id ?? null) ? (int) $job->cartao_id : null;
            $sourceType = strtolower(trim((string) ($job->source_type ?? 'ofx')));
            $rowOverrides = is_array($meta['row_overrides'] ?? null) ? $meta['row_overrides'] : [];

            $result = $this->executionService->confirmExecution(
                (int) $job->user_id,
                $sourceType !== '' ? $sourceType : 'ofx',
                $contents,
                $profile,
                (string) ($job->filename ?? ''),
                $importTarget,
                $cartaoId,
                $rowOverrides
            );

            if (!$result->success) {
                $failureMessage = $result->message !== ''
                    ? $result->message
                    : 'Falha ao processar job de importação.';
                $nextStatus = $this->handleJobFailure($job, $failureMessage);
                $shouldCleanupFile = $nextStatus === self::STATUS_FAILED;
                $message = $failureMessage;

                return [
                    'status' => (string) $job->status,
                    'message' => $message,
                    'job' => $this->formatJob($job),
                ];
            }

            $batch = is_array($result->data['batch'] ?? null) ? $result->data['batch'] : [];
            $summary = is_array($result->data['summary'] ?? null) ? $result->data['summary'] : [];

            $job->status = self::STATUS_COMPLETED;
            $job->result_batch_id = is_numeric($batch['id'] ?? null) ? (int) $batch['id'] : null;
            $job->total_rows = (int) ($summary['total_rows'] ?? $job->total_rows ?? 0);
            $job->processed_rows = (int) ($summary['total_rows'] ?? $job->processed_rows ?? 0);
            $job->imported_rows = (int) ($summary['imported_rows'] ?? $job->imported_rows ?? 0);
            $job->duplicate_rows = (int) ($summary['duplicate_rows'] ?? $job->duplicate_rows ?? 0);
            $job->error_rows = (int) ($summary['error_rows'] ?? $job->error_rows ?? 0);
            $job->error_summary = null;
            $job->finished_at = date('Y-m-d H:i:s');
            $job->save();
            $shouldCleanupFile = true;
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'import_queue_process',
                'job_id' => (int) ($job->id ?? 0),
                'user_id' => (int) ($job->user_id ?? 0),
                'source_type' => (string) ($job->source_type ?? ''),
                'import_target' => (string) ($job->import_target ?? ''),
            ], (int) ($job->user_id ?? 0));

            $failureMessage = ImportSecurityPolicy::clientProcessingErrorMessage();
            $nextStatus = $this->handleJobFailure($job, $failureMessage);
            $shouldCleanupFile = $nextStatus === self::STATUS_FAILED;
            $message = $failureMessage;
        } finally {
            if ($shouldCleanupFile) {
                $this->cleanupQueuedFile((string) ($job->temp_file_path ?? ''));
            }
        }

        return [
            'status' => (string) $job->status,
            'message' => $message,
            'job' => $this->formatJob($job),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStatusForUser(int $userId, int $jobId): ?array
    {
        $job = ImportacaoJob::query()
            ->where('id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if (!$job) {
            return null;
        }

        $summary = [
            'total_rows' => (int) ($job->total_rows ?? 0),
            'processed_rows' => (int) ($job->processed_rows ?? 0),
            'imported_rows' => (int) ($job->imported_rows ?? 0),
            'duplicate_rows' => (int) ($job->duplicate_rows ?? 0),
            'error_rows' => (int) ($job->error_rows ?? 0),
        ];

        $batchPayload = null;
        if ((int) ($job->result_batch_id ?? 0) > 0) {
            $batch = ImportacaoLote::query()
                ->where('id', (int) $job->result_batch_id)
                ->where('user_id', $userId)
                ->first();

            if ($batch) {
                $batchPayload = [
                    'id' => (int) $batch->id,
                    'status' => (string) $batch->status,
                    'source_type' => (string) $batch->source_type,
                    'filename' => (string) ($batch->filename ?? ''),
                    'total_rows' => (int) $batch->total_rows,
                    'imported_rows' => (int) $batch->imported_rows,
                    'duplicate_rows' => (int) $batch->duplicate_rows,
                    'error_rows' => (int) $batch->error_rows,
                ];

                $summary = [
                    'total_rows' => (int) $batch->total_rows,
                    'processed_rows' => (int) $batch->total_rows,
                    'imported_rows' => (int) $batch->imported_rows,
                    'duplicate_rows' => (int) $batch->duplicate_rows,
                    'error_rows' => (int) $batch->error_rows,
                ];
            }
        }

        return [
            'status' => (string) $job->status,
            'job' => $this->formatJob($job),
            'batch' => $batchPayload,
            'summary' => $summary,
            'message' => (string) ($job->error_summary ?? ''),
        ];
    }

    private function claimNextJob(): ?ImportacaoJob
    {
        $this->recoverStaleProcessingJobs();

        return DB::transaction(function (): ?ImportacaoJob {
            $job = ImportacaoJob::query()
                ->where('status', self::STATUS_QUEUED)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$job) {
                return null;
            }

            $job->status = self::STATUS_PROCESSING;
            $job->attempts = (int) ($job->attempts ?? 0) + 1;
            $job->started_at = date('Y-m-d H:i:s');
            $job->error_summary = null;
            $job->save();

            return $job;
        });
    }

    private function recoverStaleProcessingJobs(): void
    {
        $threshold = date('Y-m-d H:i:s', time() - $this->staleTtlSeconds());
        $staleJobs = ImportacaoJob::query()
            ->where('status', self::STATUS_PROCESSING)
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->where('started_at', '<=', $threshold)
            ->orderBy('id')
            ->get();

        if ($staleJobs->isEmpty()) {
            return;
        }

        $maxAttempts = $this->maxAttempts();

        foreach ($staleJobs as $job) {
            $attempts = (int) ($job->attempts ?? 0);
            $recoverMessage = 'Job recuperado após timeout de processamento.';

            if ($attempts >= $maxAttempts) {
                $job->status = self::STATUS_FAILED;
                $job->error_summary = mb_substr($recoverMessage, 0, 2000);
                $job->finished_at = date('Y-m-d H:i:s');
                $job->save();
                continue;
            }

            $job->status = self::STATUS_QUEUED;
            $job->started_at = null;
            $job->finished_at = null;
            $job->error_summary = mb_substr($recoverMessage, 0, 2000);
            $job->save();
        }
    }

    private function handleJobFailure(ImportacaoJob $job, string $failureMessage): string
    {
        $message = trim($failureMessage) !== ''
            ? trim($failureMessage)
            : 'Falha ao processar job de importação.';

        $attempts = (int) ($job->attempts ?? 0);
        $maxAttempts = $this->maxAttempts();

        if ($attempts < $maxAttempts) {
            $job->status = self::STATUS_QUEUED;
            $job->started_at = null;
            $job->finished_at = null;
            $job->error_summary = mb_substr($message, 0, 2000);
            $job->save();

            return self::STATUS_QUEUED;
        }

        $job->status = self::STATUS_FAILED;
        $job->error_summary = mb_substr($message, 0, 2000);
        $job->finished_at = date('Y-m-d H:i:s');
        $job->save();

        return self::STATUS_FAILED;
    }

    private function maxAttempts(): int
    {
        return max(1, (int) ($_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS'] ?? self::DEFAULT_MAX_ATTEMPTS));
    }

    private function staleTtlSeconds(): int
    {
        return max(30, (int) ($_ENV['IMPORTACOES_QUEUE_STALE_TTL'] ?? self::DEFAULT_STALE_TTL_SECONDS));
    }

    private function queueStorageDirectory(): string
    {
        $directory = $this->resolveQueueStorageDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Não foi possível criar diretório da fila de importação.');
        }

        $this->assertDirectoryIsPrivate($directory);

        return $directory;
    }

    private function resolveQueueStorageDirectory(): string
    {
        $configured = trim((string) ($_ENV['IMPORTACOES_QUEUE_STORAGE_PATH'] ?? ''));
        if ($configured !== '') {
            if (!$this->isAbsolutePath($configured)) {
                $configured = BASE_PATH . '/' . ltrim($configured, '/\\');
            }

            return rtrim($configured, '/\\');
        }

        $baseDirectory = dirname(BASE_PATH, 2);
        if ($baseDirectory === '' || $baseDirectory === '.' || $baseDirectory === DIRECTORY_SEPARATOR) {
            $baseDirectory = sys_get_temp_dir();
        }

        return rtrim($baseDirectory, '/\\') . '/lukrato-storage/importacoes/queue';
    }

    private function assertDirectoryIsPrivate(string $directory): void
    {
        $documentRoot = trim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($documentRoot === '' || PHP_SAPI === 'cli') {
            return;
        }

        $normalizedDirectory = $this->normalizePath($directory);
        $normalizedDocumentRoot = $this->normalizePath($documentRoot);

        if ($this->pathStartsWith($normalizedDirectory, $normalizedDocumentRoot)) {
            throw new \RuntimeException('Diretório da fila de importação precisa ficar fora do diretório público do servidor.');
        }
    }

    private function moveToQueueStorage(string $tmpName, string $filename, string $sourceType): string
    {
        $directory = $this->queueStorageDirectory();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = $sourceType !== '' ? $sourceType : 'tmp';
        }

        $target = $directory . '/imp_job_' . bin2hex(random_bytes(16)) . '.' . $extension;

        $moved = @move_uploaded_file($tmpName, $target);
        if (!$moved) {
            $moved = @rename($tmpName, $target);
        }
        if (!$moved) {
            $moved = @copy($tmpName, $target);
            if ($moved) {
                @unlink($tmpName);
            }
        }

        if (!$moved || !is_file($target)) {
            throw new \RuntimeException('Não foi possível mover arquivo para a fila de importação.');
        }

        return $target;
    }

    private function cleanupQueuedFile(string $path): void
    {
        if ($path === '') {
            return;
        }

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }

    private function normalizeFilename(string $filename): string
    {
        return ImportSanitizer::sanitizeFilename($filename, 'importacao.ofx');
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:[\\\/]/', $path) === 1
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\\\');
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        return rtrim(strtolower($normalized), '/');
    }

    private function pathStartsWith(string $path, string $prefix): bool
    {
        return $prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatJob(ImportacaoJob $job): array
    {
        $attempts = (int) ($job->attempts ?? 0);
        $maxAttempts = $this->maxAttempts();

        return [
            'id' => (int) $job->id,
            'status' => (string) $job->status,
            'source_type' => (string) $job->source_type,
            'import_target' => $this->normalizeImportTarget((string) ($job->import_target ?? 'conta')),
            'conta_id' => (int) ($job->conta_id ?? 0),
            'cartao_id' => is_numeric($job->cartao_id ?? null) ? (int) $job->cartao_id : null,
            'filename' => (string) ($job->filename ?? ''),
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'retries_remaining' => max(0, $maxAttempts - $attempts),
            'total_rows' => (int) ($job->total_rows ?? 0),
            'processed_rows' => (int) ($job->processed_rows ?? 0),
            'imported_rows' => (int) ($job->imported_rows ?? 0),
            'duplicate_rows' => (int) ($job->duplicate_rows ?? 0),
            'error_rows' => (int) ($job->error_rows ?? 0),
            'result_batch_id' => is_numeric($job->result_batch_id ?? null) ? (int) $job->result_batch_id : null,
            'error_summary' => (string) ($job->error_summary ?? ''),
            'started_at' => $job->started_at ? (string) $job->started_at : null,
            'finished_at' => $job->finished_at ? (string) $job->finished_at : null,
            'created_at' => (string) ($job->created_at ?? ''),
            'updated_at' => (string) ($job->updated_at ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMeta(string $metaJson): array
    {
        if (trim($metaJson) === '') {
            return [];
        }

        $decoded = json_decode($metaJson, true);

        return is_array($decoded) ? $decoded : [];
    }
}
