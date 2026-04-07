<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\PreviewController;
use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Models\Usuario;
use Application\Services\Importacao\ImportPreviewService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Importacao\ImportUploadSecurityService;
use Application\Repositories\ContaRepository;
use Application\Services\Plan\PlanLimitService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class PreviewControllerTest extends TestCase
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
        $this->seedAuthenticatedUserSession(801, 'Preview User');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'cartao',
        ];

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canUseImportacao')
            ->once()
            ->with(801, 'ofx', 'cartao')
            ->andReturn([
                'allowed' => false,
                'limit' => 1,
                'used' => 1,
                'remaining' => 0,
                'bucket' => 'import_cartao_ofx',
                'message' => 'Limite atingido para importação de fatura.',
                'upgrade_url' => '/assinatura',
            ]);

        $controller = new PreviewController(
            Mockery::mock(ImportPreviewService::class),
            Mockery::mock(ImportProfileConfigService::class),
            Mockery::mock(ContaRepository::class),
            $planLimitService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertTrue((bool) ($payload['errors']['limit_reached'] ?? false));
        $this->assertSame('import_cartao_ofx', $payload['errors']['bucket'] ?? null);
        $this->assertSame(1, (int) ($payload['errors']['limit_info']['limit'] ?? 0));
        $this->assertSame(1, (int) ($payload['errors']['limit_info']['used'] ?? 0));
        $this->assertSame(0, (int) ($payload['errors']['limit_info']['remaining'] ?? 1));
    }

    public function testReturnsValidationErrorWhenUploadGuardRejectsFile(): void
    {
        $this->seedAuthenticatedUserSession(802, 'Preview Guard User');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 45,
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
            ->with(802, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(45, 802)
            ->andReturn(true);

        $uploadSecurityService = Mockery::mock(ImportUploadSecurityService::class);
        $uploadSecurityService
            ->shouldReceive('extractValidatedUpload')
            ->once()
            ->with('ofx', Mockery::type('array'), true)
            ->andThrow(new \InvalidArgumentException('Arquivo OFX sem assinatura mínima válida.'));

        $controller = new PreviewController(
            Mockery::mock(ImportPreviewService::class),
            Mockery::mock(ImportProfileConfigService::class),
            $contaRepository,
            $planLimitService,
            $uploadSecurityService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Arquivo OFX sem assinatura mínima válida.', $payload['errors']['file'] ?? null);
    }

    public function testReturnsGenericErrorWhenPreviewFailsUnexpectedly(): void
    {
        $this->seedAuthenticatedUserSession(803, 'Preview Error User');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 46,
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
            ->with(803, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(46, 803)
            ->andReturn(true);

        $uploadSecurityService = Mockery::mock(ImportUploadSecurityService::class);
        $uploadSecurityService
            ->shouldReceive('extractValidatedUpload')
            ->once()
            ->with('ofx', Mockery::type('array'), true)
            ->andReturn([
                'filename' => 'extrato.ofx',
                'contents' => '<OFX><STMTTRN></STMTTRN></OFX>',
            ]);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(803, 46, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 46,
                'source_type' => 'ofx',
            ]));

        $previewService = Mockery::mock(ImportPreviewService::class);
        $previewService
            ->shouldReceive('preview')
            ->once()
            ->andThrow(new \RuntimeException('SQLSTATE[HY000]: detalhe interno'));

        $controller = new PreviewController(
            $previewService,
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

    public function testForwardsOptionalCategorizeFlagToPreviewService(): void
    {
        $this->seedAuthenticatedUserSession(8031, 'Preview Categorize User');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 461,
            'categorize_preview' => '1',
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
            ->with(8031, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(461, 8031)
            ->andReturn(true);

        $uploadSecurityService = Mockery::mock(ImportUploadSecurityService::class);
        $uploadSecurityService
            ->shouldReceive('extractValidatedUpload')
            ->once()
            ->with('ofx', Mockery::type('array'), true)
            ->andReturn([
                'filename' => 'extrato.ofx',
                'contents' => '<OFX><STMTTRN></STMTTRN></OFX>',
            ]);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(8031, 461, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 461,
                'source_type' => 'ofx',
            ]));

        $previewService = Mockery::mock(ImportPreviewService::class);
        $previewService
            ->shouldReceive('preview')
            ->once()
            ->withArgs(static function (
                string $sourceType,
                string $contents,
                ImportProfileConfigDTO $profile,
                string $filename,
                string $importTarget,
                ?int $cartaoId,
                ?int $userId,
                bool $categorizePreview
            ): bool {
                return $sourceType === 'ofx'
                    && $contents === '<OFX><STMTTRN></STMTTRN></OFX>'
                    && $profile->contaId === 461
                    && $filename === 'extrato.ofx'
                    && $importTarget === 'conta'
                    && $cartaoId === null
                    && $userId === 8031
                    && $categorizePreview === true;
            })
            ->andReturn([
                'source_type' => 'ofx',
                'import_target' => 'conta',
                'conta_id' => 461,
                'cartao_id' => null,
                'filename' => 'extrato.ofx',
                'detected_import_target' => 'conta',
                'target_mismatch' => false,
                'total_rows' => 1,
                'rows' => [],
                'warnings' => [],
                'errors' => [],
                'can_confirm' => true,
            ]);

        $controller = new PreviewController(
            $previewService,
            $profileService,
            $contaRepository,
            $planLimitService,
            $uploadSecurityService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
    }

    public function testAllowsCsvCardPreviewToReachCardValidation(): void
    {
        $this->seedAuthenticatedUserSession(8032, 'Preview Card Csv User');

        $_POST = [
            'source_type' => 'csv',
            'import_target' => 'cartao',
        ];

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canUseImportacao')
            ->once()
            ->with(8032, 'csv', 'cartao')
            ->andReturn(['allowed' => true]);

        $controller = new PreviewController(
            Mockery::mock(ImportPreviewService::class),
            Mockery::mock(ImportProfileConfigService::class),
            Mockery::mock(ContaRepository::class),
            $planLimitService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Cartão obrigatório para preview de fatura.', $payload['errors']['cartao_id'] ?? null);
    }

    public function testReturnsMismatchMetadataWhenCardOfxIsSentToAccountPreview(): void
    {
        $this->seedAuthenticatedUserSession(804, 'Preview Mismatch User');

        $_POST = [
            'source_type' => 'ofx',
            'import_target' => 'conta',
            'conta_id' => 47,
        ];

        $_FILES = [
            'file' => [
                'name' => 'fatura.ofx',
                'type' => 'application/octet-stream',
                'tmp_name' => __FILE__,
                'error' => UPLOAD_ERR_OK,
                'size' => 256,
            ],
        ];

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canUseImportacao')
            ->once()
            ->with(804, 'ofx', 'conta')
            ->andReturn(['allowed' => true]);

        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository
            ->shouldReceive('belongsToUser')
            ->once()
            ->with(47, 804)
            ->andReturn(true);

        $uploadSecurityService = Mockery::mock(ImportUploadSecurityService::class);
        $uploadSecurityService
            ->shouldReceive('extractValidatedUpload')
            ->once()
            ->with('ofx', Mockery::type('array'), true)
            ->andReturn([
                'filename' => 'fatura.ofx',
                'contents' => $this->sampleCardOfx(),
            ]);

        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $profileService
            ->shouldReceive('getForUserAndConta')
            ->once()
            ->with(804, 47, 'ofx')
            ->andReturn(ImportProfileConfigDTO::fromArray([
                'conta_id' => 47,
                'source_type' => 'ofx',
            ]));

        $controller = new PreviewController(
            new ImportPreviewService(),
            $profileService,
            $contaRepository,
            $planLimitService,
            $uploadSecurityService
        );

        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame('cartao', $data['detected_import_target'] ?? null);
        $this->assertTrue((bool) ($data['target_mismatch'] ?? false));
        $this->assertFalse((bool) ($data['can_confirm'] ?? true));
        $this->assertSame(2, (int) ($data['total_rows'] ?? 0));
        $this->assertStringContainsString('cartão/fatura', (string) ($data['errors'][0] ?? ''));
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('preview-controller-test');

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

    private function sampleCardOfx(): string
    {
        return <<<'OFX'
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

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
                        <DTPOSTED>20260305000000[-3:BRT]
                        <TRNAMT>-220.90
                        <FITID>CARD-OFX-1
                        <NAME>Restaurante
                    </STMTTRN>
                    <STMTTRN>
                        <TRNTYPE>CREDIT
                        <DTPOSTED>20260306000000[-3:BRT]
                        <TRNAMT>40.00
                        <FITID>CARD-OFX-2
                        <NAME>Estorno parcial
                    </STMTTRN>
                </BANKTRANLIST>
            </CCSTMTRS>
        </CCSTMTTRNRS>
    </CREDITCARDMSGSRSV1>
</OFX>
OFX;
    }
}
