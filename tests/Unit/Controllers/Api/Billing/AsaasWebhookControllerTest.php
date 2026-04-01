<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Billing;

use Application\Controllers\Api\Billing\AsaasWebhookController;
use Application\Services\Billing\AsaasService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AsaasWebhookControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        parent::tearDown();
    }

    public function testReceiveReturnsOkWhenWebhookValidationFails(): void
    {
        $headers = ['Asaas-Access-Token' => 'invalid'];
        $rawBody = '{"event":"PAYMENT_RECEIVED"}';

        $asaas = Mockery::mock(AsaasService::class);
        $asaas
            ->shouldReceive('validateWebhookRequest')
            ->once()
            ->with($headers, $rawBody)
            ->andReturnFalse();

        $controller = $this->buildController($asaas, $headers, $rawBody);

        $response = $controller->receive();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function testReceiveReturnsOkWhenPayloadIsInvalidJson(): void
    {
        $headers = ['Asaas-Access-Token' => 'valid'];
        $rawBody = '{"event":';

        $asaas = Mockery::mock(AsaasService::class);
        $asaas
            ->shouldReceive('validateWebhookRequest')
            ->once()
            ->with($headers, $rawBody)
            ->andReturnTrue();

        $controller = $this->buildController($asaas, $headers, $rawBody);

        $response = $controller->receive();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function testReceiveReturnsOkWhenEventIsMissing(): void
    {
        $headers = ['Asaas-Access-Token' => 'valid'];
        $rawBody = '{"id":"evt_1"}';

        $asaas = Mockery::mock(AsaasService::class);
        $asaas
            ->shouldReceive('validateWebhookRequest')
            ->once()
            ->with($headers, $rawBody)
            ->andReturnTrue();

        $controller = $this->buildController($asaas, $headers, $rawBody);

        $response = $controller->receive();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function buildController(AsaasService $asaas, array $headers, string $rawBody): AsaasWebhookController
    {
        return new class($asaas, $headers, $rawBody) extends AsaasWebhookController {
            /**
             * @param array<string, mixed> $headers
             */
            public function __construct(
                ?AsaasService $asaas,
                private array $headers,
                private string $rawBody
            ) {
                parent::__construct($asaas);
            }

            protected function readRequestHeaders(): array
            {
                return $this->headers;
            }

            protected function readRawBody(): string
            {
                return $this->rawBody;
            }
        };
    }
}
