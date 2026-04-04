<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\ServiceResultDTO;
use Application\Models\ImportacaoJob;
use Application\Services\Importacao\ImportExecutionService;
use Application\Services\Importacao\ImportQueueService;
use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class ImportQueueServiceTest extends TestCase
{
    /**
     * @var array<int, int>
     */
    private array $cleanupUserIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            ImportacaoJob::query()->whereIn('user_id', $this->cleanupUserIds)->delete();
        }

        $this->cleanupUserIds = [];
        parent::tearDown();
    }

    public function testProcessNextRetriesAndEventuallyFails(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = 92001;
        $this->cleanupUserIds[] = $userId;

        $previousMaxAttempts = $_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS'] ?? null;
        $_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS'] = '2';

        $queue = new ImportQueueService(new class extends ImportExecutionService {
            public function __construct() {}

            public function confirmExecution(
                int $userId,
                string $sourceType,
                string $contents,
                ImportProfileConfigDTO $profile,
                string $filename = '',
                string $importTarget = 'conta',
                ?int $cartaoId = null,
                array $rowOverrides = []
            ): ServiceResultDTO {
                return ServiceResultDTO::fail('Falha simulada no worker.', 422);
            }
        });

        $tmpFile = $this->createTempFile('retry-flow.ofx', '<OFX><STMTTRN></STMTTRN></OFX>');
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 501,
            'source_type' => 'ofx',
        ]);

        $queuedJob = $queue->enqueueFromUpload($userId, 'ofx', $profile, $tmpFile, 'retry-flow.ofx');
        $storedPath = (string) ImportacaoJob::query()->where('id', (int) $queuedJob['id'])->value('temp_file_path');

        $this->assertFileExists($storedPath);

        $first = $queue->processNext();
        $this->assertIsArray($first);
        $this->assertSame('queued', $first['status'] ?? null);
        $this->assertSame(1, $first['job']['attempts'] ?? null);
        $this->assertFileExists($storedPath);

        $second = $queue->processNext();
        $this->assertIsArray($second);
        $this->assertSame('failed', $second['status'] ?? null);
        $this->assertSame(2, $second['job']['attempts'] ?? null);
        $this->assertFileDoesNotExist($storedPath);

        if ($previousMaxAttempts === null) {
            unset($_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS']);
        } else {
            $_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS'] = (string) $previousMaxAttempts;
        }
    }

    public function testProcessNextRecoversStaleProcessingJob(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = 92002;
        $this->cleanupUserIds[] = $userId;

        $previousMaxAttempts = $_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS'] ?? null;
        $previousStaleTtl = $_ENV['IMPORTACOES_QUEUE_STALE_TTL'] ?? null;
        $_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS'] = '3';
        $_ENV['IMPORTACOES_QUEUE_STALE_TTL'] = '30';

        $queue = new ImportQueueService(new class extends ImportExecutionService {
            public function __construct() {}

            public function confirmExecution(
                int $userId,
                string $sourceType,
                string $contents,
                ImportProfileConfigDTO $profile,
                string $filename = '',
                string $importTarget = 'conta',
                ?int $cartaoId = null,
                array $rowOverrides = []
            ): ServiceResultDTO {
                return ServiceResultDTO::ok('Processado', [
                    'batch' => ['id' => null],
                    'summary' => [
                        'total_rows' => 1,
                        'imported_rows' => 1,
                        'duplicate_rows' => 0,
                        'error_rows' => 0,
                    ],
                ]);
            }
        });

        $tmpFile = $this->createTempFile('stale-flow.ofx', '<OFX><STMTTRN></STMTTRN></OFX>');
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 601,
            'source_type' => 'ofx',
        ]);

        $queuedJob = $queue->enqueueFromUpload($userId, 'ofx', $profile, $tmpFile, 'stale-flow.ofx');

        ImportacaoJob::query()
            ->where('id', (int) $queuedJob['id'])
            ->update([
                'status' => 'processing',
                'attempts' => 0,
                'started_at' => date('Y-m-d H:i:s', time() - 120),
                'finished_at' => null,
            ]);

        $result = $queue->processNext();

        $this->assertIsArray($result);
        $this->assertSame('completed', $result['status'] ?? null);
        $this->assertSame(1, $result['job']['attempts'] ?? null);
        $this->assertSame(1, $result['job']['imported_rows'] ?? null);

        if ($previousMaxAttempts === null) {
            unset($_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS']);
        } else {
            $_ENV['IMPORTACOES_QUEUE_MAX_ATTEMPTS'] = (string) $previousMaxAttempts;
        }

        if ($previousStaleTtl === null) {
            unset($_ENV['IMPORTACOES_QUEUE_STALE_TTL']);
        } else {
            $_ENV['IMPORTACOES_QUEUE_STALE_TTL'] = (string) $previousStaleTtl;
        }
    }

    public function testQueuePersistsAndForwardsRowOverrides(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = 92004;
        $this->cleanupUserIds[] = $userId;

        $executionService = new class extends ImportExecutionService {
            public array $receivedOverrides = [];

            public function __construct() {}

            public function confirmExecution(
                int $userId,
                string $sourceType,
                string $contents,
                ImportProfileConfigDTO $profile,
                string $filename = '',
                string $importTarget = 'conta',
                ?int $cartaoId = null,
                array $rowOverrides = []
            ): ServiceResultDTO {
                $this->receivedOverrides = $rowOverrides;

                return ServiceResultDTO::ok('Processado', [
                    'batch' => ['id' => null],
                    'summary' => [
                        'total_rows' => 1,
                        'imported_rows' => 1,
                        'duplicate_rows' => 0,
                        'error_rows' => 0,
                    ],
                ]);
            }
        };

        $queue = new ImportQueueService($executionService);
        $tmpFile = $this->createTempFile('queue-overrides.ofx', '<OFX><STMTTRN></STMTTRN></OFX>');
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 801,
            'source_type' => 'ofx',
        ]);
        $rowOverrides = [
            'preview-row-1' => [
                'categoria_id' => 101,
                'subcategoria_id' => 202,
                'user_edited' => true,
            ],
        ];

        $queuedJob = $queue->enqueueFromUpload(
            $userId,
            'ofx',
            $profile,
            $tmpFile,
            'queue-overrides.ofx',
            'conta',
            null,
            $rowOverrides
        );

        $meta = json_decode((string) ImportacaoJob::query()->where('id', (int) $queuedJob['id'])->value('meta_json'), true);
        $this->assertIsArray($meta);
        $this->assertSame($rowOverrides, $meta['row_overrides'] ?? null);

        $result = $queue->processNext();

        $this->assertIsArray($result);
        $this->assertSame('completed', $result['status'] ?? null);
        $this->assertSame($rowOverrides, $executionService->receivedOverrides);
    }

    public function testQueueUsesPrivateStorageDirectoryByDefault(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = 92003;
        $this->cleanupUserIds[] = $userId;

        $previousConfiguredDirectory = $_ENV['IMPORTACOES_QUEUE_STORAGE_PATH'] ?? null;
        unset($_ENV['IMPORTACOES_QUEUE_STORAGE_PATH']);

        $queue = new ImportQueueService(new class extends ImportExecutionService {
            public function __construct() {}
        });

        $tmpFile = $this->createTempFile('private-queue.ofx', '<OFX><STMTTRN></STMTTRN></OFX>');
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 701,
            'source_type' => 'ofx',
        ]);

        try {
            $queuedJob = $queue->enqueueFromUpload($userId, 'ofx', $profile, $tmpFile, 'private-queue.ofx');
            $storedPath = (string) ImportacaoJob::query()->where('id', (int) $queuedJob['id'])->value('temp_file_path');

            $normalizedStoredPath = str_replace('\\', '/', $storedPath);
            $normalizedLegacyStorage = str_replace('\\', '/', BASE_PATH . '/storage/importacoes/queue');
            $normalizedPrivateBase = str_replace('\\', '/', dirname(BASE_PATH, 2) . '/lukrato-storage/importacoes/queue');

            $this->assertFileExists($storedPath);
            $this->assertStringNotContainsString($normalizedLegacyStorage, $normalizedStoredPath);
            $this->assertStringContainsString($normalizedPrivateBase, $normalizedStoredPath);
        } finally {
            if (isset($storedPath) && is_file($storedPath)) {
                @unlink($storedPath);
            }

            if ($previousConfiguredDirectory === null) {
                unset($_ENV['IMPORTACOES_QUEUE_STORAGE_PATH']);
            } else {
                $_ENV['IMPORTACOES_QUEUE_STORAGE_PATH'] = (string) $previousConfiguredDirectory;
            }
        }
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for importacao queue tests');
        }
    }

    private function createTempFile(string $name, string $contents): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid($name . '-', true);
        file_put_contents($path, $contents);

        return $path;
    }
}
