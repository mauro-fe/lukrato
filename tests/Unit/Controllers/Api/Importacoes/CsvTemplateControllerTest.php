<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\CsvTemplateController;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class CsvTemplateControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testReturnsAutomaticCsvTemplateAsAttachment(): void
    {
        $this->seedAuthenticatedUserSession(701, 'Importacoes User');
        $_GET['mode'] = 'auto';

        $controller = new CsvTemplateController();
        $response = $controller->__invoke();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=utf-8', $response->getHeaders()['Content-Type'] ?? null);
        $this->assertStringContainsString('modelo_importacao_automatico.csv', $response->getHeaders()['Content-Disposition'] ?? '');
        $this->assertStringContainsString('sep=;', $response->getContent());
        $this->assertStringContainsString('tipo;data;descricao;valor', $response->getContent());
    }

    public function testReturnsManualCsvTemplateAsAttachment(): void
    {
        $this->seedAuthenticatedUserSession(702, 'Importacoes User');
        $_GET['mode'] = 'manual';

        $controller = new CsvTemplateController();
        $response = $controller->__invoke();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=utf-8', $response->getHeaders()['Content-Type'] ?? null);
        $this->assertStringContainsString('modelo_importacao_manual.csv', $response->getHeaders()['Content-Disposition'] ?? '');
        $this->assertStringContainsString('sep=;', $response->getContent());
        $this->assertStringContainsString('categoria;subcategoria;observacao;id_externo', $response->getContent());
    }

    public function testReturnsCardAutomaticCsvTemplateAsAttachment(): void
    {
        $this->seedAuthenticatedUserSession(7021, 'Importacoes User');
        $_GET['mode'] = 'auto';
        $_GET['target'] = 'cartao';

        $controller = new CsvTemplateController();
        $response = $controller->__invoke();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('modelo_importacao_cartao_automatico.csv', $response->getHeaders()['Content-Disposition'] ?? '');
        $this->assertStringContainsString('sep=;', $response->getContent());
        $this->assertStringContainsString('data;descricao;valor', $response->getContent());
        $this->assertStringNotContainsString('tipo;data;descricao;valor', $response->getContent());
        $this->assertStringContainsString('Estorno parcial;-40,00', $response->getContent());
    }

    public function testReturnsCardManualCsvTemplateAsAttachment(): void
    {
        $this->seedAuthenticatedUserSession(7022, 'Importacoes User');
        $_GET['mode'] = 'manual';
        $_GET['target'] = 'cartao';

        $controller = new CsvTemplateController();
        $response = $controller->__invoke();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('modelo_importacao_cartao_manual.csv', $response->getHeaders()['Content-Disposition'] ?? '');
        $this->assertStringContainsString('sep=;', $response->getContent());
        $this->assertStringContainsString('data;descricao;valor;observacao;id_externo', $response->getContent());
        $this->assertStringContainsString('Compra presencial;FAT-0001', $response->getContent());
    }

    public function testInvalidModeReturnsValidationResponse(): void
    {
        $this->seedAuthenticatedUserSession(703, 'Importacoes User');
        $_GET['mode'] = 'xml';

        $controller = new CsvTemplateController();
        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('Validation failed', $payload['message'] ?? null);
        $this->assertArrayHasKey('mode', $payload['errors'] ?? []);
    }

    public function testInvalidTargetReturnsValidationResponse(): void
    {
        $this->seedAuthenticatedUserSession(704, 'Importacoes User');
        $_GET['mode'] = 'auto';
        $_GET['target'] = 'investimento';

        $controller = new CsvTemplateController();
        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse((bool) ($payload['success'] ?? true));
        $this->assertSame('Validation failed', $payload['message'] ?? null);
        $this->assertArrayHasKey('target', $payload['errors'] ?? []);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('csv-template-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
