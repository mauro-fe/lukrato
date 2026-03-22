<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use Application\Services\Auth\TokenPairService;
use PHPUnit\Framework\TestCase;

class TokenPairServiceTest extends TestCase
{
    public function testIssueCreatesSelectorValidatorAndHash(): void
    {
        $service = new TokenPairService();

        $tokenPair = $service->issue();

        $this->assertSame(16, strlen($tokenPair['selector']));
        $this->assertSame(64, strlen($tokenPair['validator']));
        $this->assertSame(hash('sha256', $tokenPair['validator']), $tokenPair['token_hash']);
    }

    public function testMatchesReturnsTrueOnlyForCorrectValidator(): void
    {
        $service = new TokenPairService();
        $tokenPair = $service->issue();

        $this->assertTrue($service->matches($tokenPair['validator'], $tokenPair['token_hash']));
        $this->assertFalse($service->matches('wrong-validator', $tokenPair['token_hash']));
        $this->assertFalse($service->matches($tokenPair['validator'], null));
    }
}
