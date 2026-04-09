<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ManualCompositionAllowlistTest extends TestCase
{
    public function testLegacyManualCompositionPatternsStayRestrictedToAllowlist(): void
    {
        $allowedFiles = [
            'Application/Container/ApplicationContainer.php',
            'Application/Services/Infrastructure/LogChannelFactory.php',
        ];

        $patterns = [
            '/new\s+Client\s*\(/',
            '/\(new\s+(?:[A-Za-z0-9_]+(?:\\\\[A-Za-z0-9_]+)*)Provider\(\)\)->register\(/',
            '/new\s+ResponseEmitter\s*\(/',
            '/new\s+RequestValidator\s*\(/',
            '/new\s+HttpExceptionHandler\s*\(/',
            '/new\s+ErrorHandler\s*\(/',
            '/new\s+(?:Logger|StreamHandler)\s*\(/',
            '/new\s+(?:[A-Z][A-Za-z0-9_]*(?:\\\\[A-Za-z0-9_]+)*(?:Service|UseCase|Handler|Check|Validator|Repository|Provider|Factory))\s*\(/',
        ];

        $offenders = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('Application', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $fileInfo->getPathname());
            $content = (string) file_get_contents($path);

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content) !== 1) {
                    continue;
                }

                if (!in_array($path, $allowedFiles, true)) {
                    $offenders[] = $path;
                }

                break;
            }
        }

        $offenders = array_values(array_unique($offenders));
        sort($offenders);

        $this->assertSame(
            [],
            $offenders,
            "Composição manual fora da allowlist:\n" . implode("\n", $offenders)
        );
    }
}