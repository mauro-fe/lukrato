<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Providers\OpenAIProvider;
use PHPUnit\Framework\TestCase;

class OpenAIProviderTest extends TestCase
{
    private string $origApiKey;
    private string $origModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->origApiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->origModel = $_ENV['OPENAI_MODEL'] ?? '';
    }

    protected function tearDown(): void
    {
        $_ENV['OPENAI_API_KEY'] = $this->origApiKey;
        $_ENV['OPENAI_MODEL'] = $this->origModel;
        parent::tearDown();
    }

    public function testConstructionAcceptsEmptyApiKey(): void
    {
        $_ENV['OPENAI_API_KEY'] = '';
        $provider = new OpenAIProvider();
        $this->assertInstanceOf(OpenAIProvider::class, $provider);
    }

    public function testGetModelReturnsDefault(): void
    {
        unset($_ENV['OPENAI_MODEL']);
        $provider = new OpenAIProvider();
        $this->assertNotEmpty($provider->getModel());
    }

    public function testGetModelReturnsConfigured(): void
    {
        $_ENV['OPENAI_MODEL'] = 'gpt-4o';
        $provider = new OpenAIProvider();
        $this->assertEquals('gpt-4o', $provider->getModel());
    }

    public function testGetLastMetaEmptyInitially(): void
    {
        $_ENV['OPENAI_API_KEY'] = 'test-key';
        $provider = new OpenAIProvider();
        $meta = $provider->getLastMeta();
        $this->assertIsArray($meta);
        $this->assertEmpty($meta);
    }

    public function testGetLastRateLimitsEmptyInitially(): void
    {
        $_ENV['OPENAI_API_KEY'] = 'test-key';
        $provider = new OpenAIProvider();
        $limits = $provider->getLastRateLimits();
        $this->assertIsArray($limits);
    }
}
