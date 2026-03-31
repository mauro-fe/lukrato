<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\ContactController;
use Application\Services\Communication\MailService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ContactControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
        parent::tearDown();
    }

    public function testSendReturnsValidationErrorWhenRequiredFieldsAreMissing(): void
    {
        $controller = new ContactController(Mockery::mock(MailService::class));

        $response = $controller->send();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'message' => 'Preencha os campos obrigatórios.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testSendReturnsSuccessResponseWhenMailIsSent(): void
    {
        $_POST = [
            'nome' => 'Maria',
            'email' => 'maria@example.com',
            'whatsapp' => '11999999999',
            'assunto' => 'Ajuda',
            'mensagem' => 'Preciso de suporte',
        ];

        $mail = Mockery::mock(MailService::class);
        $mail
            ->shouldReceive('send')
            ->once()
            ->andReturnTrue();

        $controller = new ContactController($mail);

        $response = $controller->send();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Mensagem enviada com sucesso.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testSendReturnsErrorResponseWhenMailFails(): void
    {
        $_POST = [
            'nome' => 'Maria',
            'email' => 'maria@example.com',
            'whatsapp' => '11999999999',
            'assunto' => 'Ajuda',
            'mensagem' => 'Preciso de suporte',
        ];

        $mail = Mockery::mock(MailService::class);
        $mail
            ->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('smtp down'));

        $controller = new ContactController($mail);

        $response = $controller->send();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Não foi possível enviar sua mensagem agora. Tente novamente.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
