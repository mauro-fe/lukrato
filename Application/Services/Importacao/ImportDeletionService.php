<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Container\ApplicationContainer;
use Application\Models\CartaoCredito;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Models\ImportacaoItem;
use Application\Models\ImportacaoJob;
use Application\Models\ImportacaoLote;
use Application\Models\Lancamento;
use Application\Services\Cartao\CartaoBillingDateService;
use Illuminate\Database\Capsule\Manager as DB;

class ImportDeletionService
{
    private readonly ImportHistoryService $historyService;
    private readonly CartaoBillingDateService $billingDateService;

    public function __construct(
        ?ImportHistoryService $historyService = null,
        ?CartaoBillingDateService $billingDateService = null,
    ) {
        $this->historyService = ApplicationContainer::resolveOrNew($historyService, ImportHistoryService::class);
        $this->billingDateService = ApplicationContainer::resolveOrNew($billingDateService, CartaoBillingDateService::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteBatchForUser(int $userId, int $batchId): array
    {
        $existingBatch = ImportacaoLote::query()
            ->where('id', $batchId)
            ->where('user_id', $userId)
            ->first();

        if (!$existingBatch) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Lote de importação não encontrado.',
            ];
        }

        if (strtolower((string) ($existingBatch->status ?? '')) === 'processing') {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'O lote ainda está em processamento e não pode ser excluído agora.',
            ];
        }

        return DB::transaction(function () use ($userId, $batchId): array {
            $batch = ImportacaoLote::query()
                ->with('itens')
                ->where('id', $batchId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$batch) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'Lote de importação não encontrado.',
                ];
            }

            if (strtolower((string) ($batch->status ?? '')) === 'processing') {
                return [
                    'success' => false,
                    'status' => 409,
                    'message' => 'O lote ainda está em processamento e não pode ser excluído agora.',
                ];
            }

            $batchMeta = $this->decodeJson((string) ($batch->meta_json ?? ''));
            $deletedItemIds = [];
            $retainedItems = [];
            $affectedFaturaIds = [];
            $affectedCartaoIds = [];

            foreach ($batch->itens as $item) {
                $decision = $this->resolveDeletionDecision($userId, $batch, $item, $batchMeta);

                if (($decision['action'] ?? 'retain') === 'delete_tracking') {
                    $deletedItemIds[] = (int) $item->id;

                    $faturaId = $decision['fatura_id'] ?? null;
                    if (is_int($faturaId) && $faturaId > 0) {
                        $affectedFaturaIds[$faturaId] = true;
                    }

                    $cartaoId = $decision['cartao_id'] ?? null;
                    if (is_int($cartaoId) && $cartaoId > 0) {
                        $affectedCartaoIds[$cartaoId] = true;
                    }

                    continue;
                }

                $retainedItems[] = $this->formatRetainedItem($batch, $item, (string) ($decision['reason'] ?? 'Registro preservado.'));
            }

            if ($deletedItemIds !== []) {
                ImportacaoItem::query()->whereIn('id', $deletedItemIds)->delete();
            }

            $this->refreshFaturasAndCartoes($userId, array_keys($affectedFaturaIds), array_keys($affectedCartaoIds));

            ImportacaoJob::query()
                ->where('user_id', $userId)
                ->where('result_batch_id', $batchId)
                ->delete();

            $remainingItems = ImportacaoItem::query()
                ->where('lote_id', $batchId)
                ->orderBy('id')
                ->get();

            $deletedCount = count($deletedItemIds);
            $retainedCount = count($retainedItems);

            if ($remainingItems->isEmpty()) {
                ImportacaoLote::query()
                    ->where('id', $batchId)
                    ->where('user_id', $userId)
                    ->delete();

                return [
                    'success' => true,
                    'status' => 200,
                    'message' => 'Importação excluída com sucesso.',
                    'data' => [
                        'batch_id' => $batchId,
                        'batch_removed' => true,
                        'deleted_count' => $deletedCount,
                        'retained_count' => $retainedCount,
                        'retained_items' => $retainedItems,
                        'batch' => null,
                    ],
                ];
            }

            $counts = [
                'total_rows' => $remainingItems->count(),
                'imported_rows' => $remainingItems->where('status', 'imported')->count(),
                'duplicate_rows' => $remainingItems->where('status', 'duplicate')->count(),
                'error_rows' => $remainingItems->where('status', 'error')->count(),
            ];

            $summary = $deletedCount > 0
                ? sprintf('%d registro(s) preservado(s) por alteracao manual.', $retainedCount)
                : sprintf('Nenhum registro foi excluído. %d registro(s) ainda estão preservados.', $retainedCount);

            $batchMeta['deletion'] = [
                'partial' => true,
                'deleted_count' => $deletedCount,
                'retained_count' => $retainedCount,
                'retained_preview' => array_slice($retainedItems, 0, 10),
                'summary' => $summary,
                'last_attempt_at' => date('c'),
                'last_attempt_message' => $summary,
            ];

            $batch->status = $this->resolveBatchStatus(
                $counts['imported_rows'],
                $counts['duplicate_rows'],
                $counts['error_rows']
            );
            $batch->total_rows = $counts['total_rows'];
            $batch->imported_rows = $counts['imported_rows'];
            $batch->duplicate_rows = $counts['duplicate_rows'];
            $batch->error_rows = $counts['error_rows'];
            $batch->meta_json = $this->encodeJson($batchMeta);
            $batch->save();

            $historyBatch = $this->historyService->findForUser($userId, (int) $batch->id);

            return [
                'success' => true,
                'status' => 200,
                'message' => $deletedCount > 0
                    ? 'Importação parcialmente excluída. Alguns registros foram preservados.'
                    : 'Nenhum registro foi excluído porque os itens restantes foram alterados após a importação.',
                'data' => [
                    'batch_id' => (int) $batch->id,
                    'batch_removed' => false,
                    'deleted_count' => $deletedCount,
                    'retained_count' => $retainedCount,
                    'retained_items' => $retainedItems,
                    'batch' => $historyBatch,
                ],
            ];
        });
    }

    /**
     * @param array<string, mixed> $batchMeta
     * @return array<string, mixed>
     */
    private function resolveDeletionDecision(int $userId, ImportacaoLote $batch, ImportacaoItem $item, array $batchMeta): array
    {
        if (strtolower((string) ($item->status ?? '')) !== 'imported') {
            $raw = $this->decodeJson((string) ($item->raw_json ?? ''));

            return [
                'action' => 'delete_tracking',
                'fatura_id' => $this->toPositiveInt($raw['fatura_id'] ?? null),
                'cartao_id' => $this->toPositiveInt($raw['cartao_id'] ?? ($batchMeta['cartao_id'] ?? null)),
            ];
        }

        $raw = $this->decodeJson((string) ($item->raw_json ?? ''));
        $importTarget = $this->normalizeImportTarget((string) ($raw['import_target'] ?? ($batchMeta['import_target'] ?? 'conta')));

        if ($importTarget === 'cartao') {
            return $this->resolveCartaoDeletionDecision($userId, $item, $raw, $batch);
        }

        return $this->resolveContaDeletionDecision($userId, $item, $batch);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveContaDeletionDecision(int $userId, ImportacaoItem $item, ImportacaoLote $batch): array
    {
        $lancamentoId = $this->toPositiveInt($item->lancamento_id ?? null);
        if ($lancamentoId === null) {
            return ['action' => 'delete_tracking'];
        }

        $lancamento = Lancamento::withTrashed()
            ->where('id', $lancamentoId)
            ->where('user_id', $userId)
            ->first();

        if (!$lancamento || $lancamento->trashed()) {
            return ['action' => 'delete_tracking'];
        }

        $reason = $this->detectContaRetentionReason($lancamento, $item, $batch);
        if ($reason !== null) {
            return [
                'action' => 'retain',
                'reason' => $reason,
            ];
        }

        $lancamento->delete();

        return ['action' => 'delete_tracking'];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function resolveCartaoDeletionDecision(int $userId, ImportacaoItem $item, array $raw, ImportacaoLote $batch): array
    {
        $faturaItemId = $this->toPositiveInt($raw['fatura_item_id'] ?? null);
        $faturaId = $this->toPositiveInt($raw['fatura_id'] ?? null);
        $cartaoId = $this->toPositiveInt($raw['cartao_id'] ?? null);

        if ($faturaItemId === null) {
            return [
                'action' => 'delete_tracking',
                'fatura_id' => $faturaId,
                'cartao_id' => $cartaoId,
            ];
        }

        $faturaItem = FaturaCartaoItem::query()
            ->where('id', $faturaItemId)
            ->where('user_id', $userId)
            ->first();

        if (!$faturaItem) {
            return [
                'action' => 'delete_tracking',
                'fatura_id' => $faturaId,
                'cartao_id' => $cartaoId,
            ];
        }

        $resolvedCartaoId = $this->toPositiveInt($faturaItem->cartao_credito_id ?? null) ?? $cartaoId;
        if ($resolvedCartaoId === null) {
            return [
                'action' => 'retain',
                'reason' => 'O cartão relacionado não está mais disponível para validar a reversão.',
            ];
        }

        $cartao = CartaoCredito::query()
            ->where('id', $resolvedCartaoId)
            ->where('user_id', $userId)
            ->first();

        if (!$cartao) {
            return [
                'action' => 'retain',
                'reason' => 'O cartão relacionado não está mais disponível para validar a reversão.',
            ];
        }

        $reason = $this->detectCartaoRetentionReason($faturaItem, $item, $raw, $cartao, $batch);
        if ($reason !== null) {
            return [
                'action' => 'retain',
                'reason' => $reason,
            ];
        }

        $resolvedFaturaId = $this->toPositiveInt($faturaItem->fatura_id ?? null) ?? $faturaId;
        $faturaItem->delete();

        return [
            'action' => 'delete_tracking',
            'fatura_id' => $resolvedFaturaId,
            'cartao_id' => (int) $cartao->id,
        ];
    }

    private function detectContaRetentionReason(Lancamento $lancamento, ImportacaoItem $item, ImportacaoLote $batch): ?string
    {
        if ((int) ($lancamento->conta_id ?? 0) !== (int) ($item->conta_id ?? 0)) {
            return 'O lançamento foi movido para outra conta.';
        }

        if ((string) ($lancamento->tipo ?? '') !== (string) ($item->tipo ?? '')) {
            return 'O tipo do lançamento foi alterado manualmente.';
        }

        if ($this->normalizeDate($lancamento->data ?? null) !== $this->normalizeDate($item->data ?? null)) {
            return 'A data do lançamento foi alterada manualmente.';
        }

        if (!$this->sameMoney($lancamento->valor ?? null, $item->amount ?? null)) {
            return 'O valor do lançamento foi alterado manualmente.';
        }

        if (trim((string) ($lancamento->descricao ?? '')) !== trim((string) ($item->description ?? ''))) {
            return 'A descrição do lançamento foi alterada manualmente.';
        }

        $expectedObservacao = $this->buildContaObservacao($item, $batch);
        if (trim((string) ($lancamento->observacao ?? '')) !== $expectedObservacao) {
            return 'A observação do lançamento foi alterada manualmente.';
        }

        if (!(bool) ($lancamento->pago ?? false)) {
            return 'O status de pagamento do lançamento foi alterado manualmente.';
        }

        if ($this->normalizeDate($lancamento->data_pagamento ?? null) !== $this->normalizeDate($item->data ?? null)) {
            return 'A data de pagamento do lançamento foi alterada manualmente.';
        }

        if (!(bool) ($lancamento->afeta_caixa ?? false)) {
            return 'O lançamento deixou de afetar caixa após a importação.';
        }

        if ($this->toPositiveInt($lancamento->cartao_credito_id ?? null) !== null) {
            return 'O lançamento foi vinculado a um cartão após a importação.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function detectCartaoRetentionReason(
        FaturaCartaoItem $faturaItem,
        ImportacaoItem $item,
        array $raw,
        CartaoCredito $cartao,
        ImportacaoLote $batch
    ): ?string {
        $tipoImportado = strtolower(trim((string) ($item->tipo ?? 'despesa')));
        $expectedTipo = $tipoImportado === 'receita' ? 'estorno' : 'despesa';
        $expectedDescricao = $tipoImportado === 'receita'
            ? mb_substr('Estorno - ' . trim((string) ($item->description ?? '')), 0, 190)
            : trim((string) ($item->description ?? ''));
        $expectedValor = $tipoImportado === 'receita'
            ? -abs((float) ($item->amount ?? 0))
            : abs((float) ($item->amount ?? 0));
        $expectedDataCompra = $this->normalizeDate($item->data ?? null);
        $expectedVencimento = $expectedDataCompra !== null
            ? $this->billingDateService->calcularDataVencimento(
                $expectedDataCompra,
                max(1, (int) ($cartao->dia_vencimento ?? 0)),
                (int) ($cartao->dia_fechamento ?? 0) > 0 ? (int) $cartao->dia_fechamento : null
            )
            : null;
        $expectedCompetencia = $expectedDataCompra !== null
            ? $this->billingDateService->calcularCompetencia(
                $expectedDataCompra,
                (int) ($cartao->dia_fechamento ?? 0) > 0 ? (int) $cartao->dia_fechamento : null
            )
            : null;
        $expectedPago = $tipoImportado === 'receita';
        $expectedDataPagamento = $expectedPago ? $expectedDataCompra : null;
        $expectedFaturaId = $this->toPositiveInt($raw['fatura_id'] ?? null);
        $expectedCartaoId = $this->toPositiveInt($raw['cartao_id'] ?? null) ?? (int) $cartao->id;

        if ((int) ($faturaItem->cartao_credito_id ?? 0) !== $expectedCartaoId) {
            return 'O item da fatura foi movido para outro cartão.';
        }

        if ($expectedFaturaId !== null && (int) ($faturaItem->fatura_id ?? 0) !== $expectedFaturaId) {
            return 'O item da fatura foi movido para outra fatura.';
        }

        if ((string) ($faturaItem->tipo ?? '') !== $expectedTipo) {
            return 'O tipo do item da fatura foi alterado manualmente.';
        }

        if (trim((string) ($faturaItem->descricao ?? '')) !== $expectedDescricao) {
            return 'A descrição do item da fatura foi alterada manualmente.';
        }

        if (!$this->sameMoney($faturaItem->valor ?? null, $expectedValor)) {
            return 'O valor do item da fatura foi alterado manualmente.';
        }

        if ($this->normalizeDate($faturaItem->data_compra ?? null) !== $expectedDataCompra) {
            return 'A data de compra do item da fatura foi alterada manualmente.';
        }

        if ($expectedVencimento !== null && $this->normalizeDate($faturaItem->data_vencimento ?? null) !== ($expectedVencimento['data'] ?? null)) {
            return 'A data de vencimento do item da fatura foi alterada manualmente.';
        }

        if ($expectedCompetencia !== null && (int) ($faturaItem->mes_referencia ?? 0) !== (int) ($expectedCompetencia['mes'] ?? 0)) {
            return 'A competência do item da fatura foi alterada manualmente.';
        }

        if ($expectedCompetencia !== null && (int) ($faturaItem->ano_referencia ?? 0) !== (int) ($expectedCompetencia['ano'] ?? 0)) {
            return 'A competência do item da fatura foi alterada manualmente.';
        }

        if ((bool) ($faturaItem->eh_parcelado ?? false)) {
            return 'O item da fatura foi parcelado após a importação.';
        }

        if ((int) ($faturaItem->parcela_atual ?? 1) !== 1 || (int) ($faturaItem->total_parcelas ?? 1) !== 1) {
            return 'O item da fatura foi alterado em um parcelamento após a importação.';
        }

        if ($this->toPositiveInt($faturaItem->lancamento_id ?? null) !== null) {
            return 'O item da fatura já gerou um lançamento e foi preservado.';
        }

        if ((bool) ($faturaItem->pago ?? false) !== $expectedPago) {
            return 'O status de pagamento do item da fatura foi alterado após a importação.';
        }

        if ($this->normalizeDate($faturaItem->data_pagamento ?? null) !== $expectedDataPagamento) {
            return 'A data de pagamento do item da fatura foi alterada após a importação.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRetainedItem(ImportacaoLote $batch, ImportacaoItem $item, string $reason): array
    {
        $raw = $this->decodeJson((string) ($item->raw_json ?? ''));
        $batchMeta = $this->decodeJson((string) ($batch->meta_json ?? ''));

        return [
            'item_id' => (int) $item->id,
            'batch_id' => (int) $batch->id,
            'target' => $this->normalizeImportTarget((string) ($raw['import_target'] ?? ($batchMeta['import_target'] ?? 'conta'))),
            'description' => (string) ($item->description ?? ''),
            'date' => $this->normalizeDate($item->data ?? null),
            'amount' => round((float) ($item->amount ?? 0), 2),
            'reason' => trim($reason) !== '' ? trim($reason) : 'Registro preservado.',
        ];
    }

    /**
     * @param array<int, int> $faturaIds
     * @param array<int, int> $cartaoIds
     */
    private function refreshFaturasAndCartoes(int $userId, array $faturaIds, array $cartaoIds): void
    {
        foreach (array_values(array_unique(array_filter($faturaIds))) as $faturaId) {
            $fatura = Fatura::query()
                ->where('id', (int) $faturaId)
                ->where('user_id', $userId)
                ->first();

            if (!$fatura) {
                continue;
            }

            $itensRestantes = FaturaCartaoItem::query()->where('fatura_id', (int) $faturaId)->count();
            if ($itensRestantes === 0) {
                $fatura->delete();
                continue;
            }

            $fatura->valor_total = number_format(
                (float) FaturaCartaoItem::query()->where('fatura_id', (int) $faturaId)->sum('valor'),
                2,
                '.',
                ''
            );
            $fatura->save();
            $fatura->atualizarStatus();
        }

        foreach (array_values(array_unique(array_filter($cartaoIds))) as $cartaoId) {
            $cartao = CartaoCredito::query()
                ->where('id', (int) $cartaoId)
                ->where('user_id', $userId)
                ->first();

            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
            }
        }
    }

    private function buildContaObservacao(ImportacaoItem $item, ImportacaoLote $batch): string
    {
        $parts = [
            'Importado via ' . strtoupper(trim((string) ($batch->source_type ?? 'OFX')) !== '' ? (string) $batch->source_type : 'OFX'),
        ];

        $memo = trim((string) ($item->memo ?? ''));
        if ($memo !== '') {
            $parts[] = $memo;
        }

        return mb_substr(implode(' | ', $parts), 0, 500);
    }

    private function resolveBatchStatus(int $imported, int $duplicated, int $errors): string
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

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function encodeJson(array $value): ?string
    {
        if ($value === []) {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        return is_string($encoded) ? $encoded : null;
    }

    private function normalizeImportTarget(string $value): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? substr($normalized, 0, 10) : null;
    }

    private function sameMoney(mixed $left, mixed $right): bool
    {
        return round((float) ($left ?? 0), 2) === round((float) ($right ?? 0), 2);
    }

    private function toPositiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}