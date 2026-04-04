<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\ConfirmController;
use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Models\Usuario;
use Application\Services\Importacao\ImportExecutionService;
use Application\Services\Importacao\ImportQueueService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Importacao\ImportUploadSecurityService;
use Application\Repositories\ContaRepository;
use Application\Services\Plan\PlanLimitService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ConfirmControllerTest extends TestCase
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
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
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

    public function testReturnsForbiddenWhenImportQuotaIsExceeded(): void
    {
        $this->seedAuthenticatedUserSession(901, 'Confirm User');

        $_POST = [
            'source_type' => 'csv',
            'import_target' => 'conta',
        ];

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canUseImportacao')
            ->once()
            ->with(901, 'csv', 'conta')
            ->andReturn([
                'allowed' => false,
                'limit' => 1,
                'used' => 1,
                'remaining' => 0,
                'bucket' => 'import_conta_csv',
                'message' => 'Limite atingido para importação CSV.',
                'upgrade_url' => '/assinatura',
            ]);

        $controller = new ConfirmController(
            Mockery::mock(ImportExecutionService::class),
            Mockery::mock(ImportQueueService::class),
            Mockery::mock(ImportProfileConfigService::class),
            Mockery::mock(ContaRepository::class),
            $planLimitService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertTrue((bool) ($payload['errors']['limit_reached'] ?? false));
        $this->assertSame('import_conta_csv', $payload['errors']['bucket'] ?? null);
        $this->assertSame(1, (int) ($payload['errors']['limit_info']['limit'] ?? 0));
        $this->assertSame(1, (int) ($payload['errors']['limit_info']['used'] ?? 0));
        $this->assertSame(0, (int) ($payload['errors']['limit_info']['remaining'] ?? 1));
    }

    public function testEnqueuesImportWhenAsyncFlagIsEnabled(): void
    {
        $this->seedAuthenticatedUserSession(902, 'Queue User');

        $tmpFile = tempnam(sys_get_temp_dir(), 'ofx');
        $this->assertNotFalse($tmpFile);
        file_put_contents($tmpFile, '<OFX><STMTTRN><TRNAMT>-10.00</TRNAMT><DTPOSTED>20260403</DTPOSTED><NAME>Teste</NAME></STMTTRN></OFX>');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 77,
            'async' => '1',
        ];

        $_FILES = [
            'file' => [
                'name' => 'extrato.ofx',
                'type' => 'application/octet-stream',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => (int) filesize($tmpFile),
            ],
        ];

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canUseImportacao')
            ->once()
            ->with(902, 'ofx', 'conta')
            ->andReturn([
                'allowed' => true,
                'bucket' => 'import_conta_ofx',
                'limit' => 1,
                'used' => 0,
                'remaining' => 1,
            ]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(77, 902)
            ->andReturn(true);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(902, 77, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 77,
                'source_type' => 'ofx',
            ]));

        $queueService = Mockery::mock(ImportQueueService::class);
        $queueService
            ->shouldReceive('enqueueFromUpload')
            ->once()
            ->andReturn([
                'id' => 501,
                'status' => 'queued',
                'source_type' => 'ofx',
                'import_target' => 'conta',
            ]);

        $executionService = Mockery::mock(ImportExecutionService::class);
        $executionService->shouldNotReceive('confirmExecution');

        $controller = new ConfirmController(
            $executionService,
            $queueService,
            $profileService,
            $contaRepository,
            $planLimitService
        );

        try {
            $response = $controller->__invoke();
        } finally {
            @unlink((string) $tmpFile);
        }

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame('queued', $payload['data']['status'] ?? null);
        $this->assertSame(501, (int) ($payload['data']['job']['id'] ?? 0));
    }

    public function testReturnsGenericErrorWhenConfirmFailsUnexpectedly(): void
    {
        $this->seedAuthenticatedUserSession(903, 'Confirm Error User');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 78,
        ];

        $_FILES = [
            'file' => [
                'name' => 'extrato.ofx',
                'type' => 'application/octet-stream',
                'tmp_name' => __FILE__,
                'error' => UPLOAD_ERR_OK,
                'size' => 128,
            ],
        ];

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canUseImportacao')
            ->once()
            ->with(903, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(78, 903)
            ->andReturn(true);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(903, 78, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 78,
                'source_type' => 'ofx',
            ]));

        $uploadSecurityService = Mockery::mock(ImportUploadSecurityService::class);
        $uploadSecurityService
            ->shouldReceive('extractValidatedUpload')
            ->once()
            ->with('ofx', Mockery::type('array'))
            ->andReturn([
                'tmp_name' => __FILE__,
                'filename' => 'extrato.ofx',
            ]);

        $executionService = Mockery::mock(ImportExecutionService::class);
        $executionService
            ->shouldReceive('confirmExecution')
            ->once()
            ->andThrow(new \RuntimeException('SQLSTATE[HY000]: detalhe interno'));

        $queueService = Mockery::mock(ImportQueueService::class);
        $queueService->shouldNotReceive('enqueueFromUpload');

        $controller = new ConfirmController(
            $executionService,
            $queueService,
            $profileService,
            $contaRepository,
            $planLimitService,
            $uploadSecurityService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Não foi possível processar a importação agora. Tente novamente em instantes.', $payload['message'] ?? null);
        $this->assertStringNotContainsString('SQLSTATE', (string) ($payload['message'] ?? ''));
        $this->assertNotEmpty($payload['request_id'] ?? null);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('confirm-controller-test');

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
