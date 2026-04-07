<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\ConfirmController;
use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\ServiceResultDTO;
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

    /** @var string|false|null */
    private $previousConfirmAsyncDefaultEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->previousConfirmAsyncDefaultEnv = $_ENV['IMPORTACOES_CONFIRM_ASYNC_DEFAULT']
            ?? getenv('IMPORTACOES_CONFIRM_ASYNC_DEFAULT');
        $this->setConfirmAsyncDefaultEnv(null);
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
        if ($this->previousConfirmAsyncDefaultEnv === false || $this->previousConfirmAsyncDefaultEnv === null) {
            $this->setConfirmAsyncDefaultEnv(null);
        } else {
            $this->setConfirmAsyncDefaultEnv((string) $this->previousConfirmAsyncDefaultEnv);
        }
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
        $executionService
            ->shouldReceive('prepareExecution')
            ->once()
            ->andReturn($this->previewReadyResult());
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

    public function testProcessesImportSynchronouslyWhenAsyncFlagIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(904, 'Sync User');

        $tmpFile = tempnam(sys_get_temp_dir(), 'ofx');
        $this->assertNotFalse($tmpFile);
        file_put_contents($tmpFile, '<OFX><STMTTRN><TRNAMT>-10.00</TRNAMT><DTPOSTED>20260403</DTPOSTED><NAME>Teste</NAME></STMTTRN></OFX>');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 79,
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
            ->with(904, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(79, 904)
            ->andReturn(true);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(904, 79, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 79,
                'source_type' => 'ofx',
            ]));

        $uploadSecurityService = Mockery::mock(ImportUploadSecurityService::class);
        $uploadSecurityService
            ->shouldReceive('extractValidatedUpload')
            ->once()
            ->with('ofx', Mockery::type('array'))
            ->andReturn([
                'tmp_name' => $tmpFile,
                'filename' => 'extrato.ofx',
            ]);

        $queueService = Mockery::mock(ImportQueueService::class);
        $queueService->shouldNotReceive('enqueueFromUpload');

        $executionService = Mockery::mock(ImportExecutionService::class);
        $executionService
            ->shouldReceive('prepareExecution')
            ->once()
            ->andReturn($this->previewReadyResult());
        $executionService
            ->shouldReceive('confirmExecution')
            ->once()
            ->andReturn(ServiceResultDTO::ok('Importação confirmada com sucesso.', [
                'status' => 'processed',
                'batch' => ['id' => 601],
                'summary' => [
                    'total_rows' => 1,
                    'imported_rows' => 1,
                    'duplicate_rows' => 0,
                    'error_rows' => 0,
                ],
            ]));

        $controller = new ConfirmController(
            $executionService,
            $queueService,
            $profileService,
            $contaRepository,
            $planLimitService,
            $uploadSecurityService
        );

        try {
            $response = $controller->__invoke();
        } finally {
            @unlink((string) $tmpFile);
        }

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame('processed', $payload['data']['status'] ?? null);
        $this->assertSame(601, (int) ($payload['data']['batch']['id'] ?? 0));
    }

    public function testAllowsCsvCardConfirmToReachCardValidation(): void
    {
        $this->seedAuthenticatedUserSession(9041, 'Confirm Card Csv User');

        $_POST = [
            'source_type' => 'csv',
            'import_target' => 'cartao',
        ];

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canUseImportacao')
            ->once()
            ->with(9041, 'csv', 'cartao')
            ->andReturn(['allowed' => true]);

        $controller = new ConfirmController(
            Mockery::mock(ImportExecutionService::class),
            Mockery::mock(ImportQueueService::class),
            Mockery::mock(ImportProfileConfigService::class),
            Mockery::mock(ContaRepository::class),
            $planLimitService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Cartão obrigatório para confirmar importação de fatura.', $payload['errors']['cartao_id'] ?? null);
    }

    public function testEnqueuesImportWhenAsyncDefaultEnvironmentIsEnabled(): void
    {
        $this->setConfirmAsyncDefaultEnv('true');
        $this->seedAuthenticatedUserSession(905, 'Async Env User');

        $tmpFile = tempnam(sys_get_temp_dir(), 'ofx');
        $this->assertNotFalse($tmpFile);
        file_put_contents($tmpFile, '<OFX><STMTTRN><TRNAMT>-10.00</TRNAMT><DTPOSTED>20260403</DTPOSTED><NAME>Teste</NAME></STMTTRN></OFX>');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 80,
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
            ->with(905, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(80, 905)
            ->andReturn(true);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(905, 80, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 80,
                'source_type' => 'ofx',
            ]));

        $queueService = Mockery::mock(ImportQueueService::class);
        $queueService
            ->shouldReceive('enqueueFromUpload')
            ->once()
            ->andReturn([
                'id' => 701,
                'status' => 'queued',
                'source_type' => 'ofx',
                'import_target' => 'conta',
            ]);

        $executionService = Mockery::mock(ImportExecutionService::class);
        $executionService
            ->shouldReceive('prepareExecution')
            ->once()
            ->andReturn($this->previewReadyResult());
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
        $this->assertSame(701, (int) ($payload['data']['job']['id'] ?? 0));
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
            ->shouldReceive('prepareExecution')
            ->once()
            ->andReturn($this->previewReadyResult());
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

    public function testDoesNotEnqueueImportWhenPreviewDetectsTargetMismatch(): void
    {
        $this->seedAuthenticatedUserSession(906, 'Mismatch User');

        $tmpFile = tempnam(sys_get_temp_dir(), 'ofx');
        $this->assertNotFalse($tmpFile);
        file_put_contents($tmpFile, $this->sampleCardOfx());

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 81,
            'async' => '1',
        ];

        $_FILES = [
            'file' => [
                'name' => 'fatura.ofx',
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
            ->with(906, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(81, 906)
            ->andReturn(true);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(906, 81, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 81,
                'source_type' => 'ofx',
            ]));

        $queueService = Mockery::mock(ImportQueueService::class);
        $queueService->shouldNotReceive('enqueueFromUpload');

        $executionService = Mockery::mock(ImportExecutionService::class);
        $executionService
            ->shouldReceive('prepareExecution')
            ->once()
            ->andReturn($this->previewBlockedResult(
                'Este OFX parece ser de cartão/fatura. Troque o alvo para Cartão/fatura e selecione o cartão correto antes de confirmar a importação.'
            ));
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

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame(
            'Este OFX parece ser de cartão/fatura. Troque o alvo para Cartão/fatura e selecione o cartão correto antes de confirmar a importação.',
            $payload['errors']['file'] ?? null
        );
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

    private function setConfirmAsyncDefaultEnv(?string $value): void
    {
        if ($value === null) {
            unset($_ENV['IMPORTACOES_CONFIRM_ASYNC_DEFAULT']);
            putenv('IMPORTACOES_CONFIRM_ASYNC_DEFAULT');

            return;
        }

        $_ENV['IMPORTACOES_CONFIRM_ASYNC_DEFAULT'] = $value;
        putenv('IMPORTACOES_CONFIRM_ASYNC_DEFAULT=' . $value);
    }

    private function previewReadyResult(): ServiceResultDTO
    {
        return ServiceResultDTO::ok('Preview de importação preparado.', [
            'status' => 'preview_ready',
            'can_persist' => false,
            'next_step' => 'Confirme a importação para persistir os dados.',
            'preview' => [
                'source_type' => 'ofx',
                'import_target' => 'conta',
                'filename' => 'extrato.ofx',
                'total_rows' => 1,
                'rows' => [
                    [
                        'date' => '2026-04-03',
                        'amount' => 10.0,
                        'type' => 'despesa',
                        'description' => 'Teste',
                    ],
                ],
                'warnings' => [],
                'errors' => [],
                'can_confirm' => true,
            ],
        ]);
    }

    private function previewBlockedResult(string $message): ServiceResultDTO
    {
        return ServiceResultDTO::ok('Preview de importação preparado.', [
            'status' => 'preview_ready',
            'can_persist' => false,
            'next_step' => 'Confirme a importação para persistir os dados.',
            'preview' => [
                'source_type' => 'ofx',
                'import_target' => 'conta',
                'filename' => 'fatura.ofx',
                'total_rows' => 1,
                'rows' => [
                    [
                        'date' => '2026-04-03',
                        'amount' => 10.0,
                        'type' => 'despesa',
                        'description' => 'Teste',
                    ],
                ],
                'warnings' => [],
                'errors' => [$message],
                'can_confirm' => false,
            ],
        ]);
    }

    private function sampleCardOfx(): string
    {
        return <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102

<OFX>
    <CREDITCARDMSGSRSV1>
        <CCSTMTTRNRS>
            <CCSTMTRS>
                <CCACCTFROM>
                    <ACCTID>999900001234
                </CCACCTFROM>
                <BANKTRANLIST>
                    <STMTTRN>
                        <TRNTYPE>DEBIT
                        <DTPOSTED>20260403000000[-3:BRT]
                        <TRNAMT>-10.00
                        <FITID>CARD-TEST-1
                        <NAME>Teste
                    </STMTTRN>
                </BANKTRANLIST>
            </CCSTMTRS>
        </CCSTMTTRNRS>
    </CREDITCARDMSGSRSV1>
</OFX>
OFX;
    }
}
