<?php

declare(strict_types=1);

namespace Application\Services\Infrastructure;

use RuntimeException;

class SchedulerExecutionLock
{
    /** @var resource|null */
    private $handle = null;

    public function __construct(
        private readonly ?string $baseDirectory = null
    ) {
    }

    public function acquire(string $name = 'scheduler'): void
    {
        if ($this->handle !== null) {
            return;
        }

        $path = $this->buildPath($name);
        $directory = dirname($path);

        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Nao foi possivel criar o diretorio de locks do scheduler.');
        }

        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nao foi possivel abrir o arquivo de lock do scheduler.');
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException('Outra execucao do scheduler ja esta em andamento.');
        }

        $payload = json_encode([
            'pid' => getmypid(),
            'acquired_at' => date('c'),
            'sapi' => PHP_SAPI,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (is_string($payload)) {
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $payload);
            fflush($handle);
        }

        $this->handle = $handle;
    }

    public function release(): void
    {
        if ($this->handle === null) {
            return;
        }

        @flock($this->handle, LOCK_UN);
        fclose($this->handle);
        $this->handle = null;
    }

    public function __destruct()
    {
        $this->release();
    }

    private function buildPath(string $name): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name) ?: 'scheduler';
        $basePath = $this->baseDirectory
            ?? ($_ENV['STORAGE_PATH'] ?? (defined('BASE_PATH') ? BASE_PATH . '/storage' : sys_get_temp_dir()));

        return rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'locks' . DIRECTORY_SEPARATOR . $safeName . '.lock';
    }
}
