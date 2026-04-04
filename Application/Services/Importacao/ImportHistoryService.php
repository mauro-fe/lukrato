<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Models\ImportacaoLote;

class ImportHistoryService
{
    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId, array $filters = [], int $limit = 100): array
    {
        $query = $this->baseQuery($userId);

        $contaId = (int) ($filters['conta_id'] ?? 0);
        if ($contaId > 0) {
            $query->where('conta_id', $contaId);
        }

        $sourceType = strtolower(trim((string) ($filters['source_type'] ?? '')));
        if (in_array($sourceType, ['ofx', 'csv'], true)) {
            $query->where('source_type', $sourceType);
        }

        $status = strtolower(trim((string) ($filters['status'] ?? '')));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $importTargetFilter = strtolower(trim((string) ($filters['import_target'] ?? '')));
        $rows = $query->limit(max(1, min($limit, 200)))->get();

        $items = [];
        foreach ($rows as $row) {
            $item = $this->formatBatch($row);
            $importTarget = strtolower((string) ($item['import_target'] ?? 'conta'));
            if ($importTargetFilter !== '' && $importTargetFilter !== $importTarget) {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $userId, int $batchId): ?array
    {
        $row = $this->baseQuery($userId)
            ->where('id', $batchId)
            ->first();

        return $row ? $this->formatBatch($row) : null;
    }

    private function baseQuery(int $userId)
    {
        return ImportacaoLote::query()
            ->with('conta:id,nome,instituicao')
            ->where('user_id', $userId)
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBatch(ImportacaoLote $row): array
    {
        $meta = $this->decodeMeta((string) ($row->meta_json ?? ''));
        $importTarget = $this->normalizeImportTarget((string) ($meta['import_target'] ?? 'conta'));
        $cartaoId = is_numeric($meta['cartao_id'] ?? null) ? (int) $meta['cartao_id'] : null;
        $cartaoNome = trim((string) ($meta['cartao_nome'] ?? ''));
        $deletion = is_array($meta['deletion'] ?? null) ? $meta['deletion'] : [];
        $retainedCount = is_numeric($deletion['retained_count'] ?? null) ? (int) $deletion['retained_count'] : 0;
        $partialDeleteSummary = trim((string) ($deletion['summary'] ?? ''));

        if ($partialDeleteSummary === '' && $retainedCount > 0) {
            $partialDeleteSummary = sprintf('%d registro(s) preservado(s) por alteracao manual.', $retainedCount);
        }

        $lastDeleteAttemptAt = trim((string) ($deletion['last_attempt_at'] ?? ''));

        return [
            'batch_id' => (int) $row->id,
            'import_target' => $importTarget,
            'conta_id' => (int) $row->conta_id,
            'conta_nome' => (string) ($row->conta->nome ?? $row->conta->instituicao ?? 'Conta'),
            'cartao_id' => $cartaoId,
            'cartao_nome' => $cartaoNome !== '' ? $cartaoNome : null,
            'filename' => (string) ($row->filename ?? ''),
            'source_type' => strtoupper((string) $row->source_type),
            'status' => (string) $row->status,
            'total_rows' => (int) $row->total_rows,
            'imported_rows' => (int) $row->imported_rows,
            'duplicate_rows' => (int) $row->duplicate_rows,
            'error_rows' => (int) $row->error_rows,
            'created_at' => (string) $row->created_at,
            'can_delete' => strtolower((string) ($row->status ?? '')) !== 'processing',
            'retained_count' => $retainedCount,
            'partial_delete_summary' => $retainedCount > 0 ? $partialDeleteSummary : null,
            'last_delete_attempt_at' => $lastDeleteAttemptAt !== '' ? $lastDeleteAttemptAt : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMeta(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }
}
