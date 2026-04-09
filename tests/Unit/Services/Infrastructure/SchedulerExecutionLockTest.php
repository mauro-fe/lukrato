<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use Application\Services\Infrastructure\SchedulerExecutionLock;
use PHPUnit\Framework\TestCase;

class SchedulerExecutionLockTest extends TestCase
{
    private string $baseDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDirectory = sys_get_temp_dir() . '/lukrato-scheduler-lock-tests-' . bin2hex(random_bytes(4));
    }

    public function testAcquireAndReleaseAllowsSingleExecution(): void
    {
        $first = new SchedulerExecutionLock($this->baseDirectory);
        $second = new SchedulerExecutionLock($this->baseDirectory);

        $first->acquire('scheduler');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Outra execucao do scheduler ja esta em andamento.');

        try {
            $second->acquire('scheduler');
        } finally {
            $first->release();
        }
    }

    public function testReleaseAllowsNewAcquisition(): void
    {
        $first = new SchedulerExecutionLock($this->baseDirectory);
        $second = new SchedulerExecutionLock($this->baseDirectory);

        $first->acquire('scheduler');
        $first->release();

        $second->acquire('scheduler');

        $this->addToAssertionCount(1);
        $second->release();
    }

    public function testUsesConfiguredStoragePathWhenBaseDirectoryIsNotProvided(): void
    {
        $previousStoragePath = $_ENV['STORAGE_PATH'] ?? null;
        $storagePath = sys_get_temp_dir() . '/lukrato-scheduler-default-' . bin2hex(random_bytes(4));
        $_ENV['STORAGE_PATH'] = $storagePath;

        try {
            $lock = new SchedulerExecutionLock();
            $lock->acquire('scheduler-default');

            $this->assertFileExists($storagePath . '/locks/scheduler-default.lock');

            $lock->release();
        } finally {
            if ($previousStoragePath === null) {
                unset($_ENV['STORAGE_PATH']);
            } else {
                $_ENV['STORAGE_PATH'] = $previousStoragePath;
            }
        }
    }
}
