<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\AI;

use Application\Controllers\Api\AI\UserAiController;
use Application\Core\Exceptions\AuthException;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\Usuario;
use Application\Services\AI\UserAiWorkflowService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class UserAiControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
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

    public function testChatThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new UserAiController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->chat();
    }

    public function testChatReturnsValidationErrorWhenMessageIsEmpty(): void
    {
        $this->seedAuthenticatedSession(1901, 'AI User');

        $controller = new UserAiController();

        $response = $controller->chat();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Mensagem não pode ser vazia.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testSuggestCategoryReturnsValidationErrorForShortDescription(): void
    {
        $this->seedAuthenticatedSession(1902, 'AI User');
        $_POST['description'] = 'a';

        $controller = new UserAiController();

        $response = $controller->suggestCategory();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Descricao muito curta para sugerir categoria.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testChatReturnsSuccessResponseWithInjectedWorkflowService(): void
    {
        $this->seedAuthenticatedSession(1903, 'AI User');
        $_POST['message'] = 'Me ajuda com minhas finanças?';

        $workflowService = Mockery::mock(UserAiWorkflowService::class);
        $workflowService
            ->shouldReceive('chat')
            ->once()
            ->with(1903, 'Me ajuda com minhas finanças?', null)
            ->andReturn([
                'response' => AIResponseDTO::fromRule(
                    'Claro, vamos analisar.',
                    ['hint' => 'ok'],
                    IntentType::CHAT
                ),
                'derived_message' => null,
            ]);

        $controller = new UserAiController($workflowService);

        $response = $controller->chat();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'response' => 'Claro, vamos analisar.',
                'intent' => 'chat',
                'source' => 'rule',
                'cached' => false,
                'derived_message' => null,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function seedAuthenticatedSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('user-ai-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
