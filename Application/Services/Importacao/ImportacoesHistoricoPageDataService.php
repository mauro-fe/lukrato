<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\Repositories\ContaRepository;

class ImportacoesHistoricoPageDataService
{
    /** @var list<string> */
    private const ALLOWED_STATUSES = [
        'processing',
        'processed',
        'processed_with_duplicates',
        'processed_duplicates_only',
        'processed_with_errors',
        'failed',
    ];

    private readonly ContaRepository $contaRepository;
    private readonly ImportHistoryService $historyService;

    public function __construct(
        ?ContaRepository $contaRepository = null,
        ?ImportHistoryService $historyService = null,
    ) {
        $this->contaRepository = \Application\Container\ApplicationContainer::resolveOrNew($contaRepository, ContaRepository::class);
        $this->historyService = \Application\Container\ApplicationContainer::resolveOrNew($historyService, ImportHistoryService::class);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function buildForUser(int $userId, array $query = []): array
    {
        $accounts = $this->loadActiveAccounts($userId);
        $accountIds = array_column($accounts, 'id');

        $selectedAccountId = (int) ($query['conta_id'] ?? 0);
        if ($selectedAccountId > 0 && !in_array($selectedAccountId, $accountIds, true)) {
            $selectedAccountId = 0;
        }

        $selectedSourceType = $this->normalizeSourceType((string) ($query['source_type'] ?? ''));
        $selectedImportTarget = $this->normalizeImportTarget((string) ($query['import_target'] ?? ''));
        $selectedStatus = $this->normalizeStatus((string) ($query['status'] ?? ''));

        $historyItems = $this->historyService->listForUser($userId, [
            'conta_id' => $selectedAccountId,
            'source_type' => $selectedSourceType,
            'status' => $selectedStatus,
            'import_target' => $selectedImportTarget,
        ], 120);

        return [
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'selectedSourceType' => $selectedSourceType,
            'selectedImportTarget' => $selectedImportTarget,
            'selectedStatus' => $selectedStatus,
            'statusOptions' => self::ALLOWED_STATUSES,
            'historyItems' => $historyItems,
            'totals' => $this->buildTotals($historyItems),
        ];
    }

    /**
     * @return array<int, array{id:int, nome:string, instituicao:string}>
     */
    private function loadActiveAccounts(int $userId): array
    {
        $accounts = [];
        foreach ($this->contaRepository->findActive($userId) as $account) {
            $accounts[] = [
                'id' => (int) ($account->id ?? 0),
                'nome' => (string) ($account->nome ?? ''),
                'instituicao' => (string) ($account->instituicao ?? ''),
            ];
        }

        return $accounts;
    }

    private function normalizeSourceType(string $sourceType): string
    {
        $normalized = strtolower(trim($sourceType));

        return in_array($normalized, ['ofx', 'csv'], true) ? $normalized : '';
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : '';
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return in_array($normalized, self::ALLOWED_STATUSES, true) ? $normalized : '';
    }

    /**
     * @param array<int, array<string, mixed>> $historyItems
     * @return array<string, int>
     */
    private function buildTotals(array $historyItems): array
    {
        $totals = [
            'batches' => count($historyItems),
            'totalRows' => 0,
            'importedRows' => 0,
            'duplicateRows' => 0,
            'errorRows' => 0,
        ];

        foreach ($historyItems as $item) {
            $totals['totalRows'] += (int) ($item['total_rows'] ?? 0);
            $totals['importedRows'] += (int) ($item['imported_rows'] ?? 0);
            $totals['duplicateRows'] += (int) ($item['duplicate_rows'] ?? 0);
            $totals['errorRows'] += (int) ($item['error_rows'] ?? 0);
        }

        return $totals;
    }
}
