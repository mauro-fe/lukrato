<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\DeleteController;
use Application\Models\Usuario;
use Application\Services\Importacao\ImportDeletionService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class DeleteControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_ACCEPT'], $_SERVER['CONTENT_TYPE']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testReturnsSuccessPayloadWhenBatchIsDeleted(): void
    {
        $this->seedAuthenticatedUserSession(951, 'Delete User');

        $service = Mockery::mock(ImportDeletionService::class);
        $service
            ->shouldReceive('deleteBatchForUser')
            ->once()
            ->with(951, 77)
            ->andReturn([
                'success' => true,
                'status' => 200,
                'message' => 'Importação excluída com sucesso.',
                'data' => [
                    'batch_id' => 77,
                    'batch_removed' => true,
                    'deleted_count' => 3,
                    'retained_count' => 0,
                    'retained_items' => [],
                    'batch' => null,
                ],
            ]);

        $controller = new DeleteController($service);
        $response = $controller->__invoke(77);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertTrue((bool) ($payload['data']['batch_removed'] ?? false));
        $this->assertSame(3, (int) ($payload['data']['deleted_count'] ?? 0));
    }

    public function testReturnsFailureStatusFromDeletionService(): void
    {
        $this->seedAuthenticatedUserSession(952, 'Delete User');

        $service = Mockery::mock(ImportDeletionService::class);
        $service
            ->shouldReceive('deleteBatchForUser')
            ->once()
            ->with(952, 88)
            ->andReturn([
                'success' => false,
                'status' => 409,
                'message' => 'O lote ainda está em processamento e não pode ser excluído agora.',
            ]);

        $controller = new DeleteController($service);
        $response = $controller->__invoke(88);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('O lote ainda está em processamento e não pode ser excluído agora.', $payload['message'] ?? null);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('delete-controller-test');

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