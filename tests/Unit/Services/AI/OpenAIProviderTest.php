<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Providers\OpenAIHttpClient;
use Application\Services\AI\Providers\OpenAIProvider;
use PHPUnit\Framework\TestCase;

class OpenAIProviderTest extends TestCase
{
    private string $origApiKey;
    private string $origModel;
    private string|false $origApiKeyGetenv = false;
    private string|false $origModelGetenv = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->origApiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->origModel = $_ENV['OPENAI_MODEL'] ?? '';
        $this->origApiKeyGetenv = getenv('OPENAI_API_KEY');
        $this->origModelGetenv = getenv('OPENAI_MODEL');
    }

    protected function tearDown(): void
    {
        $_ENV['OPENAI_API_KEY'] = $this->origApiKey;
        $_ENV['OPENAI_MODEL'] = $this->origModel;
        $this->restoreEnv('OPENAI_API_KEY', $this->origApiKeyGetenv);
        $this->restoreEnv('OPENAI_MODEL', $this->origModelGetenv);
        parent::tearDown();
    }

    public function testConstructionAcceptsEmptyApiKey(): void
    {
        $_ENV['OPENAI_API_KEY'] = '';
        putenv('OPENAI_API_KEY=');
        $provider = new OpenAIProvider();
        $this->assertInstanceOf(OpenAIProvider::class, $provider);
    }

    public function testGetModelReturnsDefault(): void
    {
        unset($_ENV['OPENAI_MODEL']);
        putenv('OPENAI_MODEL');
        $provider = new OpenAIProvider();
        $this->assertNotEmpty($provider->getModel());
    }

    public function testGetModelReturnsConfigured(): void
    {
        $_ENV['OPENAI_MODEL'] = 'gpt-4o';
        putenv('OPENAI_MODEL=gpt-4o');
        $provider = new OpenAIProvider();
        $this->assertEquals('gpt-4o', $provider->getModel());
    }

    public function testGetLastMetaEmptyInitially(): void
    {
        $_ENV['OPENAI_API_KEY'] = 'test-key';
        putenv('OPENAI_API_KEY=test-key');
        $provider = new OpenAIProvider();
        $meta = $provider->getLastMeta();
        $this->assertIsArray($meta);
        $this->assertEmpty($meta);
    }

    public function testGetLastRateLimitsEmptyInitially(): void
    {
        $_ENV['OPENAI_API_KEY'] = 'test-key';
        putenv('OPENAI_API_KEY=test-key');
        $provider = new OpenAIProvider();
        $limits = $provider->getLastRateLimits();
        $this->assertIsArray($limits);
    }

    public function testConstructionUsesNamedOpenAiHttpClientByDefault(): void
    {
        $_ENV['OPENAI_API_KEY'] = 'test-key';
        putenv('OPENAI_API_KEY=test-key');

        $provider = new OpenAIProvider();

        $client = $this->readProperty($provider, 'client');

        $this->assertInstanceOf(OpenAIHttpClient::class, $client);
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    private function restoreEnv(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            return;
        }

        putenv($key . '=' . $value);
    }
}
