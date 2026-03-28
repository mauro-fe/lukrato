<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use Application\Services\Infrastructure\LogService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LogServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string|false $previousErrorLog;
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = BASE_PATH . '/tests/.runtime/log-service-test.log';
        $this->previousErrorLog = ini_get('error_log');

        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }

        ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->previousErrorLog === false ? '' : $this->previousErrorLog);

        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }

        parent::tearDown();
    }

    public function testSanitizeContextRedactsSensitiveFields(): void
    {
        $sanitized = LogService::sanitizeContext([
            'password' => 'super-secret',
            'token' => 'abc123',
            'csrf_token' => 'csrf-123',
            'session_id' => 'sess-123',
            'expected_prefix' => 'deadbeef',
            'provided_prefix' => 'cafebabe',
            'email' => 'john.doe@gmail.com',
            'cpf' => '12345678901',
            'headers' => [
                'Authorization' => 'Bearer abc.def',
                'Cookie' => 'PHPSESSID=123',
                'X-Request-Id' => 'trace-1',
            ],
            'rawBody' => '{"token":"abc"}',
            'payload' => ['cpf' => '12345678901'],
            'path' => '/resetar-senha?selector=sel123&validator=val456&email=john.doe@gmail.com',
            'nested' => [
                'access_token' => 'xyz',
                'user_email' => 'john.doe@gmail.com',
            ],
        ]);

        $this->assertSame('[REDACTED]', $sanitized['password']);
        $this->assertSame('[REDACTED]', $sanitized['token']);
        $this->assertSame('[REDACTED]', $sanitized['csrf_token']);
        $this->assertSame('[REDACTED]', $sanitized['session_id']);
        $this->assertSame('[REDACTED]', $sanitized['expected_prefix']);
        $this->assertSame('[REDACTED]', $sanitized['provided_prefix']);
        $this->assertSame('j***@gmail.com', $sanitized['email']);
        $this->assertSame('***.***.***-**', $sanitized['cpf']);
        $this->assertSame('[REDACTED]', $sanitized['headers']['Authorization']);
        $this->assertSame('[REDACTED]', $sanitized['headers']['Cookie']);
        $this->assertSame('trace-1', $sanitized['headers']['X-Request-Id']);
        $this->assertStringContainsString('[REDACTED_PAYLOAD]', $sanitized['rawBody']);
        $this->assertStringContainsString('[REDACTED_PAYLOAD]', $sanitized['payload']);
        $this->assertStringContainsString('selector=%5BREDACTED%5D', $sanitized['path']);
        $this->assertStringContainsString('validator=%5BREDACTED%5D', $sanitized['path']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['access_token']);
        $this->assertSame('j***@gmail.com', $sanitized['nested']['user_email']);
    }

    public function testSanitizeMessageMasksInlineSecrets(): void
    {
        $sanitized = LogService::sanitizeMessage(
            'Falha com token=abc123 email john.doe@gmail.com cpf: 12345678901 ' .
            'Authorization: Bearer token-123 /reset?selector=sel123&validator=val456'
        );

        $this->assertStringContainsString('token=[REDACTED]', $sanitized);
        $this->assertStringContainsString('j***@gmail.com', $sanitized);
        $this->assertStringContainsString('cpf: ***.***.***-**', $sanitized);
        $this->assertStringContainsString('Bearer [REDACTED]', $sanitized);
        $this->assertStringContainsString('selector=%5BREDACTED%5D', $sanitized);
        $this->assertStringContainsString('validator=%5BREDACTED%5D', $sanitized);
    }

    public function testSafeErrorLogWritesSanitizedMessage(): void
    {
        LogService::safeErrorLog(
            'Erro token=abc123 email john.doe@gmail.com Authorization: Bearer token-123'
        );

        $contents = file_get_contents($this->logFile);

        $this->assertIsString($contents);
        $this->assertStringNotContainsString('abc123', $contents);
        $this->assertStringNotContainsString('john.doe@gmail.com', $contents);
        $this->assertStringContainsString('token=[REDACTED]', $contents);
        $this->assertStringContainsString('j***@gmail.com', $contents);
        $this->assertStringContainsString('Bearer [REDACTED]', $contents);
    }

    public function testCleanupUsesResolvedAtForResolvedOnlyCleanup(): void
    {
        $this->forceDbAvailable();

        $query = Mockery::mock();
        $errorLogModel = Mockery::mock('alias:Application\Models\ErrorLog');

        $errorLogModel
            ->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $query
            ->shouldReceive('whereNotNull')
            ->once()
            ->with('resolved_at')
            ->andReturnSelf();

        $query
            ->shouldReceive('where')
            ->once()
            ->with('resolved_at', '<', Mockery::type(\Carbon\Carbon::class))
            ->andReturnSelf();

        $query
            ->shouldReceive('delete')
            ->once()
            ->andReturn(8);

        $this->assertSame(8, LogService::cleanup(30));
    }

    public function testCleanupCanIncludeUnresolvedLogsUsingCreatedAt(): void
    {
        $this->forceDbAvailable();

        $query = Mockery::mock();
        $errorLogModel = Mockery::mock('alias:Application\Models\ErrorLog');

        $errorLogModel
            ->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $query
            ->shouldReceive('where')
            ->once()
            ->with('created_at', '<', Mockery::type(\Carbon\Carbon::class))
            ->andReturnSelf();

        $query
            ->shouldReceive('delete')
            ->once()
            ->andReturn(21);

        $this->assertSame(21, LogService::cleanup(90, true));
    }

    private function forceDbAvailable(): void
    {
        $dbEnabled = new \ReflectionProperty(LogService::class, 'dbEnabled');
        $dbEnabled->setAccessible(true);
        $dbEnabled->setValue(true);

        $dbChecked = new \ReflectionProperty(LogService::class, 'dbChecked');
        $dbChecked->setAccessible(true);
        $dbChecked->setValue(true);
    }
}
