<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\Importacao\NormalizedImportRowDTO;
use Application\Services\Importacao\Contracts\ImportParserInterface;
use Application\Services\Importacao\ImportExecutionService;
use Application\Services\Importacao\ImportHistoryService;
use Application\Services\Importacao\ImportPreviewService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Importacao\ImportRowCategorizationService;
use Application\Services\Importacao\Parsers\CsvImportParser;
use Application\Services\Importacao\Parsers\OfxImportParser;
use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class ImportPreviewServiceTest extends TestCase
{
    /**
     * @var array<int, int>
     */
    private array $cleanupUserIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            $schema = DB::schema();

            if ($schema->hasTable('user_category_rules')) {
                DB::table('user_category_rules')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('faturas_cartao_itens')) {
                DB::table('faturas_cartao_itens')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('faturas')) {
                DB::table('faturas')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('cartoes_credito')) {
                DB::table('cartoes_credito')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('importacao_itens')) {
                DB::table('importacao_itens')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('importacao_lotes')) {
                DB::table('importacao_lotes')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('importacao_perfis')) {
                DB::table('importacao_perfis')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('lancamentos')) {
                DB::table('lancamentos')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('categorias')) {
                DB::table('categorias')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('contas')) {
                DB::table('contas')->whereIn('user_id', $this->cleanupUserIds)->delete();
            }
            if ($schema->hasTable('usuarios')) {
                DB::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
            }
        }

        $this->cleanupUserIds = [];
        parent::tearDown();
    }

    public function testPreviewUsesMatchingParserAndReturnsNormalizedPayload(): void
    {
        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-01',
                        'amount' => 120.5,
                        'type' => 'despesa',
                        'description' => 'Mercado',
                        'raw' => ['line' => 1],
                    ]),
                ];
            }
        };

        $service = new ImportPreviewService([$parser]);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 9, 'source_type' => 'ofx']);

        $preview = $service->preview('ofx', '<OFX>', $profile, 'extrato.ofx');

        $this->assertSame('ofx', $preview['source_type']);
        $this->assertSame(9, $preview['conta_id']);
        $this->assertSame('extrato.ofx', $preview['filename']);
        $this->assertSame(1, $preview['total_rows']);
        $this->assertSame('Mercado', $preview['rows'][0]['description'] ?? null);
        $this->assertTrue($preview['can_confirm']);
        $this->assertSame([], $preview['errors']);
    }

    public function testPreviewIncludesImportTargetAndCardIdForCardFlow(): void
    {
        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-01',
                        'amount' => 89.9,
                        'type' => 'despesa',
                        'description' => 'Compra cartao',
                    ]),
                ];
            }
        };

        $service = new ImportPreviewService([$parser]);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 11, 'source_type' => 'ofx']);

        $preview = $service->preview('ofx', '<OFX>', $profile, 'cartao.ofx', 'cartao', 77);

        $this->assertSame('cartao', $preview['import_target']);
        $this->assertSame(77, $preview['cartao_id']);
        $this->assertTrue($preview['can_confirm']);
    }

    public function testPreviewAssignsStableRowKeyWithoutApplyingCategoriesByDefault(): void
    {
        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-01',
                        'amount' => 32.9,
                        'type' => 'despesa',
                        'description' => 'Padaria Central',
                        'memo' => 'Cafe da manha',
                    ]),
                ];
            }
        };

        $categorizationService = new class extends ImportRowCategorizationService {
            public int $calls = 0;

            public function enrichRows(array $rows, ?int $userId = null): array
            {
                $this->calls++;

                return parent::enrichRows($rows, $userId);
            }
        };

        $service = new ImportPreviewService([$parser], null, $categorizationService);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 9, 'source_type' => 'ofx']);

        $preview = $service->preview('ofx', '<OFX>', $profile, 'extrato.ofx', 'conta', null, 55);

        $this->assertNotEmpty($preview['rows'][0]['row_key'] ?? null);
        $this->assertSame(64, strlen((string) ($preview['rows'][0]['row_key'] ?? '')));
        $this->assertNull($preview['rows'][0]['categoria_id'] ?? null);
        $this->assertNull($preview['rows'][0]['categoria_sugerida_id'] ?? null);
        $this->assertSame(0, $categorizationService->calls);
    }

    public function testPreviewEnrichesContaOfxRowsWithSuggestedCategoriesWhenRequested(): void
    {
        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-01',
                        'amount' => 32.9,
                        'type' => 'despesa',
                        'description' => 'Padaria Central',
                        'memo' => 'Cafe da manha',
                    ]),
                ];
            }
        };

        $categorizationService = new class extends ImportRowCategorizationService {
            public function enrichRows(array $rows, ?int $userId = null): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-01',
                        'amount' => 32.9,
                        'type' => 'despesa',
                        'description' => 'Padaria Central',
                        'memo' => 'Cafe da manha',
                        'row_key' => 'preview-row-1',
                        'categoria_id' => 12,
                        'subcategoria_id' => 13,
                        'categoria_nome' => 'Alimentacao',
                        'subcategoria_nome' => 'Padaria',
                        'categoria_sugerida_id' => 12,
                        'subcategoria_sugerida_id' => 13,
                        'categoria_sugerida_nome' => 'Alimentacao',
                        'subcategoria_sugerida_nome' => 'Padaria',
                        'categoria_source' => 'user_rule',
                        'categoria_confidence' => 'user_rule',
                    ]),
                ];
            }
        };

        $service = new ImportPreviewService([$parser], null, $categorizationService);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 9, 'source_type' => 'ofx']);

        $preview = $service->preview('ofx', '<OFX>', $profile, 'extrato.ofx', 'conta', null, 55, true);

        $this->assertSame('preview-row-1', $preview['rows'][0]['row_key'] ?? null);
        $this->assertSame(12, $preview['rows'][0]['categoria_id'] ?? null);
        $this->assertSame(13, $preview['rows'][0]['subcategoria_id'] ?? null);
        $this->assertSame('Alimentacao', $preview['rows'][0]['categoria_nome'] ?? null);
        $this->assertSame('Padaria', $preview['rows'][0]['subcategoria_nome'] ?? null);
        $this->assertSame('user_rule', $preview['rows'][0]['categoria_source'] ?? null);
        $this->assertTrue($preview['can_confirm']);
    }

    public function testPreviewEnrichesCardOfxRowsWithSuggestedCategoriesWhenRequested(): void
    {
        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-20',
                        'amount' => 103.68,
                        'type' => 'despesa',
                        'description' => 'Openai *Chatgpt Subscr',
                    ]),
                ];
            }
        };

        $categorizationService = new class extends ImportRowCategorizationService {
            public int $calls = 0;

            public function enrichRows(array $rows, ?int $userId = null): array
            {
                $this->calls++;

                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-20',
                        'amount' => 103.68,
                        'type' => 'despesa',
                        'description' => 'Openai *Chatgpt Subscr',
                        'row_key' => 'card-preview-row-1',
                        'categoria_id' => 21,
                        'subcategoria_id' => 22,
                        'categoria_nome' => 'Assinaturas',
                        'subcategoria_nome' => 'Software',
                        'categoria_sugerida_id' => 21,
                        'subcategoria_sugerida_id' => 22,
                        'categoria_sugerida_nome' => 'Assinaturas',
                        'subcategoria_sugerida_nome' => 'Software',
                        'categoria_source' => 'rule',
                        'categoria_confidence' => 'rule',
                    ]),
                ];
            }
        };

        $service = new ImportPreviewService([$parser], null, $categorizationService);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 9, 'source_type' => 'ofx']);

        $preview = $service->preview('ofx', '<OFX>', $profile, 'fatura.ofx', 'cartao', 77, 55, true);

        $this->assertSame(1, $categorizationService->calls);
        $this->assertSame('cartao', $preview['import_target']);
        $this->assertSame('card-preview-row-1', $preview['rows'][0]['row_key'] ?? null);
        $this->assertSame(21, $preview['rows'][0]['categoria_id'] ?? null);
        $this->assertSame(22, $preview['rows'][0]['subcategoria_id'] ?? null);
        $this->assertSame('rule', $preview['rows'][0]['categoria_source'] ?? null);
        $this->assertTrue($preview['can_confirm']);
    }

    public function testPreviewBlocksCardOfxWhenAccountTargetIsSelected(): void
    {
        $service = new ImportPreviewService([new OfxImportParser()]);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 12, 'source_type' => 'ofx']);

        $preview = $service->preview('ofx', $this->sampleOfxCard(), $profile, 'fatura.ofx', 'conta');

        $this->assertSame('cartao', $preview['detected_import_target'] ?? null);
        $this->assertTrue((bool) ($preview['target_mismatch'] ?? false));
        $this->assertFalse((bool) ($preview['can_confirm'] ?? true));
        $this->assertSame(2, $preview['total_rows'] ?? null);
        $this->assertStringContainsString('cartão/fatura', (string) ($preview['errors'][0] ?? ''));
    }

    public function testPreviewBlocksBankOfxWhenCardTargetIsSelected(): void
    {
        $service = new ImportPreviewService([new OfxImportParser()]);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 13, 'source_type' => 'ofx']);

        $preview = $service->preview('ofx', $this->sampleOfx(), $profile, 'extrato.ofx', 'cartao', 77);

        $this->assertSame('conta', $preview['detected_import_target'] ?? null);
        $this->assertTrue((bool) ($preview['target_mismatch'] ?? false));
        $this->assertFalse((bool) ($preview['can_confirm'] ?? true));
        $this->assertSame(2, $preview['total_rows'] ?? null);
        $this->assertStringContainsString('conta bancária', (string) ($preview['errors'][0] ?? ''));
    }

    public function testPreviewThrowsWhenParserIsNotRegistered(): void
    {
        $service = new ImportPreviewService([]);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 7, 'source_type' => 'ofx']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Nenhum parser de importação registrado');

        $service->preview('xml', '<xml/>', $profile);
    }

    public function testExecutionServiceReturnsPreparationPayloadWithoutPersistence(): void
    {
        $previewService = new ImportPreviewService([new OfxImportParser()]);
        $executionService = new ImportExecutionService($previewService);
        $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 3, 'source_type' => 'ofx']);

        $result = $executionService->prepareExecution('ofx', '<OFX></OFX>', $profile, 'arquivo.ofx');

        $this->assertTrue($result->success);
        $this->assertSame(201, $result->httpCode);
        $this->assertSame('preview_ready', $result->data['status'] ?? null);
        $this->assertFalse($result->data['can_persist'] ?? true);
        $this->assertSame(0, $result->data['preview']['total_rows'] ?? null);
        $this->assertSame('arquivo.ofx', $result->data['preview']['filename'] ?? null);
    }

    public function testCsvParserAutomaticModeUsesHeaderAndConfiguredDelimiter(): void
    {
        $parser = new CsvImportParser();
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 41,
            'source_type' => 'csv',
            'options' => [
                'csv_mapping_mode' => 'auto',
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_start_row' => 2,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
            ],
        ]);

        $csv = implode("\n", [
            'tipo;data;descricao;valor',
            'despesa;01/03/2026;Mercado;150,25',
            'receita;05/03/2026;Salario;3200,00',
        ]);

        $rows = $parser->parse($csv, $profile);

        $this->assertCount(2, $rows);
        $this->assertSame('despesa', $rows[0]->type);
        $this->assertSame('2026-03-01', $rows[0]->date);
        $this->assertSame(150.25, $rows[0]->amount);
        $this->assertSame('receita', $rows[1]->type);
    }

    public function testCsvParserIgnoresExcelSeparatorHintLine(): void
    {
        $parser = new CsvImportParser();
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 4111,
            'source_type' => 'csv',
            'options' => [
                'csv_mapping_mode' => 'auto',
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_start_row' => 2,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
            ],
        ]);

        $csv = implode("\n", [
            'sep=;',
            'tipo;data;descricao;valor',
            'despesa;01/03/2026;Mercado;150,25',
            'receita;05/03/2026;Salario;3200,00',
        ]);

        $rows = $parser->parse($csv, $profile);

        $this->assertCount(2, $rows);
        $this->assertSame('despesa', $rows[0]->type);
        $this->assertSame('2026-03-01', $rows[0]->date);
        $this->assertSame(150.25, $rows[0]->amount);
        $this->assertSame('receita', $rows[1]->type);
    }

    public function testCsvParserAutomaticModeInfersCardTransactionsWithoutTipoColumn(): void
    {
        $parser = new CsvImportParser();
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 411,
            'source_type' => 'csv',
            'options' => [
                'import_target' => 'cartao',
                'csv_mapping_mode' => 'auto',
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_start_row' => 2,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
            ],
        ]);

        $csv = implode("\n", [
            'data;descricao;valor',
            '05/03/2026;Restaurante;220,90',
            '06/03/2026;Estorno parcial;-40,00',
        ]);

        $rows = $parser->parse($csv, $profile);

        $this->assertCount(2, $rows);
        $this->assertSame('despesa', $rows[0]->type);
        $this->assertSame(220.9, $rows[0]->amount);
        $this->assertSame('receita', $rows[1]->type);
        $this->assertSame(40.0, $rows[1]->amount);
    }

    public function testCsvParserManualModeUsesColumnMapAndStartRow(): void
    {
        $parser = new CsvImportParser();
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 42,
            'source_type' => 'csv',
            'options' => [
                'csv_mapping_mode' => 'manual',
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_start_row' => 3,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
                'csv_column_map' => [
                    'tipo' => 'A',
                    'data' => 'B',
                    'descricao' => 'C',
                    'valor' => 'D',
                    'categoria' => 'E',
                    'subcategoria' => 'F',
                    'observacao' => 'G',
                    'id_externo' => 'H',
                ],
            ],
        ]);

        $csv = implode("\n", [
            'c1;c2;c3;c4;c5;c6;c7;c8',
            'ignore;ignore;ignore;ignore;ignore;ignore;ignore;ignore',
            'despesa;01/03/2026;Padaria;12,30;Alimentacao;Lanche;Cafe da manha;CSV-1',
            'receita;02/03/2026;Reembolso;22,00;Renda;Extras;Retorno de compra;CSV-2',
        ]);

        $rows = $parser->parse($csv, $profile);

        $this->assertCount(2, $rows);
        $this->assertSame('Padaria', $rows[0]->description);
        $this->assertSame('CSV-1', $rows[0]->externalId);
        $this->assertSame('receita', $rows[1]->type);
    }

    public function testPreviewBlocksCsvManualModeWhenRequiredMappingIsMissing(): void
    {
        $service = new ImportPreviewService([new CsvImportParser()]);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 43,
            'source_type' => 'csv',
            'options' => [
                'csv_mapping_mode' => 'manual',
                'csv_start_row' => 2,
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_column_map' => [
                    'data' => 'B',
                    'descricao' => 'C',
                    'valor' => '',
                    'tipo' => '',
                ],
            ],
        ]);

        $csv = implode("\n", [
            'A;B;C;D',
            'despesa;01/03/2026;Mercado;150,00',
        ]);

        $preview = $service->preview('csv', $csv, $profile, 'manual.csv');

        $this->assertFalse((bool) ($preview['can_confirm'] ?? true));
        $this->assertSame(0, $preview['total_rows']);
        $this->assertNotEmpty($preview['errors']);
        $this->assertStringContainsString('Mapeamento CSV manual incompleto', (string) $preview['errors'][0]);
    }

    public function testPreviewBlocksCsvWhenLastLineIsIncomplete(): void
    {
        $service = new ImportPreviewService([new CsvImportParser()]);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 431,
            'source_type' => 'csv',
            'options' => [
                'csv_mapping_mode' => 'auto',
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_start_row' => 2,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
            ],
        ]);

        $csv = implode("\n", [
            'tipo;data;descricao;valor',
            'despesa;01/03/2026;Mercado;150,00',
            'despesa;;;',
        ]);

        $preview = $service->preview('csv', $csv, $profile, 'quebrado.csv');

        $this->assertFalse((bool) ($preview['can_confirm'] ?? true));
        $this->assertSame(0, $preview['total_rows']);
        $this->assertNotEmpty($preview['errors']);
        $this->assertStringContainsString('última linha do csv parece incompleta', mb_strtolower((string) $preview['errors'][0]));
    }

    public function testPreviewBlocksCsvWhenDateDoesNotMatchConfiguredFormat(): void
    {
        $service = new ImportPreviewService([new CsvImportParser()]);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => 432,
            'source_type' => 'csv',
            'options' => [
                'csv_mapping_mode' => 'auto',
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_start_row' => 2,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
            ],
        ]);

        $csv = implode("\n", [
            'tipo;data;descricao;valor',
            'despesa;2026/03/01;Mercado;150,00',
        ]);

        $preview = $service->preview('csv', $csv, $profile, 'data-invalida.csv');

        $this->assertFalse((bool) ($preview['can_confirm'] ?? true));
        $this->assertSame(0, $preview['total_rows']);
        $this->assertNotEmpty($preview['errors']);
        $this->assertStringContainsString('data inválida', mb_strtolower((string) $preview['errors'][0]));
        $this->assertStringContainsString('d/m/Y', (string) $preview['errors'][0]);
    }

    public function testPreviewBlocksWhenRowsExceedConfiguredLimit(): void
    {
        $previousLimit = $_ENV['IMPORTACOES_MAX_ROWS'] ?? null;
        $_ENV['IMPORTACOES_MAX_ROWS'] = '1';

        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-01',
                        'amount' => 10,
                        'type' => 'despesa',
                        'description' => 'Linha 1',
                    ]),
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-02',
                        'amount' => 20,
                        'type' => 'despesa',
                        'description' => 'Linha 2',
                    ]),
                ];
            }
        };

        try {
            $service = new ImportPreviewService([$parser]);
            $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 99, 'source_type' => 'ofx']);

            $preview = $service->preview('ofx', '<OFX>', $profile, 'lote.ofx');
        } finally {
            if ($previousLimit === null) {
                unset($_ENV['IMPORTACOES_MAX_ROWS']);
            } else {
                $_ENV['IMPORTACOES_MAX_ROWS'] = (string) $previousLimit;
            }
        }

        $this->assertFalse((bool) ($preview['can_confirm'] ?? true));
        $this->assertSame(0, $preview['total_rows']);
        $this->assertStringContainsString('limite de 1 linhas/transações', (string) ($preview['errors'][0] ?? ''));
    }

    public function testOfxParserRejectsWhenTransactionsExceedConfiguredLimit(): void
    {
        $previousLimit = $_ENV['IMPORTACOES_MAX_ROWS'] ?? null;
        $_ENV['IMPORTACOES_MAX_ROWS'] = '1';

        try {
            $parser = new OfxImportParser();
            $profile = ImportProfileConfigDTO::fromArray(['conta_id' => 50, 'source_type' => 'ofx']);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Arquivo excede o limite de 1 linhas/transações por importação.');

            $parser->parse(
                '<OFX><STMTTRN><TRNAMT>-10</TRNAMT><DTPOSTED>20260401</DTPOSTED><NAME>A</NAME></STMTTRN><STMTTRN><TRNAMT>-20</TRNAMT><DTPOSTED>20260402</DTPOSTED><NAME>B</NAME></STMTTRN></OFX>',
                $profile
            );
        } finally {
            if ($previousLimit === null) {
                unset($_ENV['IMPORTACOES_MAX_ROWS']);
            } else {
                $_ENV['IMPORTACOES_MAX_ROWS'] = (string) $previousLimit;
            }
        }
    }

    public function testCsvParserRejectsWhenRowsExceedConfiguredLimit(): void
    {
        $previousLimit = $_ENV['IMPORTACOES_MAX_ROWS'] ?? null;
        $_ENV['IMPORTACOES_MAX_ROWS'] = '2';

        try {
            $parser = new CsvImportParser();
            $profile = ImportProfileConfigDTO::fromArray([
                'conta_id' => 51,
                'source_type' => 'csv',
                'options' => [
                    'csv_mapping_mode' => 'auto',
                    'csv_delimiter' => ';',
                    'csv_has_header' => true,
                    'csv_start_row' => 2,
                    'csv_date_format' => 'd/m/Y',
                    'csv_decimal_separator' => ',',
                ],
            ]);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Arquivo excede o limite de 2 linhas/transações por importação.');

            $parser->parse(implode("\n", [
                'tipo;data;descricao;valor',
                'despesa;01/03/2026;Mercado;150,25',
                'receita;05/03/2026;Salario;3200,00',
                'despesa;08/03/2026;Padaria;18,90',
            ]), $profile);
        } finally {
            if ($previousLimit === null) {
                unset($_ENV['IMPORTACOES_MAX_ROWS']);
            } else {
                $_ENV['IMPORTACOES_MAX_ROWS'] = (string) $previousLimit;
            }
        }
    }

    public function testExecutionServiceConfirmPersistsLancamentosAndPreventsDuplicates(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $contents = $this->sampleOfx();
        $service = new ImportExecutionService(new ImportPreviewService([new OfxImportParser()]));

        $first = $service->confirmExecution($userId, 'ofx', $contents, $profile, 'primeiro.ofx');

        $this->assertTrue($first->success);
        $this->assertSame(2, $first->data['summary']['total_rows'] ?? null);
        $this->assertSame(2, $first->data['summary']['imported_rows'] ?? null);
        $this->assertSame(0, $first->data['summary']['duplicate_rows'] ?? null);
        $this->assertSame(0, $first->data['summary']['error_rows'] ?? null);
        $this->assertSame(2, DB::table('lancamentos')->where('user_id', $userId)->count());

        $second = $service->confirmExecution($userId, 'ofx', $contents, $profile, 'segundo.ofx');

        $this->assertTrue($second->success);
        $this->assertSame('processed_duplicates_only', $second->data['status'] ?? null);
        $this->assertSame(0, $second->data['summary']['imported_rows'] ?? null);
        $this->assertSame(2, $second->data['summary']['duplicate_rows'] ?? null);
        $this->assertSame(2, DB::table('lancamentos')->where('user_id', $userId)->count());
    }

    public function testExecutionServiceConfirmAppliesOverridesPersistsCategoryMetadataAndLearns(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $categoriaSugeridaId = $this->createCategoria($userId, 'Alimentacao');
        $subcategoriaSugeridaId = $this->createSubcategoria($userId, $categoriaSugeridaId, 'Padaria');
        $categoriaManualId = $this->createCategoria($userId, 'Moradia');
        $subcategoriaManualId = $this->createSubcategoria($userId, $categoriaManualId, 'Energia');

        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-03',
                        'amount' => 89.5,
                        'type' => 'despesa',
                        'description' => 'Padaria do Bairro',
                        'memo' => 'Compra teste',
                        'external_id' => 'OFX-CAT-1',
                        'raw' => ['fitid' => 'OFX-CAT-1'],
                    ]),
                ];
            }
        };

        $previewService = new ImportPreviewService([$parser]);
        $service = new ImportExecutionService($previewService);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $preview = $previewService->preview('ofx', '<OFX></OFX>', $profile, 'categorizado.ofx', 'conta', null, $userId);
        $rowKey = (string) ($preview['rows'][0]['row_key'] ?? '');

        $result = $service->confirmExecution(
            $userId,
            'ofx',
            '<OFX></OFX>',
            $profile,
            'categorizado.ofx',
            'conta',
            null,
            [
                $rowKey => [
                    'categoria_id' => $categoriaManualId,
                    'subcategoria_id' => $subcategoriaManualId,
                    'categoria_sugerida_id' => $categoriaSugeridaId,
                    'subcategoria_sugerida_id' => $subcategoriaSugeridaId,
                    'categoria_source' => 'rule',
                    'categoria_confidence' => 'rule',
                    'user_edited' => true,
                ],
            ]
        );

        $this->assertTrue($result->success);

        $lancamento = DB::table('lancamentos')->where('user_id', $userId)->first();
        $this->assertNotNull($lancamento);
        $this->assertSame($categoriaManualId, (int) ($lancamento->categoria_id ?? 0));
        $this->assertSame($subcategoriaManualId, (int) ($lancamento->subcategoria_id ?? 0));

        $item = DB::table('importacao_itens')->where('user_id', $userId)->first();
        $this->assertNotNull($item);

        $raw = json_decode((string) ($item->raw_json ?? ''), true);
        $this->assertIsArray($raw);
        $this->assertSame('conta', $raw['import_target'] ?? null);
        $this->assertSame($rowKey, $raw['row_key'] ?? null);
        $this->assertSame($categoriaManualId, (int) ($raw['categoria_id'] ?? 0));
        $this->assertSame($subcategoriaManualId, (int) ($raw['subcategoria_id'] ?? 0));
        $this->assertTrue((bool) ($raw['categoria_editada'] ?? false));
        $this->assertSame('correction', $raw['categoria_learning_source'] ?? null);

        $rule = DB::table('user_category_rules')->where('user_id', $userId)->first();
        $this->assertNotNull($rule);
        $this->assertStringContainsString('padaria', (string) ($rule->pattern ?? ''));
        $this->assertStringContainsString('padaria', (string) ($rule->normalized_pattern ?? ''));
        $this->assertSame($categoriaManualId, (int) ($rule->categoria_id ?? 0));
        $this->assertSame($subcategoriaManualId, (int) ($rule->subcategoria_id ?? 0));
        $this->assertSame('correction', $rule->source ?? null);
        $this->assertSame(1, (int) ($rule->usage_count ?? 0));
    }

    public function testExecutionServiceConfirmPreservesSuggestedMetadataFromOverridesWithoutLearning(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $categoriaId = $this->createCategoria($userId, 'Alimentacao');
        $subcategoriaId = $this->createSubcategoria($userId, $categoriaId, 'Padaria');

        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-03',
                        'amount' => 89.5,
                        'type' => 'despesa',
                        'description' => 'Padaria do Bairro',
                        'memo' => 'Compra teste',
                        'external_id' => 'OFX-CAT-2',
                        'raw' => ['fitid' => 'OFX-CAT-2'],
                    ]),
                ];
            }
        };

        $previewService = new ImportPreviewService([$parser]);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $preview = $previewService->preview('ofx', '<OFX></OFX>', $profile, 'sem-auto.ofx', 'conta', null, $userId);
        $rowKey = (string) ($preview['rows'][0]['row_key'] ?? '');

        $service = new ImportExecutionService($previewService);
        $result = $service->confirmExecution(
            $userId,
            'ofx',
            '<OFX></OFX>',
            $profile,
            'sem-auto.ofx',
            'conta',
            null,
            [
                $rowKey => [
                    'categoria_id' => $categoriaId,
                    'subcategoria_id' => $subcategoriaId,
                    'categoria_sugerida_id' => $categoriaId,
                    'subcategoria_sugerida_id' => $subcategoriaId,
                    'categoria_source' => 'rule',
                    'categoria_confidence' => 'rule',
                    'user_edited' => false,
                ],
            ]
        );

        $this->assertTrue($result->success);

        $item = DB::table('importacao_itens')->where('user_id', $userId)->first();
        $this->assertNotNull($item);

        $raw = json_decode((string) ($item->raw_json ?? ''), true);
        $this->assertIsArray($raw);
        $this->assertSame($categoriaId, (int) ($raw['categoria_id'] ?? 0));
        $this->assertSame($subcategoriaId, (int) ($raw['subcategoria_id'] ?? 0));
        $this->assertSame($categoriaId, (int) ($raw['categoria_sugerida_id'] ?? 0));
        $this->assertSame($subcategoriaId, (int) ($raw['subcategoria_sugerida_id'] ?? 0));
        $this->assertSame('rule', $raw['categoria_source'] ?? null);
        $this->assertFalse((bool) ($raw['categoria_editada'] ?? true));
        $this->assertNull($raw['categoria_learning_source'] ?? null);
        $this->assertSame(0, DB::table('user_category_rules')->where('user_id', $userId)->count());
    }

    public function testExecutionServiceConfirmPersistsCardInvoiceItemsForCardTarget(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $service = new ImportExecutionService(new ImportPreviewService([new OfxImportParser()]));
        $result = $service->confirmExecution(
            $userId,
            'ofx',
            $this->sampleOfxCard(),
            $profile,
            'fatura-cartao.ofx',
            'cartao',
            $cartaoId
        );

        $this->assertTrue($result->success);
        $this->assertSame('cartao', $result->data['batch']['import_target'] ?? null);
        $this->assertSame($cartaoId, $result->data['batch']['cartao_id'] ?? null);
        $this->assertSame(2, $result->data['summary']['imported_rows'] ?? null);
        $this->assertSame(2, DB::table('faturas_cartao_itens')->where('user_id', $userId)->count());
        $this->assertSame(0, DB::table('lancamentos')->where('user_id', $userId)->count());
        $this->assertSame(1, DB::table('faturas_cartao_itens')->where('user_id', $userId)->where('tipo', 'despesa')->count());
        $this->assertSame(1, DB::table('faturas_cartao_itens')->where('user_id', $userId)->where('tipo', 'estorno')->count());
    }

    public function testExecutionServiceConfirmPersistsCardInvoiceItemsForCardCsvWithoutTipo(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId);
        $categoriaId = $this->createCategoria($userId, 'Alimentacao');
        $subcategoriaId = $this->createSubcategoria($userId, $categoriaId, 'Restaurante');
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'csv',
            'options' => [
                'csv_mapping_mode' => 'auto',
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_start_row' => 2,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
            ],
        ]);

        $csv = implode("\n", [
            'data;descricao;valor;categoria;subcategoria',
            '05/03/2026;Restaurante;220,90;Alimentacao;Restaurante',
            '06/03/2026;Estorno parcial;-40,00;Alimentacao;Restaurante',
        ]);

        $service = new ImportExecutionService(new ImportPreviewService([new CsvImportParser()]));
        $result = $service->confirmExecution(
            $userId,
            'csv',
            $csv,
            $profile,
            'fatura-cartao.csv',
            'cartao',
            $cartaoId
        );

        $this->assertTrue($result->success);
        $this->assertSame('cartao', $result->data['batch']['import_target'] ?? null);
        $this->assertSame($cartaoId, $result->data['batch']['cartao_id'] ?? null);
        $this->assertSame(2, $result->data['summary']['imported_rows'] ?? null);
        $this->assertSame(2, DB::table('faturas_cartao_itens')->where('user_id', $userId)->count());
        $this->assertSame(0, DB::table('lancamentos')->where('user_id', $userId)->count());
        $this->assertSame(1, DB::table('faturas_cartao_itens')->where('user_id', $userId)->where('tipo', 'despesa')->count());
        $this->assertSame(1, DB::table('faturas_cartao_itens')->where('user_id', $userId)->where('tipo', 'estorno')->count());
        $itemDespesa = DB::table('faturas_cartao_itens')->where('user_id', $userId)->where('tipo', 'despesa')->first();
        $this->assertSame($categoriaId, (int) ($itemDespesa->categoria_id ?? 0));
        $this->assertSame($subcategoriaId, (int) ($itemDespesa->subcategoria_id ?? 0));
        $itemEstorno = DB::table('faturas_cartao_itens')->where('user_id', $userId)->where('tipo', 'estorno')->first();
        $this->assertSame($categoriaId, (int) ($itemEstorno->categoria_id ?? 0));
        $this->assertSame($subcategoriaId, (int) ($itemEstorno->subcategoria_id ?? 0));
    }

    public function testExecutionServiceConfirmAppliesCategoryOverridesToCardInvoiceItems(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId);
        $categoriaId = $this->createCategoria($userId, 'Transporte');
        $subcategoriaId = $this->createSubcategoria($userId, $categoriaId, 'Aplicativo');

        $parser = new class implements ImportParserInterface {
            public function supports(string $sourceType): bool
            {
                return $sourceType === 'ofx';
            }

            public function parse(string $contents, ImportProfileConfigDTO $profile): array
            {
                return [
                    NormalizedImportRowDTO::fromArray([
                        'date' => '2026-04-03',
                        'amount' => 23.5,
                        'type' => 'despesa',
                        'description' => 'Uber Trip',
                        'memo' => 'Compra cartao',
                        'external_id' => 'CARD-CAT-1',
                        'raw' => ['fitid' => 'CARD-CAT-1'],
                    ]),
                ];
            }
        };

        $previewService = new ImportPreviewService([$parser]);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $preview = $previewService->preview('ofx', '<OFX></OFX>', $profile, 'fatura-card.ofx', 'cartao', $cartaoId, $userId);
        $rowKey = (string) ($preview['rows'][0]['row_key'] ?? '');

        $service = new ImportExecutionService($previewService);
        $result = $service->confirmExecution(
            $userId,
            'ofx',
            '<OFX></OFX>',
            $profile,
            'fatura-card.ofx',
            'cartao',
            $cartaoId,
            [
                $rowKey => [
                    'categoria_id' => $categoriaId,
                    'subcategoria_id' => $subcategoriaId,
                    'user_edited' => true,
                ],
            ]
        );

        $this->assertTrue($result->success);

        $item = DB::table('faturas_cartao_itens')->where('user_id', $userId)->first();
        $this->assertNotNull($item);
        $this->assertSame($categoriaId, (int) ($item->categoria_id ?? 0));
        $this->assertSame($subcategoriaId, (int) ($item->subcategoria_id ?? 0));

        $raw = json_decode((string) DB::table('importacao_itens')->where('user_id', $userId)->value('raw_json'), true);
        $this->assertIsArray($raw);
        $this->assertSame('cartao', $raw['import_target'] ?? null);
        $this->assertSame($categoriaId, (int) ($raw['categoria_id'] ?? 0));
        $this->assertSame($subcategoriaId, (int) ($raw['subcategoria_id'] ?? 0));
    }

    public function testExecutionServiceBlocksCardOfxWhenTargetIsConta(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $service = new ImportExecutionService(new ImportPreviewService([new OfxImportParser()]));
        $result = $service->confirmExecution(
            $userId,
            'ofx',
            $this->sampleOfxCard(),
            $profile,
            'fatura-enviada-como-conta.ofx',
            'conta'
        );

        $this->assertFalse($result->success);
        $this->assertSame(422, $result->httpCode);
        $this->assertStringContainsString('cartão/fatura', $result->message);
        $this->assertSame(0, DB::table('lancamentos')->where('user_id', $userId)->count());
        $this->assertSame(0, DB::table('faturas_cartao_itens')->where('user_id', $userId)->count());
    }

    public function testExecutionServiceAvoidsDuplicateCardRowsUsingExternalId(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $service = new ImportExecutionService(new ImportPreviewService([new OfxImportParser()]));
        $contents = $this->sampleOfxCard();

        $first = $service->confirmExecution($userId, 'ofx', $contents, $profile, 'fatura-1.ofx', 'cartao', $cartaoId);
        $second = $service->confirmExecution($userId, 'ofx', $contents, $profile, 'fatura-2.ofx', 'cartao', $cartaoId);

        $this->assertTrue($first->success);
        $this->assertTrue($second->success);
        $this->assertSame('processed_duplicates_only', $second->data['status'] ?? null);
        $this->assertSame(0, $second->data['summary']['imported_rows'] ?? null);
        $this->assertSame(2, $second->data['summary']['duplicate_rows'] ?? null);
        $this->assertSame(2, DB::table('faturas_cartao_itens')->where('user_id', $userId)->count());
    }

    public function testExecutionServiceReimportsCardRowsWhenImportedHistoryHasNoInvoiceItems(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId);
        $profile = ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => 'ofx',
        ]);

        $service = new ImportExecutionService(new ImportPreviewService([new OfxImportParser()]));
        $contents = $this->sampleOfxCard();

        $first = $service->confirmExecution($userId, 'ofx', $contents, $profile, 'fatura-1.ofx', 'cartao', $cartaoId);
        DB::table('faturas_cartao_itens')->where('user_id', $userId)->delete();
        $second = $service->confirmExecution($userId, 'ofx', $contents, $profile, 'fatura-2.ofx', 'cartao', $cartaoId);

        $this->assertTrue($first->success);
        $this->assertTrue($second->success);
        $this->assertSame(2, $second->data['summary']['imported_rows'] ?? null);
        $this->assertSame(0, $second->data['summary']['duplicate_rows'] ?? null);
        $this->assertSame(2, DB::table('faturas_cartao_itens')->where('user_id', $userId)->count());
    }

    public function testProfileConfigServicePersistsAndLoadsByConta(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $service = new ImportProfileConfigService();

        $saved = $service->saveForUserAndConta($userId, $contaId, [
            'source_type' => 'csv',
            'label' => 'Perfil Teste',
            'agencia' => '1234',
            'numero_conta' => '98765-0',
            'options' => [
                'csv_mapping_mode' => 'manual',
                'csv_start_row' => 3,
                'csv_delimiter' => ';',
                'csv_has_header' => true,
                'csv_date_format' => 'd/m/Y',
                'csv_decimal_separator' => ',',
                'csv_column_map' => [
                    'tipo' => 'A',
                    'data' => 'B',
                    'descricao' => 'C',
                    'valor' => 'D',
                    'categoria' => 'E',
                    'subcategoria' => 'F',
                    'observacao' => 'G',
                    'id_externo' => 'H',
                ],
            ],
        ]);

        $loaded = $service->getForUserAndConta($userId, $contaId);

        $this->assertSame('csv', $saved->sourceType);
        $this->assertSame('Perfil Teste', $saved->label);
        $this->assertSame('1234', $saved->agencia);
        $this->assertSame('98765-0', $saved->numeroConta);
        $this->assertSame('manual', $loaded->options['csv_mapping_mode'] ?? null);
        $this->assertSame(3, $loaded->options['csv_start_row'] ?? null);
        $this->assertSame(';', $loaded->options['csv_delimiter'] ?? null);
        $this->assertTrue((bool) ($loaded->options['csv_has_header'] ?? false));
        $columnMap = is_array($loaded->options['csv_column_map'] ?? null) ? $loaded->options['csv_column_map'] : [];
        $this->assertSame('A', $columnMap['tipo'] ?? null);
        $this->assertSame('D', $columnMap['valor'] ?? null);
    }

    public function testHistoryServiceListsRealBatchesWithFilters(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);

        DB::table('importacao_lotes')->insert([
            [
                'user_id' => $userId,
                'conta_id' => $contaId,
                'source_type' => 'ofx',
                'filename' => 'a.ofx',
                'status' => 'processed',
                'total_rows' => 5,
                'imported_rows' => 5,
                'duplicate_rows' => 0,
                'error_rows' => 0,
                'meta_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            ],
            [
                'user_id' => $userId,
                'conta_id' => $contaId,
                'source_type' => 'ofx',
                'filename' => 'b.ofx',
                'status' => 'processed_with_duplicates',
                'total_rows' => 5,
                'imported_rows' => 3,
                'duplicate_rows' => 2,
                'error_rows' => 0,
                'meta_json' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 minutes')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 minutes')),
            ],
            [
                'user_id' => $userId,
                'conta_id' => $contaId,
                'source_type' => 'ofx',
                'filename' => 'card.ofx',
                'status' => 'processed',
                'total_rows' => 2,
                'imported_rows' => 2,
                'duplicate_rows' => 0,
                'error_rows' => 0,
                'meta_json' => json_encode([
                    'import_target' => 'cartao',
                    'cartao_id' => 99,
                    'cartao_nome' => 'Nubank',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        $service = new ImportHistoryService();
        $all = $service->listForUser($userId, [], 20);
        $filtered = $service->listForUser($userId, ['status' => 'processed_with_duplicates'], 20);
        $cardOnly = $service->listForUser($userId, ['import_target' => 'cartao'], 20);

        $this->assertCount(3, $all);
        $this->assertCount(1, $filtered);
        $this->assertSame('processed_with_duplicates', $filtered[0]['status'] ?? null);
        $this->assertSame('b.ofx', $filtered[0]['filename'] ?? null);
        $this->assertCount(1, $cardOnly);
        $this->assertSame('cartao', $cardOnly[0]['import_target'] ?? null);
        $this->assertSame('Nubank', $cardOnly[0]['cartao_nome'] ?? null);
    }

    public function testStubParsersSupportExpectedSourceTypes(): void
    {
        $this->assertTrue((new OfxImportParser())->supports('ofx'));
        $this->assertFalse((new OfxImportParser())->supports('csv'));

        $this->assertTrue((new CsvImportParser())->supports('csv'));
        $this->assertFalse((new CsvImportParser())->supports('ofx'));
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for importacão tests');
        }
    }

    private function createUser(): int
    {
        $email = 'importacao-test-' . bin2hex(random_bytes(5)) . '@example.com';
        $userId = (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Usuário Importação',
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cleanupUserIds[] = $userId;

        return $userId;
    }

    private function createConta(int $userId): int
    {
        return (int) DB::table('contas')->insertGetId([
            'user_id' => $userId,
            'nome' => 'Conta Importacao',
            'instituicao' => 'Banco Teste',
            'tipo_conta' => 'corrente',
            'saldo_inicial' => 0,
            'ativo' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createCartao(int $userId, int $contaId): int
    {
        return (int) DB::table('cartoes_credito')->insertGetId([
            'user_id' => $userId,
            'conta_id' => $contaId,
            'nome_cartao' => 'Cartão Teste',
            'bandeira' => 'visa',
            'ultimos_digitos' => '1234',
            'limite_total' => 5000,
            'limite_disponivel' => 5000,
            'dia_vencimento' => 10,
            'dia_fechamento' => 1,
            'cor_cartao' => '#1d4ed8',
            'ativo' => 1,
            'arquivado' => 0,
            'lembrar_fatura_antes_segundos' => null,
            'fatura_canal_email' => 0,
            'fatura_canal_inapp' => 1,
            'fatura_notificado_mes' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createCategoria(int $userId, string $nome, string $tipo = 'despesa'): int
    {
        return (int) DB::table('categorias')->insertGetId([
            'user_id' => $userId,
            'parent_id' => null,
            'nome' => $nome,
            'icone' => null,
            'tipo' => $tipo,
            'is_seeded' => 0,
            'ordem' => 0,
        ]);
    }

    private function createSubcategoria(int $userId, int $categoriaId, string $nome): int
    {
        return (int) DB::table('categorias')->insertGetId([
            'user_id' => $userId,
            'parent_id' => $categoriaId,
            'nome' => $nome,
            'icone' => null,
            'tipo' => 'despesa',
            'is_seeded' => 0,
            'ordem' => 0,
        ]);
    }

    private function sampleOfx(): string
    {
        return <<<OFX
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
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT
            <DTPOSTED>20260301000000[-3:BRT]
            <TRNAMT>-120.50
            <FITID>OFX-1
            <NAME>Mercado
            <MEMO>Compra do mes
          </STMTTRN>
          <STMTTRN>
            <TRNTYPE>CREDIT
            <DTPOSTED>20260302000000[-3:BRT]
            <TRNAMT>300.00
            <FITID>OFX-2
            <NAME>Salario
            <MEMO>Pagamento
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
OFX;
    }

    private function sampleOfxCard(): string
    {
        return <<<OFX
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
