<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\AI;

use Application\Controllers\Api\AI\UserAiController;
use Application\Core\Exceptions\AuthException;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Lib\Auth;
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
        Auth::resolveUserUsing(null);
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
        Auth::resolveUserUsing(null);
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

    public function testChatFailureDoesNotLeakInternalAiErrorDetails(): void
    {
        $this->seedAuthenticatedSession(1904, 'AI User');
        $_POST['message'] = 'Me ajuda com minhas finanças?';

        $workflowService = Mockery::mock(UserAiWorkflowService::class);
        $workflowService
            ->shouldReceive('chat')
            ->once()
            ->with(1904, 'Me ajuda com minhas finanças?', null)
            ->andReturn([
                'response' => AIResponseDTO::failWithInternalError(
                    'O assistente de IA esta indisponivel no momento. Tente novamente em instantes.',
                    'RuntimeException: cURL error 6: Could not resolve host: api.openai.com',
                    IntentType::CHAT
                ),
                'derived_message' => null,
            ]);

        $controller = new UserAiController($workflowService);

        $response = $controller->chat();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'O assistente de IA esta indisponivel no momento. Tente novamente em instantes.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('Could not resolve host', $response->getContent());
    }

    public function testGetQuotaReturnsRichPlanMetadata(): void
    {
        $this->startIsolatedSession('user-ai-controller-quota-test');

        $user = new TestUserAiQuotaUser();
        $user->id = 1905;
        $user->nome = 'AI User';
        $user->is_admin = 0;
        $user->planCode = 'ultra';
        $user->paidAccess = true;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = 1905;
        $_SESSION['usuario_nome'] = 'AI User';
        $_SESSION['usuario_cache'] = [
            'id' => 1905,
            'data' => $user,
        ];

        Auth::resolveUserUsing(static fn(int $id): ?Usuario => $id === 1905 ? $user : null);

        $controller = new UserAiController();
        $response = $controller->getQuota();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('ultra', $payload['data']['plan']);
        $this->assertTrue($payload['data']['is_pro']);
        $this->assertTrue($payload['data']['is_ultra']);
        $this->assertSame('ULTRA', $payload['data']['plan_label']);
        $this->assertNull($payload['data']['upgrade_target']);
        $this->assertTrue($payload['data']['can_use']);
        $this->assertTrue($payload['data']['chat']['unlimited']);
        $this->assertTrue($payload['data']['categorization']['unlimited']);
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

final class TestUserAiQuotaUser extends Usuario
{
    public ?string $planCode = null;
    public bool $paidAccess = false;

    public function planoAtual()
    {
        return $this->planCode === null ? null : (object) ['code' => $this->planCode];
    }

    public function isPro(): bool
    {
        return $this->paidAccess;
    }
}
