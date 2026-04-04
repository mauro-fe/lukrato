<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use Application\Services\Importacao\ImportUploadSecurityService;
use PHPUnit\Framework\TestCase;

class ImportUploadSecurityServiceTest extends TestCase
{
    public function testRejectsOfxWithoutMinimumSignature(): void
    {
        $service = new ImportUploadSecurityService();
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload-ofx');
        $this->assertNotFalse($tmpFile);

        file_put_contents($tmpFile, 'arquivo qualquer');

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Arquivo OFX sem assinatura mínima válida.');

            $service->extractValidatedUpload('ofx', [
                'name' => 'extrato.ofx',
                'type' => 'application/octet-stream',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => (int) filesize($tmpFile),
            ]);
        } finally {
            @unlink((string) $tmpFile);
        }
    }

    public function testLoadsValidCsvUpload(): void
    {
        $service = new ImportUploadSecurityService();
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload-csv');
        $this->assertNotFalse($tmpFile);

        file_put_contents($tmpFile, implode("\n", [
            'tipo;data;descricao;valor',
            'despesa;01/03/2026;Mercado;150,25',
        ]));

        try {
            $result = $service->extractValidatedUpload('csv', [
                'name' => 'lancamentos.csv',
                'type' => 'text/csv',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => (int) filesize($tmpFile),
            ], true);
        } finally {
            @unlink((string) $tmpFile);
        }

        $this->assertSame('lancamentos.csv', $result['filename']);
        $this->assertSame('csv', pathinfo((string) $result['filename'], PATHINFO_EXTENSION));
        $this->assertStringContainsString('Mercado', (string) ($result['contents'] ?? ''));
    }
}
