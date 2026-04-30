<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Container\ApplicationContainer;
use Application\Casts\MoneyDecimalCast;
use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\ServiceResultDTO;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Models\CartaoCredito;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\ImportacaoItem;
use Application\Models\ImportacaoLote;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\Cartao\CartaoBillingDateService;
use Application\Services\Cartao\CartaoFaturaSupportService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Validators\LancamentoValidator;
use Illuminate\Database\Capsule\Manager as DB;

class ImportExecutionService
{
    private readonly ImportPreviewService $previewService;
    private readonly LancamentoCreationService $lancamentoCreationService;
    private readonly CartaoBillingDateService $billingDateService;
    private readonly CartaoFaturaSupportService $faturaSupportService;

    public function __construct(
        ?ImportPreviewService $previewService = null,
        ?LancamentoCreationService $lancamentoCreationService = null,
        ?CartaoBillingDateService $billingDateService = null,
        ?CartaoFaturaSupportService $faturaSupportService = null,
    ) {
        $this->previewService = ApplicationContainer::resolveOrNew($previewService, ImportPreviewService::class);
        $this->lancamentoCreationService = ApplicationContainer::resolveOrNew($lancamentoCreationService, LancamentoCreationService::class);
        $this->billingDateService = ApplicationContainer::resolveOrNew($billingDateService, CartaoBillingDateService::class);
        $this->faturaSupportService = ApplicationContainer::resolveOrNew($faturaSupportService, CartaoFaturaSupportService::class);
    }

    public function prepareExecution(
        string $sourceType,
        string $contents,
        ImportProfileConfigDTO $profile,
        string $filename = '',
        string $importTarget = 'conta',
        ?int $cartaoId = null,
        ?int $userId = null,
        array $rowOverrides = []
    ): ServiceResultDTO {
        $importTarget = $this->normalizeImportTarget($importTarget);
        $preview = $this->previewService->preview(
            $sourceType,
            $contents,
            $profile,
            $filename,
            $importTarget,
            $cartaoId,
            $userId
        );

        if ($userId !== null && $userId > 0 && $rowOverrides !== []) {
            $preview = $this->applyRowOverridesToPreview($preview, $rowOverrides, $userId);
        }

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
        ?int $cartaoId = null,
        array $rowOverrides = []
    ): ServiceResultDTO {
        $importTarget = $this->normalizeImportTarget($importTarget);
        $preparation = $this->prepareExecution(
            $sourceType,
            $contents,
            $profile,
            $filename,
            $importTarget,
            $cartaoId,
            $userId,
            $rowOverrides
        );

        if (!$preparation->success) {
            return $preparation;
        }

        $preview = is_array($preparation->data['preview'] ?? null)
            ? $preparation->data['preview']
            : [];
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
                $normalized = $this->normalizeRow($row, (int) $index);
            } catch (\InvalidArgumentException $e) {
                $errors++;
                $errorMessages[] = $e->getMessage();
                continue;
            }

            $rowHash = $this->generateRowHash($userId, $contaId, $normalized, 'conta', null);
            try {
                $outcome = $this->processContaRow(
                    $userId,
                    $contaId,
                    $normalized,
                    $rowHash,
                    (int) $lote->id,
                    (int) $index,
                    (string) ($preview['source_type'] ?? $sourceType)
                );
            } catch (\Throwable $e) {
                $outcome = $this->handleRowProcessingException(
                    $e,
                    'conta',
                    $userId,
                    $contaId,
                    $normalized,
                    $rowHash,
                    (int) $lote->id,
                    (int) $index,
                    null
                );
            }

            if (($outcome['status'] ?? '') === 'imported') {
                $imported++;
                continue;
            }

            if (($outcome['status'] ?? '') === 'duplicate') {
                $duplicated++;
                continue;
            }

            $errors++;
            $errorMessage = (string) ($outcome['message'] ?? ImportSecurityPolicy::clientProcessingErrorMessage());
            $errorMessages[] = $errorMessage;
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
                'Vincule uma conta ao cartão antes de importar a fatura.',
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
                $normalized = $this->normalizeRow($row, (int) $index);
            } catch (\InvalidArgumentException $e) {
                $errors++;
                $errorMessages[] = $e->getMessage();
                continue;
            }

            $rowHash = $this->generateRowHash($userId, $contaId, $normalized, 'cartao', (int) $cartao->id);
            try {
                $outcome = $this->processCartaoRow(
                    $userId,
                    $contaId,
                    $cartao,
                    $normalized,
                    $rowHash,
                    (int) $lote->id,
                    (int) $index
                );
            } catch (\Throwable $e) {
                $outcome = $this->handleRowProcessingException(
                    $e,
                    'cartao',
                    $userId,
                    $contaId,
                    $normalized,
                    $rowHash,
                    (int) $lote->id,
                    (int) $index,
                    $cartao
                );
            }

            if (($outcome['status'] ?? '') === 'imported') {
                $imported++;

                $faturaId = is_numeric($outcome['fatura_id'] ?? null) ? (int) $outcome['fatura_id'] : null;
                if ($faturaId !== null) {
                    $faturasAfetadas[$faturaId] = true;
                }

                continue;
            }

            if (($outcome['status'] ?? '') === 'duplicate') {
                $duplicated++;
                continue;
            }

            $errors++;
            $errorMessage = (string) ($outcome['message'] ?? ImportSecurityPolicy::clientProcessingErrorMessage());
            $errorMessages[] = $errorMessage;
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
     * @param array<string, mixed> $normalized
     * @return array{status:string,message?:string}
     */
    private function processContaRow(
        int $userId,
        int $contaId,
        array $normalized,
        string $rowHash,
        int $loteId,
        int $rowIndex,
        string $sourceType
    ): array {
        return DB::transaction(function () use ($userId, $contaId, $normalized, $rowHash, $loteId, $rowIndex, $sourceType): array {
            if ($this->isDuplicate($userId, $contaId, $rowHash)) {
                $duplicateHash = $this->decorateItemRowHash($rowHash, 'duplicate', $loteId, $rowIndex);
                $this->persistItem(
                    $loteId,
                    $userId,
                    $contaId,
                    $normalized,
                    $duplicateHash,
                    'duplicate',
                    null,
                    'Linha ignorada por duplicidade.',
                    $this->buildContaImportMeta($normalized)
                );

                return ['status' => 'duplicate'];
            }

            $result = $this->lancamentoCreationService->createFromPayload($userId, [
                'tipo' => $normalized['type'],
                'data' => $normalized['date'],
                'valor' => $normalized['amount'],
                'descricao' => $normalized['description'],
                'observacao' => $this->buildObservacao($normalized['memo'] ?? null, $sourceType),
                'conta_id' => $contaId,
                'categoria_id' => $normalized['categoria_id'] ?? null,
                'subcategoria_id' => $normalized['subcategoria_id'] ?? null,
                'pago' => true,
            ]);

            if ($result->success) {
                $lancamentoId = $this->extractLancamentoId($result->data);
                $this->learnFromImportedRow($userId, $normalized);
                $this->persistItem(
                    $loteId,
                    $userId,
                    $contaId,
                    $normalized,
                    $rowHash,
                    'imported',
                    $lancamentoId,
                    null,
                    $this->buildContaImportMeta($normalized)
                );

                return ['status' => 'imported'];
            }

            $errorMessage = $result->message !== '' ? $result->message : 'Falha ao criar lançamento.';
            $errorHash = $this->decorateItemRowHash($rowHash, 'error', $loteId, $rowIndex);
            $this->persistItem(
                $loteId,
                $userId,
                $contaId,
                $normalized,
                $errorHash,
                'error',
                null,
                $errorMessage,
                $this->buildContaImportMeta($normalized)
            );

            return ['status' => 'error', 'message' => $errorMessage];
        });
    }

    /**
     * @param array<string, mixed> $normalized
     * @return array{status:string,message?:string,fatura_id?:int,fatura_item_id?:int}
     */
    private function processCartaoRow(
        int $userId,
        int $contaId,
        CartaoCredito $cartao,
        array $normalized,
        string $rowHash,
        int $loteId,
        int $rowIndex
    ): array {
        return DB::transaction(function () use ($userId, $contaId, $cartao, $normalized, $rowHash, $loteId, $rowIndex): array {
            $cartaoMeta = [
                'import_target' => 'cartao',
                'cartao_id' => (int) $cartao->id,
                'cartao_nome' => (string) ($cartao->nome_cartao ?? ''),
            ];

            if ($this->isDuplicateCardInvoiceItem($userId, $contaId, (int) $cartao->id, $rowHash, $normalized)) {
                $duplicateHash = $this->decorateItemRowHash($rowHash, 'duplicate', $loteId, $rowIndex);
                $this->persistItem(
                    $loteId,
                    $userId,
                    $contaId,
                    $normalized,
                    $duplicateHash,
                    'duplicate',
                    null,
                    'Linha ignorada por duplicidade.',
                    $cartaoMeta
                );

                return ['status' => 'duplicate'];
            }

            $persisted = $this->persistCardInvoiceItem($userId, $cartao, $normalized);
            if (($persisted['success'] ?? false) === true) {
                $faturaId = is_numeric($persisted['fatura_id'] ?? null) ? (int) $persisted['fatura_id'] : null;
                $faturaItemId = is_numeric($persisted['fatura_item_id'] ?? null) ? (int) $persisted['fatura_item_id'] : null;

                $this->learnFromImportedRow($userId, $normalized);
                $this->persistItem(
                    $loteId,
                    $userId,
                    $contaId,
                    $normalized,
                    $rowHash,
                    'imported',
                    null,
                    null,
                    array_merge($cartaoMeta, [
                        'fatura_id' => $faturaId,
                        'fatura_item_id' => $faturaItemId,
                        'categoria_id' => $normalized['categoria_id'] ?? null,
                        'subcategoria_id' => $normalized['subcategoria_id'] ?? null,
                        'categoria_nome' => $normalized['categoria_nome'] ?? null,
                        'subcategoria_nome' => $normalized['subcategoria_nome'] ?? null,
                        'categoria_editada' => $normalized['categoria_editada'] ?? false,
                        'categoria_learning_source' => $normalized['categoria_learning_source'] ?? null,
                    ])
                );

                return [
                    'status' => 'imported',
                    'fatura_id' => $faturaId,
                    'fatura_item_id' => $faturaItemId,
                ];
            }

            $errorMessage = trim((string) ($persisted['message'] ?? 'Falha ao persistir item de fatura.'));
            $errorHash = $this->decorateItemRowHash($rowHash, 'error', $loteId, $rowIndex);
            $this->persistItem(
                $loteId,
                $userId,
                $contaId,
                $normalized,
                $errorHash,
                'error',
                null,
                $errorMessage,
                $cartaoMeta
            );

            return ['status' => 'error', 'message' => $errorMessage];
        });
    }

    /**
     * @param array<string, mixed> $normalized
     * @return array{status:string,message:string}
     */
    private function handleRowProcessingException(
        \Throwable $e,
        string $importTarget,
        int $userId,
        int $contaId,
        array $normalized,
        string $rowHash,
        int $loteId,
        int $rowIndex,
        ?CartaoCredito $cartao
    ): array {
        $message = ImportSecurityPolicy::clientProcessingErrorMessage();
        LogService::captureException($e, $importTarget === 'cartao' ? LogCategory::CARTAO : LogCategory::LANCAMENTO, [
            'action' => 'import_row_processing',
            'import_target' => $importTarget,
            'user_id' => $userId,
            'conta_id' => $contaId,
            'cartao_id' => $cartao !== null ? (int) $cartao->id : null,
            'row_hash' => $rowHash,
            'row' => [
                'date' => $normalized['date'] ?? null,
                'amount' => $normalized['amount'] ?? null,
                'type' => $normalized['type'] ?? null,
                'description' => $normalized['description'] ?? null,
                'external_id' => $normalized['external_id'] ?? null,
            ],
        ], $userId);

        $extraMeta = $importTarget === 'cartao' && $cartao !== null
            ? [
                'import_target' => 'cartao',
                'cartao_id' => (int) $cartao->id,
                'cartao_nome' => (string) ($cartao->nome_cartao ?? ''),
            ]
            : $this->buildContaImportMeta($normalized);

        try {
            $this->persistItem(
                $loteId,
                $userId,
                $contaId,
                $normalized,
                $this->decorateItemRowHash($rowHash, 'exception', $loteId, $rowIndex),
                'error',
                null,
                $message,
                $extraMeta
            );
        } catch (\Throwable $persistException) {
            LogService::captureException($persistException, LogCategory::GENERAL, [
                'action' => 'import_row_error_tracking',
                'import_target' => $importTarget,
                'user_id' => $userId,
                'conta_id' => $contaId,
                'row_hash' => $rowHash,
            ], $userId);
        }

        return ['status' => 'error', 'message' => $message];
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

                $amount = $this->moneyFloat($row['amount'] ?? 0);
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
                $categoriaId = $this->parsePositiveId($row['categoria_id'] ?? null);
                $subcategoriaId = $this->parsePositiveId($row['subcategoria_id'] ?? null);

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
                        'categoria_id' => $categoriaId,
                        'subcategoria_id' => $subcategoriaId,
                        'eh_parcelado' => false,
                        'parcela_atual' => 1,
                        'total_parcelas' => 1,
                        'pago' => true,
                        'data_pagamento' => $date,
                    ]);

                    $novoTotal = max(0, $this->decimalAttribute($fatura, 'valor_total') - $amount);
                    $fatura->valor_total = $this->faturaSupportService->moneyString($novoTotal);
                    $fatura->save();

                    return [
                        'success' => true,
                        'fatura_id' => (int) $fatura->id,
                        'fatura_item_id' => (int) $item->id,
                    ];
                }

                $limiteDisponivel = $this->decimalAttribute($card, 'limite_disponivel');
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
                    'categoria_id' => $categoriaId,
                    'subcategoria_id' => $subcategoriaId,
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
    private function normalizeRow(array $row, int $rowIndex = 0): array
    {
        $date = trim((string) ($row['date'] ?? ''));
        $description = ImportSanitizer::sanitizeText((string) ($row['description'] ?? ''), 190);
        try {
            $amount = $this->moneyString($row['amount'] ?? null);
        } catch (\InvalidArgumentException) {
            throw new \InvalidArgumentException('Linha sem valor positivo para importação.');
        }
        $type = strtolower(trim((string) ($row['type'] ?? 'despesa')));

        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('Linha sem data válida para importação.');
        }

        if ($description === '') {
            throw new \InvalidArgumentException('Linha sem descrição válida para importação.');
        }

        if ((float) $amount <= 0) {
            throw new \InvalidArgumentException('Linha sem valor positivo para importação.');
        }

        if (!in_array($type, ['receita', 'despesa'], true)) {
            $type = 'despesa';
        }

        $rowKey = ImportSanitizer::sanitizeText((string) ($row['row_key'] ?? ''), 80);
        if ($rowKey === '') {
            $rowKey = ImportRowCategorizationService::buildRowKeyFromPayload($row, $rowIndex);
        }

        $categoriaId = $this->parsePositiveId($row['categoria_id'] ?? $row['categoriaId'] ?? null);
        $subcategoriaId = $this->parsePositiveId($row['subcategoria_id'] ?? $row['subcategoriaId'] ?? null);
        $categoriaSugeridaId = $this->parsePositiveId($row['categoria_sugerida_id'] ?? null);
        $subcategoriaSugeridaId = $this->parsePositiveId($row['subcategoria_sugerida_id'] ?? null);

        $resolvedNames = ImportRowCategorizationService::resolveCategoryNames($categoriaId, $subcategoriaId);
        $resolvedSuggestedNames = ImportRowCategorizationService::resolveCategoryNames($categoriaSugeridaId, $subcategoriaSugeridaId);

        return [
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
            'memo' => ($memo = ImportSanitizer::sanitizeText((string) ($row['memo'] ?? ''), 500, true)) !== '' ? $memo : null,
            'external_id' => ($externalId = ImportSanitizer::sanitizeText((string) ($row['external_id'] ?? ''), 120)) !== '' ? $externalId : null,
            'raw' => is_array($row['raw'] ?? null) ? ImportSanitizer::sanitizeMixed($row['raw']) : [],
            'row_key' => $rowKey,
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'categoria_nome' => ($categoriaNome = ImportSanitizer::sanitizeText((string) ($row['categoria_nome'] ?? ''), 100)) !== ''
                ? $categoriaNome
                : ($resolvedNames['categoria_nome'] ?? null),
            'subcategoria_nome' => ($subcategoriaNome = ImportSanitizer::sanitizeText((string) ($row['subcategoria_nome'] ?? ''), 100)) !== ''
                ? $subcategoriaNome
                : ($resolvedNames['subcategoria_nome'] ?? null),
            'categoria_sugerida_id' => $categoriaSugeridaId,
            'subcategoria_sugerida_id' => $subcategoriaSugeridaId,
            'categoria_sugerida_nome' => ($categoriaSugeridaNome = ImportSanitizer::sanitizeText((string) ($row['categoria_sugerida_nome'] ?? ''), 100)) !== ''
                ? $categoriaSugeridaNome
                : ($resolvedSuggestedNames['categoria_nome'] ?? null),
            'subcategoria_sugerida_nome' => ($subcategoriaSugeridaNome = ImportSanitizer::sanitizeText((string) ($row['subcategoria_sugerida_nome'] ?? ''), 100)) !== ''
                ? $subcategoriaSugeridaNome
                : ($resolvedSuggestedNames['subcategoria_nome'] ?? null),
            'categoria_source' => ($categoriaSource = ImportSanitizer::sanitizeText((string) ($row['categoria_source'] ?? ''), 40)) !== '' ? $categoriaSource : null,
            'categoria_confidence' => ($categoriaConfidence = ImportSanitizer::sanitizeText((string) ($row['categoria_confidence'] ?? ''), 40)) !== '' ? $categoriaConfidence : null,
            'categoria_editada' => $this->toBoolean($row['categoria_editada'] ?? false),
            'categoria_learning_source' => ($categoriaLearningSource = ImportSanitizer::sanitizeText((string) ($row['categoria_learning_source'] ?? ''), 20)) !== ''
                ? $categoriaLearningSource
                : null,
        ];
    }

    /**
     * @param array<string, mixed> $preview
     * @param array<string, mixed> $rowOverrides
     * @return array<string, mixed>
     */
    private function applyRowOverridesToPreview(array $preview, array $rowOverrides, int $userId): array
    {
        $rows = is_array($preview['rows'] ?? null) ? $preview['rows'] : [];
        if ($rows === []) {
            return $preview;
        }

        $normalizedOverrides = $this->normalizeRowOverrides($rowOverrides);
        if ($normalizedOverrides === []) {
            return $preview;
        }

        $matchedOverrideKeys = [];
        $overrideErrors = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $rowKey = trim((string) ($row['row_key'] ?? ''));
            if ($rowKey === '') {
                $rowKey = ImportRowCategorizationService::buildRowKeyFromPayload($row, (int) $index);
                $row['row_key'] = $rowKey;
            }

            if (!isset($normalizedOverrides[$rowKey])) {
                $rows[$index] = $row;
                continue;
            }

            [$rows[$index], $rowErrors] = $this->applySingleRowOverride(
                $row,
                $normalizedOverrides[$rowKey],
                $userId,
                (int) $index
            );

            $matchedOverrideKeys[$rowKey] = true;
            if ($rowErrors !== []) {
                $overrideErrors = array_merge($overrideErrors, $rowErrors);
            }
        }

        $unknownOverrideKeys = array_values(array_diff(array_keys($normalizedOverrides), array_keys($matchedOverrideKeys)));
        if ($unknownOverrideKeys !== []) {
            $overrideErrors[] = 'O preview mudou desde a revisão das categorias. Gere o preview novamente antes de confirmar.';
        }

        $preview['rows'] = $rows;
        if ($overrideErrors !== []) {
            $preview['errors'] = array_values(array_unique(array_merge(
                is_array($preview['errors'] ?? null) ? $preview['errors'] : [],
                $overrideErrors
            )));
            $preview['can_confirm'] = false;
        }

        return $preview;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $override
     * @return array{0:array<string, mixed>,1:array<int, string>}
     */
    private function applySingleRowOverride(array $row, array $override, int $userId, int $rowIndex): array
    {
        $categoriaId = $this->parsePositiveId($override['categoria_id'] ?? null);
        $subcategoriaId = $this->parsePositiveId($override['subcategoria_id'] ?? null);

        $overrideSuggestedCategoriaId = $this->parsePositiveId($override['categoria_sugerida_id'] ?? null);
        $overrideSuggestedSubcategoriaId = $this->parsePositiveId($override['subcategoria_sugerida_id'] ?? null);
        $overrideSuggestedNames = ImportRowCategorizationService::resolveCategoryNames(
            $overrideSuggestedCategoriaId,
            $overrideSuggestedSubcategoriaId
        );

        $rowErrors = [];
        if ($subcategoriaId !== null && $categoriaId === null) {
            $rowErrors[] = sprintf('Linha %d: selecione uma categoria antes da subcategoria.', $rowIndex + 1);
            return [$row, $rowErrors];
        }

        $validationErrors = [];
        $categoriaId = LancamentoValidator::validateCategoriaOwnership($categoriaId, $userId, $validationErrors);
        if ($subcategoriaId !== null && $categoriaId !== null) {
            $subcategoriaId = LancamentoValidator::validateSubcategoriaOwnership($subcategoriaId, $categoriaId, $userId, $validationErrors);
        }

        if ($validationErrors !== []) {
            foreach ($validationErrors as $message) {
                $rowErrors[] = sprintf('Linha %d: %s', $rowIndex + 1, $message);
            }

            return [$row, $rowErrors];
        }

        $suggestedCategoriaId = $this->parsePositiveId(
            $row['categoria_sugerida_id']
                ?? $overrideSuggestedCategoriaId
                ?? $row['categoria_id']
                ?? null
        );
        $suggestedSubcategoriaId = $this->parsePositiveId(
            $row['subcategoria_sugerida_id']
                ?? $overrideSuggestedSubcategoriaId
                ?? $row['subcategoria_id']
                ?? null
        );

        $wasEdited = $this->toBoolean($override['user_edited'] ?? false)
            || $categoriaId !== $suggestedCategoriaId
            || $subcategoriaId !== $suggestedSubcategoriaId;

        $learningSource = null;
        if ($wasEdited && $categoriaId !== null) {
            if ($suggestedCategoriaId !== null || $suggestedSubcategoriaId !== null) {
                if ($categoriaId !== $suggestedCategoriaId || $subcategoriaId !== $suggestedSubcategoriaId) {
                    $learningSource = 'correction';
                }
            } else {
                $learningSource = 'confirmed';
            }
        }

        $resolvedNames = ImportRowCategorizationService::resolveCategoryNames($categoriaId, $subcategoriaId);
        $row['categoria_sugerida_id'] = $suggestedCategoriaId;
        $row['subcategoria_sugerida_id'] = $suggestedSubcategoriaId;
        $row['categoria_sugerida_nome'] = ($row['categoria_sugerida_nome'] ?? null)
            ?: ($override['categoria_sugerida_nome'] ?? null)
            ?: ($overrideSuggestedNames['categoria_nome'] ?? null);
        $row['subcategoria_sugerida_nome'] = ($row['subcategoria_sugerida_nome'] ?? null)
            ?: ($override['subcategoria_sugerida_nome'] ?? null)
            ?: ($overrideSuggestedNames['subcategoria_nome'] ?? null);
        $row['categoria_id'] = $categoriaId;
        $row['subcategoria_id'] = $subcategoriaId;
        $row['categoria_nome'] = $resolvedNames['categoria_nome'];
        $row['subcategoria_nome'] = $resolvedNames['subcategoria_nome'];
        $row['categoria_editada'] = $wasEdited;
        $row['categoria_learning_source'] = $learningSource;
        if (!isset($row['categoria_source']) || $row['categoria_source'] === null || $row['categoria_source'] === '') {
            $row['categoria_source'] = ($categoriaSource = ImportSanitizer::sanitizeText((string) ($override['categoria_source'] ?? ''), 40)) !== ''
                ? $categoriaSource
                : null;
        }
        if (!isset($row['categoria_confidence']) || $row['categoria_confidence'] === null || $row['categoria_confidence'] === '') {
            $row['categoria_confidence'] = ($categoriaConfidence = ImportSanitizer::sanitizeText((string) ($override['categoria_confidence'] ?? ''), 40)) !== ''
                ? $categoriaConfidence
                : null;
        }
        $row['categoria_source'] = $wasEdited
            ? ($categoriaId !== null ? 'manual' : null)
            : ($row['categoria_source'] ?? null);
        $row['categoria_confidence'] = $wasEdited
            ? ($categoriaId !== null ? 'manual' : null)
            : ($row['categoria_confidence'] ?? null);

        return [$row, []];
    }

    /**
     * @param array<string, mixed> $rowOverrides
     * @return array<string, array<string, mixed>>
     */
    private function normalizeRowOverrides(array $rowOverrides): array
    {
        $normalized = [];

        foreach ($rowOverrides as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $rowKey = is_string($key) && trim($key) !== ''
                ? trim($key)
                : trim((string) ($value['row_key'] ?? ''));

            if ($rowKey === '') {
                continue;
            }

            $normalized[$rowKey] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $normalizedRow
     * @return array<string, mixed>
     */
    private function buildContaImportMeta(array $normalizedRow): array
    {
        return [
            'import_target' => 'conta',
            'row_key' => $normalizedRow['row_key'] ?? null,
            'categoria_id' => $normalizedRow['categoria_id'] ?? null,
            'subcategoria_id' => $normalizedRow['subcategoria_id'] ?? null,
            'categoria_nome' => $normalizedRow['categoria_nome'] ?? null,
            'subcategoria_nome' => $normalizedRow['subcategoria_nome'] ?? null,
            'categoria_sugerida_id' => $normalizedRow['categoria_sugerida_id'] ?? null,
            'subcategoria_sugerida_id' => $normalizedRow['subcategoria_sugerida_id'] ?? null,
            'categoria_sugerida_nome' => $normalizedRow['categoria_sugerida_nome'] ?? null,
            'subcategoria_sugerida_nome' => $normalizedRow['subcategoria_sugerida_nome'] ?? null,
            'categoria_source' => $normalizedRow['categoria_source'] ?? null,
            'categoria_confidence' => $normalizedRow['categoria_confidence'] ?? null,
            'categoria_editada' => $normalizedRow['categoria_editada'] ?? false,
            'categoria_learning_source' => $normalizedRow['categoria_learning_source'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $normalizedRow
     */
    private function learnFromImportedRow(int $userId, array $normalizedRow): void
    {
        $categoriaId = $this->parsePositiveId($normalizedRow['categoria_id'] ?? null);
        $learningSource = trim((string) ($normalizedRow['categoria_learning_source'] ?? ''));
        if ($categoriaId === null || $learningSource === '') {
            return;
        }

        CategoryRuleEngine::learn(
            $userId,
            (string) ($normalizedRow['description'] ?? ''),
            $categoriaId,
            $this->parsePositiveId($normalizedRow['subcategoria_id'] ?? null),
            $learningSource
        );
    }

    private function parsePositiveId(mixed $value): ?int
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
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
            $this->moneyString($row['amount'] ?? 0),
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

    /**
     * @param array<string, mixed> $row
     */
    private function isDuplicateCardInvoiceItem(
        int $userId,
        int $contaId,
        int $cartaoId,
        string $rowHash,
        array $row
    ): bool {
        $items = ImportacaoItem::query()
            ->where('user_id', $userId)
            ->where('conta_id', $contaId)
            ->where('row_hash', $rowHash)
            ->where('status', 'imported')
            ->get(['id', 'raw_json']);

        if ($items->isEmpty()) {
            return false;
        }

        $hasRelevantCardHistory = false;
        $historyContexts = [];
        foreach ($items as $item) {
            $raw = json_decode((string) ($item->raw_json ?? ''), true);
            if (!is_array($raw)) {
                $raw = [];
            }

            $rawTarget = $this->normalizeImportTarget((string) ($raw['import_target'] ?? 'cartao'));
            if ($rawTarget !== 'cartao') {
                continue;
            }

            $rawCartaoId = $this->parsePositiveId($raw['cartao_id'] ?? null);
            if ($rawCartaoId !== null && $rawCartaoId !== $cartaoId) {
                continue;
            }

            $hasRelevantCardHistory = true;
            $faturaItemId = $this->parsePositiveId($raw['fatura_item_id'] ?? null);
            $historyContexts[] = [
                'importacao_item_id' => (int) ($item->id ?? 0),
                'raw_import_target' => $rawTarget,
                'raw_cartao_id' => $rawCartaoId,
                'raw_fatura_id' => $this->parsePositiveId($raw['fatura_id'] ?? null),
                'raw_fatura_item_id' => $faturaItemId,
            ];

            $linkedItem = $faturaItemId !== null
                ? $this->findCardInvoiceItem($userId, $cartaoId, $faturaItemId)
                : null;
            if ($linkedItem !== null) {
                if ($this->cardInvoiceItemMatchesRow($linkedItem, $row)) {
                    $this->logCardInvoiceDuplicateDecision(
                        LogLevel::WARNING,
                        'linked_fatura_item',
                        $userId,
                        $contaId,
                        $cartaoId,
                        $rowHash,
                        $row,
                        ['history' => end($historyContexts) ?: []],
                        $linkedItem
                    );

                    return true;
                }

                $historyContexts[array_key_last($historyContexts)]['linked_item_mismatch'] = true;
                $historyContexts[array_key_last($historyContexts)]['linked_item'] = $this->formatInvoiceItemForLog($linkedItem);
            }
        }

        $matchingItem = $hasRelevantCardHistory ? $this->findMatchingCardInvoiceItem($userId, $cartaoId, $row) : null;
        if ($matchingItem !== null) {
            $this->logCardInvoiceDuplicateDecision(
                LogLevel::WARNING,
                'matching_fatura_item',
                $userId,
                $contaId,
                $cartaoId,
                $rowHash,
                $row,
                ['histories' => $historyContexts],
                $matchingItem
            );

            return true;
        }

        if ($hasRelevantCardHistory) {
            $this->releaseStaleCardInvoiceHistory($items, $rowHash);
            $this->logCardInvoiceDuplicateDecision(
                LogLevel::INFO,
                'stale_history_released',
                $userId,
                $contaId,
                $cartaoId,
                $rowHash,
                $row,
                ['histories' => $historyContexts],
                null
            );
        }

        return false;
    }

    private function findCardInvoiceItem(int $userId, int $cartaoId, int $faturaItemId): ?FaturaCartaoItem
    {
        return FaturaCartaoItem::query()
            ->where('id', $faturaItemId)
            ->where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->whereNull('cancelado_em')
            ->first();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function cardInvoiceItemMatchesRow(FaturaCartaoItem $item, array $row): bool
    {
        $expected = $this->expectedCardInvoiceRowData($row);
        if ($expected === null) {
            return false;
        }

        $itemDate = $item->data_compra instanceof \DateTimeInterface
            ? $item->data_compra->format('Y-m-d')
            : substr((string) $item->data_compra, 0, 10);

        return $itemDate === $expected['date']
            && (string) $item->descricao === $expected['description']
            && $this->moneyAttribute($item, 'valor') === $expected['amount']
            && (string) $item->tipo === $expected['type'];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function findMatchingCardInvoiceItem(int $userId, int $cartaoId, array $row): ?FaturaCartaoItem
    {
        $expected = $this->expectedCardInvoiceRowData($row);
        if ($expected === null) {
            return null;
        }

        return FaturaCartaoItem::query()
            ->where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->where('data_compra', $expected['date'])
            ->where('descricao', $expected['description'])
            ->where('valor', $expected['amount'])
            ->where('tipo', $expected['type'])
            ->whereNull('cancelado_em')
            ->first();
    }

    /**
     * @param array<string, mixed> $row
     * @return array{date:string,description:string,amount:string,type:string}|null
     */
    private function expectedCardInvoiceRowData(array $row): ?array
    {
        $amount = $this->moneyFloat($row['amount'] ?? 0);
        $date = trim((string) ($row['date'] ?? ''));
        $description = mb_substr(trim((string) ($row['description'] ?? '')), 0, 190);

        if ($amount <= 0 || $date === '' || $description === '') {
            return null;
        }

        $isRefund = ($row['type'] ?? 'despesa') === 'receita';

        return [
            'date' => $date,
            'description' => $isRefund ? mb_substr('Estorno - ' . $description, 0, 190) : $description,
            'amount' => $this->faturaSupportService->moneyString($isRefund ? -$amount : $amount),
            'type' => $isRefund ? 'estorno' : 'despesa',
        ];
    }

    /**
     * @param iterable<ImportacaoItem> $items
     */
    private function releaseStaleCardInvoiceHistory(iterable $items, string $rowHash): void
    {
        foreach ($items as $item) {
            $itemId = (int) ($item->id ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            ImportacaoItem::query()
                ->where('id', $itemId)
                ->where('row_hash', $rowHash)
                ->update([
                    'row_hash' => hash('sha256', implode('|', [$rowHash, 'stale-card-invoice-item', $itemId])),
                    'message' => 'Histórico liberado para reimportação: item de fatura não encontrado.',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $extraContext
     */
    private function logCardInvoiceDuplicateDecision(
        LogLevel $level,
        string $reason,
        int $userId,
        int $contaId,
        int $cartaoId,
        string $rowHash,
        array $row,
        array $extraContext,
        ?FaturaCartaoItem $invoiceItem
    ): void {
        LogService::persist(
            level: $level,
            category: LogCategory::FATURA,
            message: 'Importação de fatura: decisão de duplicidade',
            context: array_merge([
                'action' => 'import_card_invoice_duplicate_decision',
                'decision_reason' => $reason,
                'user_id' => $userId,
                'conta_id' => $contaId,
                'cartao_id' => $cartaoId,
                'row_hash' => $rowHash,
                'row' => [
                    'date' => $row['date'] ?? null,
                    'amount' => $row['amount'] ?? null,
                    'type' => $row['type'] ?? null,
                    'description' => $row['description'] ?? null,
                    'external_id' => $row['external_id'] ?? null,
                ],
                'matched_invoice_item' => $invoiceItem === null ? null : [
                    ...$this->formatInvoiceItemForLog($invoiceItem),
                ],
            ], $extraContext),
            userId: $userId
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatInvoiceItemForLog(FaturaCartaoItem $invoiceItem): array
    {
        return [
            'id' => (int) $invoiceItem->id,
            'fatura_id' => (int) $invoiceItem->fatura_id,
            'cartao_credito_id' => (int) $invoiceItem->cartao_credito_id,
            'descricao' => (string) $invoiceItem->descricao,
            'valor' => $this->moneyAttribute($invoiceItem, 'valor'),
            'tipo' => (string) $invoiceItem->tipo,
            'data_compra' => $invoiceItem->data_compra instanceof \DateTimeInterface
                ? $invoiceItem->data_compra->format('Y-m-d')
                : (string) $invoiceItem->data_compra,
            'categoria_id' => $invoiceItem->categoria_id === null ? null : (int) $invoiceItem->categoria_id,
            'subcategoria_id' => $invoiceItem->subcategoria_id === null ? null : (int) $invoiceItem->subcategoria_id,
            'cancelado_em' => $invoiceItem->cancelado_em instanceof \DateTimeInterface
                ? $invoiceItem->cancelado_em->format(DATE_ATOM)
                : $invoiceItem->cancelado_em,
        ];
    }

    private function decorateItemRowHash(string $baseRowHash, string $status, int $loteId, int $rowIndex): string
    {
        if ($status === 'imported') {
            return $baseRowHash;
        }

        return hash('sha256', implode('|', [$baseRowHash, $status, $loteId, $rowIndex]));
    }

    private function decimalAttribute(\Illuminate\Database\Eloquent\Model $model, string $attribute): float
    {
        return $this->moneyFloat($model->getRawOriginal($attribute) ?? 0);
    }

    private function moneyAttribute(\Illuminate\Database\Eloquent\Model $model, string $attribute): string
    {
        return $this->moneyString($model->getRawOriginal($attribute) ?? 0);
    }

    private function moneyString(mixed $value): string
    {
        return MoneyDecimalCast::normalize($value) ?? '0.00';
    }

    private function moneyFloat(mixed $value): float
    {
        return (float) $this->moneyString($value);
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
