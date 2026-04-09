<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Referral;

use PHPUnit\Framework\TestCase;

class ReferralEnvBoundaryTest extends TestCase
{
    public function testReferralServicesDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Services/Referral/ReferralAntifraudService.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents, sprintf('Nao foi possivel ler %s', $file));
            $this->assertDoesNotMatchRegularExpression(
                '/\$_ENV|getenv\s*\(/i',
                $contents,
                sprintf('%s nao deve ler ambiente diretamente.', $file)
            );
        }
    }
}
