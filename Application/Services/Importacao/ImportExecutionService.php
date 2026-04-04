<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\ServiceResultDTO;
use Application\Enums\LogCategory;
use Application\Models\CartaoCredito;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\ImportacaoItem;
use Application\Models\ImportacaoLote;
use Application\Services\Cartao\CartaoBillingDateService;
use Application\Services\Cartao\CartaoFaturaSupportService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Lancamento\LancamentoCreationService;
use Illuminate\Database\Capsule\Manager as DB;

class ImportExecutionService
{
    public function __construct(
        private readonly ImportPreviewService $previewService = new ImportPreviewService(),
        private readonly LancamentoCreationService $lancamentoCreationService = new LancamentoCreationService(),
        private readonly CartaoBillingDateService $billingDateService = new CartaoBillingDateService(),
        private readonly CartaoFaturaSupportService $faturaSupportService = new CartaoFaturaSupportService(),
    ) {}

    public function prepareExecution(
        string $sourceType,
        string $contents,
        ImportProfileConfigDTO $profile,
        string $filename = '',
        string $importTarget = 'conta',
        ?int $cartaoId = null
    ): ServiceResultDTO {
        $importTarget = $this->normalizeImportTarget($importTarget);
        $preview = $this->previewService->preview($sourceType, $contents, $profile, $filename, $importTarget, $cartaoId);

        return ServiceResultDTO::ok('Preview de importação preparado.', [
            'status' => 'preview_ready',
            'can_persist' => false,
            'next_step' => 'Confirme a importação para persistir os dados.',
            'preview' => $preview,
        ]);
    }

    public function confirmExecution(
        int $userId,
        string $sourceType,
        string $contents,
        ImportProfileConfigDTO $profile,
        string $filename = '',
        string $importTarget = 'conta',
        ?int $cartaoId = null
    ): ServiceResultDTO {
        $importTarget = $this->normalizeImportTarget($importTarget);
        $preview = $this->previewService->preview($sourceType, $contents, $profile, $filename, $importTarget, $cartaoId);
        $canConfirm = (bool) ($preview['can_confirm'] ?? false);
        $rows = is_array($preview['rows'] ?? null) ? $preview['rows'] : [];
        $previewErrors = is_array($preview['errors'] ?? null) ? $preview['errors'] : [];

        if (!$canConfirm && $previewErrors !== []) {
            $message = trim((string) ($previewErrors[0] ?? ''));

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Preview bloqueado. Ajuste o arquivo ou a configuração antes de confirmar.',
                422
            );
        }

        if ($rows === []) {
            return ServiceResultDTO::fail('Nenhuma transação válida encontrada para confirmar.', 422);
        }

        if (!$canConfirm) {
            return ServiceResultDTO::fail('Preview bloqueado. Ajuste o arquivo ou a configuração antes de confirmar.', 422);
        }

        if ($importTarget === 'cartao') {
            if (($cartaoId ?? 0) <= 0) {
                return ServiceResultDTO::fail('Cartão obrigatório para confirmar importação de fatura.', 422);
            }

            return $this->confirmCartaoExecution(
                $userId,
                $sourceType,
                $contents,
                $profile,
                $preview,
                $rows,
                (int) $cartaoId
            );
        }

        if ($profile->contaId <= 0) {
            return ServiceResultDTO::fail('Conta obrigatória para confirmar importação.', 422);
        }

        return $this->confirmContaExecution($userId, $sourceType, $contents, $profile, $preview, $rows);
    }

    /**
     * @param array<string, mixed> $preview
     * @param array<int, array<string, mixed>> $rows
     */
    private function confirmContaExecution(
        int $userId,
        string $sourceType,
        string $contents,
        ImportProfileConfigDTO $profile,
        array $preview,
        array $rows
    ): ServiceResultDTO {
        $contaId = $profile->contaId;
        $lote = $this->createBatch(
            $userId,
            $contaId,
            (string) ($preview['source_type'] ?? strtolower($sourceType)),
            (string) ($preview['filename'] ?? ''),
            $contents,
            is_array($preview['warnings'] ?? null) ? $preview['warnings'] : [],
            'conta',
            null,
            null,
            count($rows)
        );

        $imported = 0;
        $duplicated = 0;
        $errors = 0;
        $errorMessages = [];

        foreach ($rows as $index => $row) {
            try {
                $normalized = $this->normalizeRow($row);
            } catch (\InvalidArgumentException $e) {
                $errors++;
                $errorMessages[] = $e->getMessage();
                continue;
            }

            $rowHash = $this->generateRowHash($userId, $contaId, $normalized, 'conta', null);
            if ($this->isDuplicate($userId, $contaId, $rowHash)) {
                $duplicated++;
                $duplicateHash = $this->decorateItemRowHash($rowHash, 'duplicate', (int) $lote->id, (int) $index);
                $this->persistItem(
                    (int) $lote->id,
                    $userId,
                    $contaId,
                    $normalized,
                    $duplicateHash,
                    'duplicate',
                    null,
                    'Linha ignorada por duplicidade.',
                    ['import_target' => 'conta']
                );
                continue;
            }

            $result = $this->lancamentoCreationService->createFromPayload($userId, [
                'tipo' => $normalized['type'],
                'data' => $normalized['date'],
                'valor' => $normalized['amount'],
                'descricao' => $normalized['description'],
                'observacao' => $this->buildObservacao($normalized['memo'] ?? null, $preview['source_type'] ?? $sourceType),
                'conta_id' => $contaId,
                'pago' => true,
            ]);

            if ($result->success) {
                $imported++;
                $lancamentoId = $this->extractLancamentoId($result->data);
                $this->persistItem(
                    (int) $lote->id,
                    $userId,
                    $contaId,
                    $normalized,
                    $rowHash,
                    'imported',
                    $lancamentoId,
                    null,
                    ['import_target' => 'conta']
                );
                continue;
            }

            $errors++;
            $errorMessage = $result->message !== '' ? $result->message : 'Falha ao criar lançamento.';
            $errorMessages[] = $errorMessage;
            $errorHash = $this->decorateItemRowHash($rowHash, 'error', (int) $lote->id, (int) $index);
            $this->persistItem(
                (int) $lote->id,
                $userId,
                $contaId,
                $normalized,
                $errorHash,
                'error',
                null,
                $errorMessage,
                ['import_target' => 'conta']
            );
        }

        $status = $this->finalizeBatch($lote, $imported, $duplicated, $errors, $errorMessages);

        return ServiceResultDTO::ok('Importação confirmada com sucesso.', [
            'status' => $status,
            'batch' => $this->formatBatch($lote),
            'summary' => [
                'total_rows' => count($rows),
                'imported_rows' => $imported,
                'duplicate_rows' => $duplicated,
                'error_rows' => $errors,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $preview
     * @param array<int, array<string, mixed>> $rows
     */
    private function confirmCartaoExecution(
        int $userId,
        string $sourceType,
        string $contents,
        ImportProfileConfigDTO $profile,
        array $preview,
        array $rows,
        int $cartaoId
    ): ServiceResultDTO {
        $cartao = CartaoCredito::query()
            ->where('id', $cartaoId)
            ->where('user_id', $userId)
            ->first();

        if (!$cartao) {
            return ServiceResultDTO::fail('Cartão inválido para o usuário autenticado.', 403);
        }

        if ((bool) ($cartao->arquivado ?? false)) {
            return ServiceResultDTO::fail('Cartão arquivado não pode receber importação.', 422);
        }

        $contaId = (int) ($cartao->conta_id ?? 0);
        if ($contaId <= 0) {
            return ServiceResultDTO::fail(
                'Vincule uma conta ao cartão antes de importar OFX de fatura.',
                422
            );
        }

        $lote = $this->createBatch(
            $userId,
            $contaId,
            (string) ($preview['source_type'] ?? strtolower($sourceType)),
            (string) ($preview['filename'] ?? ''),
            $contents,
            is_array($preview['warnings'] ?? null) ? $preview['warnings'] : [],
            'cartao',
            (int) $cartao->id,
            (string) ($cartao->nome_cartao ?? ''),
            count($rows)
        );

        $imported = 0;
        $duplicated = 0;
        $errors = 0;
        $errorMessages = [];
        $faturasAfetadas = [];

        foreach ($rows as $index => $row) {
            try {
                $normalized = $this->normalizeRow($row);
            } catch (\InvalidArgumentException $e) {
                $errors++;
                $errorMessages[] = $e->getMessage();
                continue;
            }

            $rowHash = $this->generateRowHash($userId, $contaId, $normalized, 'cartao', (int) $cartao->id);
            if ($this->isDuplicate($userId, $contaId, $rowHash)) {
                $duplicated++;
                $duplicateHash = $this->decorateItemRowHash($rowHash, 'duplicate', (int) $lote->id, (int) $index);
                $this->persistItem(
                    (int) $lote->id,
                    $userId,
                    $contaId,
                    $normalized,
                    $duplicateHash,
                    'duplicate',
                    null,
                    'Linha ignorada por duplicidade.',
                    [
                        'import_target' => 'cartao',
                        'cartao_id' => (int) $cartao->id,
                        'cartao_nome' => (string) ($cartao->nome_cartao ?? ''),
                    ]
                );
                continue;
            }

            $persisted = $this->persistCardInvoiceItem($userId, $cartao, $normalized);
            if (($persisted['success'] ?? false) === true) {
                $imported++;

                $faturaId = is_numeric($persisted['fatura_id'] ?? null) ? (int) $persisted['fatura_id'] : null;
                if ($faturaId !== null) {
                    $faturasAfetadas[$faturaId] = true;
                }

                $this->persistItem(
                    (int) $lote->id,
                    $userId,
                    $contaId,
                    $normalized,
                    $rowHash,
                    'imported',
                    null,
                    null,
                    [
                        'import_target' => 'cartao',
                        'cartao_id' => (int) $cartao->id,
                        'cartao_nome' => (string) ($cartao->nome_cartao ?? ''),
                        'fatura_id' => $faturaId,
                        'fatura_item_id' => is_numeric($persisted['fatura_item_id'] ?? null)
                            ? (int) $persisted['fatura_item_id']
                            : null,
                    ]
                );
                continue;
            }

            $errors++;
            $errorMessage = trim((string) ($persisted['message'] ?? 'Falha ao persistir item de fatura.'));
            $errorMessages[] = $errorMessage;
            $errorHash = $this->decorateItemRowHash($rowHash, 'error', (int) $lote->id, (int) $index);
            $this->persistItem(
                (int) $lote->id,
                $userId,
                $contaId,
                $normalized,
                $errorHash,
                'error',
                null,
                $errorMessage,
                [
                    'import_target' => 'cartao',
                    'cartao_id' => (int) $cartao->id,
                    'cartao_nome' => (string) ($cartao->nome_cartao ?? ''),
                ]
            );
        }

        if ($faturasAfetadas !== []) {
            $faturas = Fatura::query()->whereIn('id', array_keys($faturasAfetadas))->get();
            foreach ($faturas as $fatura) {
                $fatura->atualizarStatus();
            }
        }

        $cartao->atualizarLimiteDisponivel();
        $status = $this->finalizeBatch($lote, $imported, $duplicated, $errors, $errorMessages);

        return ServiceResultDTO::ok('Importação de cartão/fatura confirmada com sucesso.', [
            'status' => $status,
            'batch' => $this->formatBatch($lote),
            'summary' => [
                'total_rows' => count($rows),
                'imported_rows' => $imported,
                'duplicate_rows' => $duplicated,
                'error_rows' => $errors,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{success:bool,fatura_id?:int,fatura_item_id?:int,message?:string}
     */
    private function persistCardInvoiceItem(int $userId, CartaoCredito $cartao, array $row): array
    {
        try {
            return DB::transaction(function () use ($userId, $cartao, $row): array {
                $card = CartaoCredito::query()
                    ->where('id', (int) $cartao->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (!$card) {
                    return [
                        'success' => false,
                        'message' => 'Cartão não encontrado para persistir item de fatura.',
                    ];
                }

                if ((bool) ($card->arquivado ?? false)) {
                    return [
                        'success' => false,
                        'message' => 'Cartão arquivado não pode receber novos itens.',
                    ];
                }

                $diaVencimento = (int) ($card->dia_vencimento ?? 0);
                if ($diaVencimento <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Cartão sem dia de vencimento configurado para classificar a fatura.',
                    ];
                }

                $amount = round((float) ($row['amount'] ?? 0), 2);
                if ($amount <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Linha sem valor válido para importação de cartão.',
                    ];
                }

                $date = (string) $row['date'];
                $description = mb_substr(trim((string) $row['description']), 0, 190);
                $diaFechamento = (int) ($card->dia_fechamento ?? 0);
                $vencimento = $this->billingDateService->calcularDataVencimento(
                    $date,
                    $diaVencimento,
                    $diaFechamento > 0 ? $diaFechamento : null
                );
                $competencia = $this->billingDateService->calcularCompetencia(
                    $date,
                    $diaFechamento > 0 ? $diaFechamento : null
                );
                $fatura = $this->faturaSupportService->buscarOuCriarFatura(
                    $userId,
                    (int) $card->id,
                    (int) $vencimento['mes'],
                    (int) $vencimento['ano']
                );

                if (($row['type'] ?? 'despesa') === 'receita') {
                    $item = FaturaCartaoItem::query()->create([
                        'user_id' => $userId,
                        'cartao_credito_id' => (int) $card->id,
                        'fatura_id' => (int) $fatura->id,
                        'lancamento_id' => null,
                        'descricao' => mb_substr('Estorno - ' . $description, 0, 190),
                        'valor' => $this->faturaSupportService->moneyString(-$amount),
                        'tipo' => 'estorno',
                        'data_compra' => $date,
                        'data_vencimento' => (string) $vencimento['data'],
                        'mes_referencia' => (int) $competencia['mes'],
                        'ano_referencia' => (int) $competencia['ano'],
                        'eh_parcelado' => false,
                        'parcela_atual' => 1,
                        'total_parcelas' => 1,
                        'pago' => true,
                        'data_pagamento' => $date,
                    ]);

                    $novoTotal = max(0, (float) $fatura->valor_total - $amount);
                    $fatura->valor_total = $this->faturaSupportService->moneyString($novoTotal);
                    $fatura->save();

                    return [
                        'success' => true,
                        'fatura_id' => (int) $fatura->id,
                        'fatura_item_id' => (int) $item->id,
                    ];
                }

                $limiteDisponivel = (float) ($card->limite_disponivel ?? 0);
                if ($limiteDisponivel > 0 && $amount > $limiteDisponivel) {
                    return [
                        'success' => false,
                        'message' => sprintf(
                            'Limite insuficiente no cartão para a linha importada. Disponível: R$ %.2f, valor da linha: R$ %.2f.',
                            $limiteDisponivel,
                            $amount
                        ),
                    ];
                }

                $item = FaturaCartaoItem::query()->create([
                    'user_id' => $userId,
                    'cartao_credito_id' => (int) $card->id,
                    'fatura_id' => (int) $fatura->id,
                    'lancamento_id' => null,
                    'descricao' => $description,
                    'valor' => $this->faturaSupportService->moneyString($amount),
                    'tipo' => 'despesa',
                    'data_compra' => $date,
                    'data_vencimento' => (string) $vencimento['data'],
                    'mes_referencia' => (int) $competencia['mes'],
                    'ano_referencia' => (int) $competencia['ano'],
                    'eh_parcelado' => false,
                    'parcela_atual' => 1,
                    'total_parcelas' => 1,
                    'pago' => false,
                ]);

                $this->faturaSupportService->incrementarValorFatura($fatura, $amount);

                return [
                    'success' => true,
                    'fatura_id' => (int) $fatura->id,
                    'fatura_item_id' => (int) $item->id,
                ];
            });
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'import_persist_card_invoice_item',
                'user_id' => $userId,
                'cartao_id' => (int) ($cartao->id ?? 0),
            ], $userId);

            return [
                'success' => false,
                'message' => ImportSecurityPolicy::clientProcessingErrorMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $date = trim((string) ($row['date'] ?? ''));
        $description = ImportSanitizer::sanitizeText((string) ($row['description'] ?? ''), 190);
        $amount = (float) ($row['amount'] ?? 0);
        $type = strtolower(trim((string) ($row['type'] ?? 'despesa')));

        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('Linha sem data válida para importação.');
        }

        if ($description === '') {
            throw new \InvalidArgumentException('Linha sem descrição válida para importação.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Linha sem valor positivo para importação.');
        }

        if (!in_array($type, ['receita', 'despesa'], true)) {
            $type = 'despesa';
        }

        return [
            'date' => $date,
            'description' => $description,
            'amount' => round($amount, 2),
            'type' => $type,
            'memo' => ($memo = ImportSanitizer::sanitizeText((string) ($row['memo'] ?? ''), 500, true)) !== '' ? $memo : null,
            'external_id' => ($externalId = ImportSanitizer::sanitizeText((string) ($row['external_id'] ?? ''), 120)) !== '' ? $externalId : null,
            'raw' => is_array($row['raw'] ?? null) ? ImportSanitizer::sanitizeMixed($row['raw']) : [],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function generateRowHash(
        int $userId,
        int $contaId,
        array $row,
        string $importTarget = 'conta',
        ?int $cartaoId = null
    ): string {
        $importTarget = $this->normalizeImportTarget($importTarget);
        $externalId = trim((string) ($row['external_id'] ?? ''));

        if ($importTarget === 'cartao' && $cartaoId !== null && $externalId !== '') {
            return hash('sha256', implode('|', [
                $userId,
                'cartao',
                $cartaoId,
                $externalId,
            ]));
        }

        $payload = implode('|', [
            $userId,
            $contaId,
            $importTarget,
            $cartaoId ?? 0,
            $row['date'] ?? '',
            $row['description'] ?? '',
            number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
            $row['type'] ?? '',
            $externalId,
        ]);

        return hash('sha256', $payload);
    }

    private function isDuplicate(int $userId, int $contaId, string $rowHash): bool
    {
        return ImportacaoItem::query()
            ->where('user_id', $userId)
            ->where('conta_id', $contaId)
            ->where('row_hash', $rowHash)
            ->where('status', 'imported')
            ->exists();
    }

    private function decorateItemRowHash(string $baseRowHash, string $status, int $loteId, int $rowIndex): string
    {
        if ($status === 'imported') {
            return $baseRowHash;
        }

        return hash('sha256', implode('|', [$baseRowHash, $status, $loteId, $rowIndex]));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $extraRaw
     */
    private function persistItem(
        int $loteId,
        int $userId,
        int $contaId,
        array $row,
        string $rowHash,
        string $status,
        ?int $lancamentoId,
        ?string $message,
        array $extraRaw = []
    ): void {
        $rawPayload = is_array($row['raw'] ?? null) ? $row['raw'] : [];
        if ($extraRaw !== []) {
            $rawPayload = array_merge($rawPayload, $extraRaw);
        }

        ImportacaoItem::query()->create([
            'lote_id' => $loteId,
            'user_id' => $userId,
            'conta_id' => $contaId,
            'lancamento_id' => $lancamentoId,
            'row_hash' => $rowHash,
            'status' => $status,
            'external_id' => $row['external_id'] ?? null,
            'data' => $row['date'],
            'amount' => $row['amount'],
            'tipo' => $row['type'],
            'description' => $row['description'],
            'memo' => $row['memo'] ?? null,
            'raw_json' => json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            'message' => $message,
        ]);
    }

    /**
     * @param array<string, mixed> $resultData
     */
    private function extractLancamentoId(array $resultData): ?int
    {
        $lancamento = is_array($resultData['lancamento'] ?? null) ? $resultData['lancamento'] : null;
        $id = $lancamento['id'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    private function resolveFinalStatus(int $imported, int $duplicated, int $errors): string
    {
        if ($errors > 0 && $imported === 0 && $duplicated === 0) {
            return 'failed';
        }

        if ($errors > 0) {
            return 'processed_with_errors';
        }

        if ($imported === 0 && $duplicated > 0) {
            return 'processed_duplicates_only';
        }

        if ($duplicated > 0) {
            return 'processed_with_duplicates';
        }

        return 'processed';
    }

    private function buildObservacao(?string $memo, string $sourceType): string
    {
        $parts = [
            'Importado via ' . strtoupper(trim($sourceType) !== '' ? $sourceType : 'OFX'),
        ];

        if (is_string($memo) && trim($memo) !== '') {
            $parts[] = trim($memo);
        }

        return mb_substr(implode(' | ', $parts), 0, 500);
    }

    /**
     * @param array<int, string> $warnings
     */
    private function createBatch(
        int $userId,
        int $contaId,
        string $sourceType,
        string $filename,
        string $contents,
        array $warnings,
        string $importTarget,
        ?int $cartaoId,
        ?string $cartaoNome,
        int $totalRows
    ): ImportacaoLote {
        return ImportacaoLote::query()->create([
            'user_id' => $userId,
            'conta_id' => $contaId,
            'source_type' => strtolower(trim($sourceType)),
            'filename' => $filename,
            'file_hash' => hash('sha256', $contents),
            'status' => 'processing',
            'total_rows' => $totalRows,
            'imported_rows' => 0,
            'duplicate_rows' => 0,
            'error_rows' => 0,
            'error_summary' => null,
            'meta_json' => json_encode([
                'warnings' => $warnings,
                'import_target' => $this->normalizeImportTarget($importTarget),
                'cartao_id' => $cartaoId,
                'cartao_nome' => $cartaoNome,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ]);
    }

    /**
     * @param array<int, string> $errorMessages
     */
    private function finalizeBatch(
        ImportacaoLote $lote,
        int $imported,
        int $duplicated,
        int $errors,
        array $errorMessages
    ): string {
        $status = $this->resolveFinalStatus($imported, $duplicated, $errors);
        $lote->status = $status;
        $lote->imported_rows = $imported;
        $lote->duplicate_rows = $duplicated;
        $lote->error_rows = $errors;
        $lote->error_summary = $errorMessages !== [] ? implode(' | ', array_slice($errorMessages, 0, 6)) : null;
        $lote->save();

        return $status;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBatch(ImportacaoLote $lote): array
    {
        $meta = $this->decodeBatchMeta((string) ($lote->meta_json ?? ''));
        $importTarget = $this->normalizeImportTarget((string) ($meta['import_target'] ?? 'conta'));
        $cartaoId = is_numeric($meta['cartao_id'] ?? null) ? (int) $meta['cartao_id'] : null;
        $cartaoNome = trim((string) ($meta['cartao_nome'] ?? ''));

        return [
            'id' => (int) $lote->id,
            'status' => (string) $lote->status,
            'import_target' => $importTarget,
            'conta_id' => (int) $lote->conta_id,
            'cartao_id' => $cartaoId,
            'cartao_nome' => $cartaoNome !== '' ? $cartaoNome : null,
            'source_type' => (string) $lote->source_type,
            'filename' => (string) ($lote->filename ?? ''),
            'total_rows' => (int) $lote->total_rows,
            'imported_rows' => (int) $lote->imported_rows,
            'duplicate_rows' => (int) $lote->duplicate_rows,
            'error_rows' => (int) $lote->error_rows,
            'created_at' => (string) $lote->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBatchMeta(string $metaJson): array
    {
        if (trim($metaJson) === '') {
            return [];
        }

        $decoded = json_decode($metaJson, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }
}
