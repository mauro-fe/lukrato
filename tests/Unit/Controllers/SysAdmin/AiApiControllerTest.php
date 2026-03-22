<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\SysAdmin;

use Application\Controllers\SysAdmin\AiApiController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class AiApiControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testHealthProxyThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new AiApiController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->healthProxy();
    }

    public function testQuotaReturnsBadRequestWhenOpenAiKeyIsMissing(): void
    {
        $this->seedAuthenticatedSession(2201, 'AI Admin', true);
        $_ENV['AI_PROVIDER'] = 'openai';
        unset($_ENV['OPENAI_API_KEY']);

        $controller = new AiApiController();

        $response = $controller->quota();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'OPENAI_API_KEY nao configurada',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testChatReturnsValidationErrorWhenMessageIsEmpty(): void
    {
        $this->seedAuthenticatedSession(2202, 'AI Admin', true);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = new AiApiController();

        $response = $controller->chat();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Mensagem nao pode ser vazia',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function seedAuthenticatedSession(int $userId, string $name, bool $isAdmin): void
    {
        $this->startIsolatedSession('ai-api-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = $isAdmin ? 1 : 0;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
