<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use Application\Services\Infrastructure\CpfProtectionService;
use PHPUnit\Framework\TestCase;

class CpfProtectionServiceTest extends TestCase
{
    private string|null $previousKey = null;
    private string|null $previousAppKey = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousKey = $_ENV['CPF_ENCRYPTION_KEY'] ?? null;
        $this->previousAppKey = $_ENV['APP_KEY'] ?? null;
        $_ENV['CPF_ENCRYPTION_KEY'] = 'base64:' . base64_encode(random_bytes(32));
    }

    protected function tearDown(): void
    {
        if ($this->previousKey === null) {
            unset($_ENV['CPF_ENCRYPTION_KEY']);
        } else {
            $_ENV['CPF_ENCRYPTION_KEY'] = $this->previousKey;
        }

        if ($this->previousAppKey === null) {
            unset($_ENV['APP_KEY']);
        } else {
            $_ENV['APP_KEY'] = $this->previousAppKey;
        }

        parent::tearDown();
    }

    public function testEncryptAndDecryptCpfRoundTrip(): void
    {
        $service = new CpfProtectionService();
        $cpf = '529.982.247-25';

        $encrypted = $service->encrypt($cpf);
        $decrypted = $service->decrypt($encrypted);

        $this->assertNotSame($cpf, $encrypted);
        $this->assertSame('52998224725', $decrypted);
    }

    public function testHashUsesNormalizedDigits(): void
    {
        $service = new CpfProtectionService();

        $hashA = $service->hash('529.982.247-25');
        $hashB = $service->hash('52998224725');

        $this->assertSame($hashA, $hashB);
        $this->assertSame(hash('sha256', '52998224725'), $hashA);
    }

    public function testEncryptFallsBackToAppKeyWhenCpfSpecificKeyIsMissing(): void
    {
        unset($_ENV['CPF_ENCRYPTION_KEY']);
        $_ENV['APP_KEY'] = 'base64:' . base64_encode(random_bytes(32));

        $service = new CpfProtectionService();
        $cpf = '529.982.247-25';

        $encrypted = $service->encrypt($cpf);

        $this->assertSame('52998224725', $service->decrypt($encrypted));
    }
}
