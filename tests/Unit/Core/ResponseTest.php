<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Core\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testSuccessResponseBuildsJsonPayloadWithoutSending(): void
    {
        $response = Response::successResponse(['total' => 150], 'Resumo pronto', 202);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
        $this->assertNull($response->getDownloadFilePath());
        $this->assertSame([
            'success' => true,
            'message' => 'Resumo pronto',
            'data' => ['total' => 150],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testErrorResponseIncludesErrorsWhenProvided(): void
    {
        $response = Response::errorResponse('Dados invalidos', 422, ['month' => 'Formato invalido']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Dados invalidos',
            'errors' => ['month' => 'Formato invalido'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCookieAndForgetCookieQueueOperationsForEmitter(): void
    {
        $response = (new Response())
            ->cookie('remember', 'abc', 30, '/')
            ->forgetCookie('legacy', '/painel');

        $cookies = $response->getCookies();

        $this->assertCount(2, $cookies);
        $this->assertSame('remember', $cookies[0]['name']);
        $this->assertSame('abc', $cookies[0]['value']);
        $this->assertSame('/', $cookies[0]['options']['path']);
        $this->assertSame('legacy', $cookies[1]['name']);
        $this->assertSame('', $cookies[1]['value']);
        $this->assertSame('/painel', $cookies[1]['options']['path']);
    }

    public function testDownloadResponseKeepsFileEmissionDeferred(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'response-test-');
        file_put_contents($filePath, 'relatorio');

        try {
            $response = Response::downloadResponse($filePath, 'relatorio.txt');

            $this->assertSame($filePath, $response->getDownloadFilePath());
            $this->assertSame('', $response->getContent());
            $this->assertSame('attachment; filename="relatorio.txt"', $response->getHeaders()['Content-Disposition']);
            $this->assertSame((string) filesize($filePath), $response->getHeaders()['Content-Length']);
        } finally {
            @unlink($filePath);
        }
    }

    public function testClearOutputBufferMarksResponseForEmitter(): void
    {
        $response = (new Response())
            ->setContent('csv')
            ->clearOutputBuffer();

        $this->assertTrue($response->shouldClearOutputBuffer());
    }

    public function testHtmlResetsPendingOutputBufferFlag(): void
    {
        $response = (new Response())
            ->clearOutputBuffer()
            ->html('<p>ok</p>');

        $this->assertFalse($response->shouldClearOutputBuffer());
        $this->assertSame('<p>ok</p>', $response->getContent());
    }

    public function testHeaderRejectsLineBreakInjection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header.');

        (new Response())->header("X-Test\r\nInjected", 'ok');
    }

    public function testJsonBodySubstitutesInvalidUtf8InsteadOfThrowing500(): void
    {
        $response = (new Response())->jsonBody([
            'message' => "Texto inválido: \xB1",
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        $this->assertStringContainsString('Texto inválido:', $decoded['message']);
    }
}
