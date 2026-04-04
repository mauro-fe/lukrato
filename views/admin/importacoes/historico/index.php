<?php

declare(strict_types=1);

$historyItems = is_array($historyItems ?? null) ? $historyItems : [];
$accounts = is_array($accounts ?? null) ? $accounts : [];
$selectedAccountId = (int) ($selectedAccountId ?? 0);
$selectedSourceType = strtolower(trim((string) ($selectedSourceType ?? '')));
$selectedStatus = strtolower(trim((string) ($selectedStatus ?? '')));
$selectedImportTarget = strtolower(trim((string) ($selectedImportTarget ?? '')));
$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];

$statusLabels = [
    'processing' => 'Processando',
    'processed' => 'Processado',
    'processed_with_duplicates' => 'Processado com duplicados',
    'processed_duplicates_only' => 'Somente duplicados',
    'processed_with_errors' => 'Processado com erros',
    'failed' => 'Falhou',
];
$targetLabels = [
    'conta' => 'Conta',
    'cartao' => 'Cartão',
];

$totalBatches = count($historyItems);
$totalRows = 0;
$totalImported = 0;
$totalDuplicates = 0;
$totalErrors = 0;

foreach ($historyItems as $item) {
    $totalRows += (int) ($item['total_rows'] ?? 0);
    $totalImported += (int) ($item['imported_rows'] ?? 0);
    $totalDuplicates += (int) ($item['duplicate_rows'] ?? 0);
    $totalErrors += (int) ($item['error_rows'] ?? 0);
}
?>

<section class="imp-history-page" data-importacoes-page="historico">
    <header
        class="imp-page-hero imp-page-hero--compact imp-surface surface-card surface-card--interactive surface-card--clip">
        <div class="imp-page-hero__content">
            <p class="imp-page-hero__eyebrow">Importações</p>
            <h1 class="imp-page-hero__title">Histórico de lotes</h1>
            <p class="imp-page-hero__lead">
                Consulte os lotes confirmados e acompanhe importadas, duplicadas e erros por conta ou cartão.
            </p>
            <div class="imp-page-hero__meta-inline imp-page-hero__meta-inline--chips">
                <span class="imp-chip">Filtro por alvo</span>
                <span class="imp-chip">Filtro por conta</span>
                <span class="imp-chip">Status por lote</span>
            </div>
        </div>

        <aside class="imp-page-hero__aside imp-page-hero__aside--compact">
            <dl class="imp-hero-definition">
                <div class="surface-card">
                    <dt>Lotes</dt>
                    <dd data-imp-history-total-batches><?= $totalBatches ?></dd>
                </div>
                <div class="surface-card">
                    <dt>Importadas</dt>
                    <dd data-imp-history-total-imported><?= $totalImported ?></dd>
                </div>
                <div class="surface-card">
                    <dt>Duplicadas</dt>
                    <dd data-imp-history-total-duplicates><?= $totalDuplicates ?></dd>
                </div>
            </dl>
            <div class=" imp-page-hero__actions">
                <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes">Voltar para importações</a>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>importacoes/configuracoes">Configurar contas</a>
            </div>
        </aside>
    </header>

    <div class="imp-history-kpis">
        <article class="imp-kpi-card imp-surface surface-card">
            <p class="imp-kpi-card__label">Linhas analisadas</p>
            <p class="imp-kpi-card__value" data-imp-history-total-rows><?= $totalRows ?></p>
        </article>
        <article class="imp-kpi-card imp-surface surface-card">
            <p class="imp-kpi-card__label">Importadas</p>
            <p class="imp-kpi-card__value" data-imp-history-total-imported><?= $totalImported ?></p>
        </article>
        <article class="imp-kpi-card imp-surface surface-card">
            <p class="imp-kpi-card__label">Duplicadas</p>
            <p class="imp-kpi-card__value" data-imp-history-total-duplicates><?= $totalDuplicates ?></p>
        </article>
        <article class="imp-kpi-card imp-surface surface-card">
            <p class="imp-kpi-card__label">Erros</p>
            <p class="imp-kpi-card__value" data-imp-history-total-errors><?= $totalErrors ?></p>
        </article>
    </div>

    <form class="imp-history-filters imp-surface surface-card surface-card--interactive" data-imp-history-filters
        method="get" action="<?= BASE_URL ?>importacoes/historico">
        <header class="imp-card-head">
            <p class="imp-card-eyebrow">Busca rápida</p>
            <h3 class="imp-card-title">Filtros do histórico</h3>
            <p class="imp-card-text">Filtre os lotes por alvo, conta, formato e status.</p>
        </header>

        <div class="imp-history-filters__grid">
            <div class="imp-field">
                <label class="imp-field__label" for="imp-filter-target">Alvo</label>
                <select id="imp-filter-target" class="imp-field__control" name="import_target"
                    data-imp-history-filter-target>
                    <option value="">Todos</option>
                    <option value="conta" <?= $selectedImportTarget === 'conta' ? 'selected' : '' ?>>Conta</option>
                    <option value="cartao" <?= $selectedImportTarget === 'cartao' ? 'selected' : '' ?>>Cartão/fatura
                    </option>
                </select>
            </div>
            <div class="imp-field">
                <label class="imp-field__label" for="imp-filter-account">Conta</label>
                <select id="imp-filter-account" class="imp-field__control" name="conta_id"
                    data-imp-history-filter-account>
                    <option value="0">Todas as contas</option>
                    <?php foreach ($accounts as $account) : ?>
                        <?php $accountId = (int) ($account['id'] ?? 0); ?>
                        <option value="<?= $accountId ?>" <?= $accountId === $selectedAccountId ? 'selected' : '' ?>>
                            <?= escape((string) ($account['nome'] ?? 'Conta sem nome')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="imp-field">
                <label class="imp-field__label" for="imp-filter-source">Formato</label>
                <select id="imp-filter-source" class="imp-field__control" name="source_type"
                    data-imp-history-filter-source>
                    <option value="">Todos</option>
                    <option value="ofx" <?= $selectedSourceType === 'ofx' ? 'selected' : '' ?>>OFX</option>
                    <option value="csv" <?= $selectedSourceType === 'csv' ? 'selected' : '' ?>>CSV</option>
                </select>
            </div>
            <div class="imp-field">
                <label class="imp-field__label" for="imp-filter-status">Status</label>
                <select id="imp-filter-status" class="imp-field__control" name="status" data-imp-history-filter-status>
                    <option value="">Todos</option>
                    <?php foreach ($statusOptions as $statusOption) : ?>
                        <?php $statusValue = strtolower(trim((string) $statusOption)); ?>
                        <?php if ($statusValue === '') continue; ?>
                        <option value="<?= escape($statusValue) ?>"
                            <?= $selectedStatus === $statusValue ? 'selected' : '' ?>>
                            <?= escape($statusLabels[$statusValue] ?? $statusValue) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="imp-history-filters__actions">
            <button type="submit" class="btn btn-secondary">Aplicar filtros</button>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes/historico">Limpar</a>
        </div>
    </form>

    <article class="imp-history-card imp-surface surface-card surface-card--interactive">
        <header class="imp-card-head">
            <h3 class="imp-card-title">Lotes processados</h3>
            <p class="imp-card-text">
                Cada lote mostra o resultado final da confirmação da importação.
            </p>
        </header>

        <?php if ($historyItems === []) : ?>
            <div class="imp-empty-state imp-empty-state--history surface-card">
                <h4 class="imp-card-title">Nenhum lote registrado</h4>
                <p class="imp-card-text">
                    Nenhum lote encontrado para os filtros atuais. Confirme uma importação para gerar histórico.
                </p>
                <div class="imp-empty-state__actions">
                    <a class="btn btn-primary" href="<?= BASE_URL ?>importacoes">Iniciar nova importação</a>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>importacoes/configuracoes">Revisar configurações</a>
                </div>
            </div>
        <?php else : ?>
            <div class="imp-history-table-wrap">
                <table class="imp-history-table" data-imp-history-table>
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Alvo</th>
                            <th>Contexto</th>
                            <th>Arquivo</th>
                            <th>Formato</th>
                            <th>Linhas</th>
                            <th>Importadas</th>
                            <th>Duplicadas</th>
                            <th>Erros</th>
                            <th>Status</th>
                            <th>Importado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyItems as $item) : ?>
                            <?php $status = strtolower((string) ($item['status'] ?? 'processed')); ?>
                            <?php $target = strtolower((string) ($item['import_target'] ?? 'conta')); ?>
                            <?php
                            $contextLabel = $target === 'cartao'
                                ? ((string) ($item['cartao_nome'] ?? '') !== ''
                                    ? (string) ($item['cartao_nome'] ?? '')
                                    : ('Cartão #' . (int) ($item['cartao_id'] ?? 0)))
                                : (string) ($item['conta_nome'] ?? '-');
                            ?>
                            <?php $batchId = (int) ($item['batch_id'] ?? 0); ?>
                            <?php $canDelete = (bool) ($item['can_delete'] ?? true); ?>
                            <?php $partialDeleteSummary = trim((string) ($item['partial_delete_summary'] ?? '')); ?>
                            <tr data-imp-history-row data-batch-id="<?= $batchId ?>"
                                data-total-rows="<?= (int) ($item['total_rows'] ?? 0) ?>"
                                data-imported-rows="<?= (int) ($item['imported_rows'] ?? 0) ?>"
                                data-duplicate-rows="<?= (int) ($item['duplicate_rows'] ?? 0) ?>"
                                data-error-rows="<?= (int) ($item['error_rows'] ?? 0) ?>">
                                <td class="imp-history-table__batch">#<?= escape((string) ($item['batch_id'] ?? '-')) ?></td>
                                <td><?= escape($targetLabels[$target] ?? 'Conta') ?></td>
                                <td><?= escape($contextLabel) ?></td>
                                <td class="imp-history-table__file">
                                    <div class="imp-history-table__file-name"><?= escape((string) ($item['filename'] ?? '')) ?>
                                    </div>
                                    <p class="imp-history-table__summary" data-imp-history-retention-summary
                                        <?= $partialDeleteSummary === '' ? 'hidden' : '' ?>>
                                        <?= escape($partialDeleteSummary) ?>
                                    </p>
                                </td>
                                <td class="imp-history-table__source">
                                    <?= strtoupper(escape((string) ($item['source_type'] ?? ''))) ?></td>
                                <td data-imp-history-col="total_rows"><?= (int) ($item['total_rows'] ?? 0) ?></td>
                                <td data-imp-history-col="imported_rows"><?= (int) ($item['imported_rows'] ?? 0) ?></td>
                                <td data-imp-history-col="duplicate_rows"><?= (int) ($item['duplicate_rows'] ?? 0) ?></td>
                                <td data-imp-history-col="error_rows"><?= (int) ($item['error_rows'] ?? 0) ?></td>
                                <td>
                                    <span class="imp-status-badge" data-status="<?= escape($status) ?>"
                                        data-imp-history-status-badge>
                                        <?= escape($statusLabels[$status] ?? $status) ?>
                                    </span>
                                </td>
                                <td class="imp-history-table__date"><?= escape((string) ($item['created_at'] ?? '')) ?></td>
                                <td class="imp-history-table__actions">
                                    <button type="button" class="btn btn-ghost imp-history-table__delete"
                                        data-imp-history-delete
                                        data-delete-url="<?= BASE_URL ?>api/importacoes/historico/<?= $batchId ?>"
                                        <?= $canDelete ? '' : 'disabled' ?>>
                                        Excluir importação
                                    </button>
                                    <p class="imp-history-table__action-hint" data-imp-history-action-hint>
                                        <?= $canDelete ? 'Remove o lote e os registros ainda intactos.' : 'Aguarde o processamento terminar para excluir.' ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>