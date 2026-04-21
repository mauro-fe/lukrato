<?php

declare(strict_types=1);

$historyItems = is_array($historyItems ?? null) ? $historyItems : [];
$accounts = is_array($accounts ?? null) ? $accounts : [];
$selectedAccountId = (int) ($selectedAccountId ?? 0);
$selectedSourceType = strtolower(trim((string) ($selectedSourceType ?? '')));
$selectedStatus = strtolower(trim((string) ($selectedStatus ?? '')));
$selectedImportTarget = strtolower(trim((string) ($selectedImportTarget ?? '')));
$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
$totals = is_array($totals ?? null) ? $totals : [];

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

$totalBatches = (int) ($totals['batches'] ?? count($historyItems));
$totalRows = (int) ($totals['totalRows'] ?? 0);
$totalImported = (int) ($totals['importedRows'] ?? 0);
$totalDuplicates = (int) ($totals['duplicateRows'] ?? 0);
$totalErrors = (int) ($totals['errorRows'] ?? 0);

$selectedAccountName = 'Todas as contas';
foreach ($accounts as $account) {
    if ((int) ($account['id'] ?? 0) === $selectedAccountId) {
        $selectedAccountName = (string) ($account['nome'] ?? 'Conta sem nome');
        break;
    }
}

$selectedTargetLabel = match ($selectedImportTarget) {
    'conta' => 'Conta',
    'cartao' => 'Cartão/fatura',
    default => 'Todos os alvos',
};
$selectedSourceLabel = match ($selectedSourceType) {
    'ofx' => 'OFX',
    'csv' => 'CSV',
    default => 'OFX e CSV',
};
$selectedStatusLabel = $selectedStatus !== ''
    ? (string) ($statusLabels[$selectedStatus] ?? $selectedStatus)
    : 'Todos os status';

$activeScopeParts = [];
if ($selectedAccountId > 0) {
    $activeScopeParts[] = $selectedAccountName;
}
if ($selectedImportTarget !== '') {
    $activeScopeParts[] = $selectedTargetLabel;
}
if ($selectedSourceType !== '') {
    $activeScopeParts[] = $selectedSourceLabel;
}
if ($selectedStatus !== '') {
    $activeScopeParts[] = $selectedStatusLabel;
}

$activeScopeTitle = $activeScopeParts !== []
    ? implode(' · ', $activeScopeParts)
    : 'Todos os lotes confirmados';
$activeScopeCopy = $activeScopeParts !== []
    ? 'O histórico abaixo já está recortado pelos filtros escolhidos.'
    : 'Veja a base confirmada completa antes de refinar por conta, formato ou status.';
?>

<section class="imp-history-page" data-importacoes-page="historico" data-lk-help-page="importacoes_historico">
    <header class="imp-page-hero imp-page-hero--compact imp-surface surface-card surface-card--interactive surface-card--clip">
        <div class="imp-page-hero__content">
            <p class="imp-page-hero__eyebrow">Importações</p>
            <h1 class="imp-page-hero__title">Histórico de lotes</h1>
            <p class="imp-page-hero__lead">
                Consulte os lotes confirmados e acompanhe importadas, duplicadas e erros por conta ou cartão.
            </p>
            <div class="imp-page-hero__meta-inline imp-page-hero__meta-inline--chips">
                <span class="imp-chip">Lotes confirmados</span>
                <span class="imp-chip">Filtros vivos</span>
                <span class="imp-chip">Exclusão segura</span>
            </div>
        </div>

        <aside class="imp-page-hero__aside imp-page-hero__aside--compact">
            <div class="imp-hero-snapshot imp-history-hero-snapshot surface-card">
                <span class="imp-hero-snapshot__eyebrow">Painel rápido</span>
                <div class="imp-hero-snapshot__grid">
                    <div class="imp-hero-snapshot__item">
                        <span>Lotes</span>
                        <strong data-imp-history-total-batches><?= $totalBatches ?></strong>
                    </div>
                    <div class="imp-hero-snapshot__item">
                        <span>Linhas</span>
                        <strong data-imp-history-total-rows><?= $totalRows ?></strong>
                    </div>
                    <div class="imp-hero-snapshot__item">
                        <span>Importadas</span>
                        <strong data-imp-history-total-imported><?= $totalImported ?></strong>
                    </div>
                    <div class="imp-hero-snapshot__item">
                        <span>Erros</span>
                        <strong data-imp-history-total-errors><?= $totalErrors ?></strong>
                    </div>
                </div>
                <div class="imp-page-hero__actions">
                    <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes">Voltar para importações</a>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>importacoes/configuracoes">Configurar contas</a>
                </div>
            </div>
        </aside>
    </header>

    <div class="imp-history-layout">
        <div class="imp-history-main">
            <article class="imp-history-card imp-history-card--filters imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Busca rápida</p>
                        <h3 class="imp-card-title">Recorte do histórico</h3>
                        <p class="imp-card-text">Filtre os lotes por alvo, conta, formato e status sem recarregar a página.</p>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Filtros vivos</span>
                </header>

                <div class="imp-history-filter-layout">
                    <article class="imp-history-filter-note surface-card">
                        <span class="imp-history-filter-note__eyebrow">Visão atual</span>
                        <strong class="imp-history-filter-note__title" data-imp-history-current-scope><?= escape($activeScopeTitle) ?></strong>
                        <p class="imp-history-filter-note__copy" data-imp-history-current-scope-copy>
                            <?= escape($activeScopeCopy) ?>
                        </p>

                        <dl class="imp-history-filter-summary">
                            <div>
                                <dt>Conta</dt>
                                <dd data-imp-history-filter-account-label><?= escape($selectedAccountName) ?></dd>
                            </div>
                            <div>
                                <dt>Alvo</dt>
                                <dd data-imp-history-filter-target-label><?= escape($selectedTargetLabel) ?></dd>
                            </div>
                            <div>
                                <dt>Formato</dt>
                                <dd data-imp-history-filter-source-label><?= escape($selectedSourceLabel) ?></dd>
                            </div>
                            <div>
                                <dt>Status</dt>
                                <dd data-imp-history-filter-status-label><?= escape($selectedStatusLabel) ?></dd>
                            </div>
                        </dl>
                    </article>

                    <form class="imp-history-filters" data-imp-history-filters method="get" action="<?= BASE_URL ?>importacoes/historico">
                        <div class="imp-history-filters__grid">
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-filter-target">Alvo</label>
                                <select id="imp-filter-target" class="imp-field__control" name="import_target"
                                    data-imp-history-filter-target>
                                    <option value="">Todos</option>
                                    <option value="conta" <?= $selectedImportTarget === 'conta' ? 'selected' : '' ?>>Conta</option>
                                    <option value="cartao" <?= $selectedImportTarget === 'cartao' ? 'selected' : '' ?>>Cartão/fatura</option>
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
                                        <option value="<?= escape($statusValue) ?>" <?= $selectedStatus === $statusValue ? 'selected' : '' ?>>
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
                </div>
            </article>

            <article class="imp-history-card imp-history-card--results imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Lotes confirmados</p>
                        <h3 class="imp-card-title">Resultados do histórico</h3>
                        <p class="imp-card-text">Cada lote mostra o resultado final da confirmação da importação e o que ainda pode ser limpo com segurança.</p>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready"><span data-imp-history-total-batches><?= $totalBatches ?></span> lotes</span>
                </header>

                <div class="imp-empty-state imp-empty-state--history surface-card" data-imp-history-empty
                    <?= $historyItems === [] ? '' : 'hidden' ?>>
                    <h4 class="imp-card-title">Nenhum lote registrado</h4>
                    <p class="imp-card-text">
                        Nenhum lote encontrado para os filtros atuais. Confirme uma importação para gerar histórico.
                    </p>
                    <div class="imp-empty-state__actions">
                        <a class="btn btn-primary" href="<?= BASE_URL ?>importacoes">Iniciar nova importação</a>
                        <a class="btn btn-secondary" href="<?= BASE_URL ?>importacoes/configuracoes">Revisar configurações</a>
                    </div>
                </div>

                <div class="imp-history-table-wrap" data-imp-history-table-wrap <?= $historyItems === [] ? 'hidden' : '' ?>>
                    <table class="imp-history-table" data-imp-history-table>
                        <thead>
                            <tr>
                                <th>Lote</th>
                                <th>Contexto</th>
                                <th>Arquivo</th>
                                <th>Resultado</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody data-imp-history-rows>
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
                                <?php $createdAt = (string) ($item['created_at'] ?? ''); ?>
                                <?php $sourceTypeLabel = strtoupper((string) ($item['source_type'] ?? '')); ?>
                                <tr data-imp-history-row data-batch-id="<?= $batchId ?>"
                                    data-total-rows="<?= (int) ($item['total_rows'] ?? 0) ?>"
                                    data-imported-rows="<?= (int) ($item['imported_rows'] ?? 0) ?>"
                                    data-duplicate-rows="<?= (int) ($item['duplicate_rows'] ?? 0) ?>"
                                    data-error-rows="<?= (int) ($item['error_rows'] ?? 0) ?>">
                                    <td class="imp-history-table__batch" data-label="Lote">
                                        <div class="imp-history-batch">
                                            <strong class="imp-history-batch__id">#<?= escape((string) ($item['batch_id'] ?? '-')) ?></strong>
                                            <span class="imp-history-batch__date"><?= escape($createdAt) ?></span>
                                        </div>
                                    </td>
                                    <td class="imp-history-table__context" data-label="Contexto">
                                        <div class="imp-history-context">
                                            <span class="imp-history-context__target"><?= escape($target === 'cartao' ? 'Cartão/fatura' : 'Conta') ?></span>
                                            <strong class="imp-history-context__name"><?= escape($contextLabel) ?></strong>
                                        </div>
                                    </td>
                                    <td class="imp-history-table__file" data-label="Arquivo">
                                        <div class="imp-history-file">
                                            <div class="imp-history-file__name"><?= escape((string) ($item['filename'] ?? '')) ?></div>
                                            <p class="imp-history-file__meta"><?= escape(trim($sourceTypeLabel . ' · ' . ($target === 'cartao' ? 'Cartão/fatura' : 'Conta'))) ?></p>
                                            <p class="imp-history-table__summary" data-imp-history-retention-summary
                                                <?= $partialDeleteSummary === '' ? 'hidden' : '' ?>>
                                                <?= escape($partialDeleteSummary) ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="imp-history-table__outcome" data-label="Resultado">
                                        <dl class="imp-history-outcome">
                                            <div>
                                                <dt>Linhas</dt>
                                                <dd data-imp-history-col="total_rows"><?= (int) ($item['total_rows'] ?? 0) ?></dd>
                                            </div>
                                            <div>
                                                <dt>Importadas</dt>
                                                <dd data-imp-history-col="imported_rows"><?= (int) ($item['imported_rows'] ?? 0) ?></dd>
                                            </div>
                                            <div>
                                                <dt>Duplicadas</dt>
                                                <dd data-imp-history-col="duplicate_rows"><?= (int) ($item['duplicate_rows'] ?? 0) ?></dd>
                                            </div>
                                            <div>
                                                <dt>Erros</dt>
                                                <dd data-imp-history-col="error_rows"><?= (int) ($item['error_rows'] ?? 0) ?></dd>
                                            </div>
                                        </dl>
                                    </td>
                                    <td class="imp-history-table__status-cell" data-label="Status">
                                        <div class="imp-history-status">
                                            <span class="imp-status-badge" data-status="<?= escape($status) ?>" data-imp-history-status-badge>
                                                <?= escape($statusLabels[$status] ?? $status) ?>
                                            </span>
                                            <p class="imp-history-status__hint" data-imp-history-status-hint>
                                                <?= $canDelete ? 'Pronto para consulta ou limpeza segura.' : 'Aguarde o processamento terminar para excluir.' ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="imp-history-table__actions" data-label="Ações">
                                        <button type="button" class="btn btn-ghost imp-history-table__delete" data-imp-history-delete <?= $canDelete ? '' : 'disabled' ?>>
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
            </article>
        </div>

        <aside class="imp-history-side">
            <article class="imp-history-card imp-history-side-card imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Resumo do recorte</p>
                        <h3 class="imp-card-title">Totais ativos</h3>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Resumo</span>
                </header>

                <dl class="imp-definition-list imp-definition-list--stack imp-history-side-summary">
                    <div class="surface-card">
                        <dt>Lotes</dt>
                        <dd data-imp-history-total-batches><?= $totalBatches ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Linhas</dt>
                        <dd data-imp-history-total-rows><?= $totalRows ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Importadas</dt>
                        <dd data-imp-history-total-imported><?= $totalImported ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Duplicadas</dt>
                        <dd data-imp-history-total-duplicates><?= $totalDuplicates ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Erros</dt>
                        <dd data-imp-history-total-errors><?= $totalErrors ?></dd>
                    </div>
                    <div class="surface-card imp-history-side-summary__item--full">
                        <dt>Conta</dt>
                        <dd data-imp-history-filter-account-label><?= escape($selectedAccountName) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Formato</dt>
                        <dd data-imp-history-filter-source-label><?= escape($selectedSourceLabel) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Status</dt>
                        <dd data-imp-history-filter-status-label><?= escape($selectedStatusLabel) ?></dd>
                    </div>
                    <div class="surface-card imp-history-side-summary__item--full">
                        <dt>Alvo</dt>
                        <dd data-imp-history-filter-target-label><?= escape($selectedTargetLabel) ?></dd>
                    </div>
                </dl>
            </article>

            <article class="imp-history-card imp-history-side-card imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Conexão</p>
                        <h3 class="imp-card-title">O que fazer depois</h3>
                        <p class="imp-card-text">Use o histórico para auditar o lote atual e decidir se ajusta o perfil antes de um novo envio.</p>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Fluxo</span>
                </header>

                <ul class="imp-history-flow-list">
                    <li>
                        <strong>Leitura do lote</strong>
                        <span>Cada confirmação vira um lote vinculado à conta ou cartão usado na importação.</span>
                    </li>
                    <li>
                        <strong>Exclusão segura</strong>
                        <span>Ao excluir, o sistema remove o lote e preserva lançamentos que já foram alterados manualmente.</span>
                    </li>
                    <li>
                        <strong>Reprocessamento</strong>
                        <span>Se o lote não ficou bom, ajuste o perfil da conta e volte ao fluxo principal para gerar outro.</span>
                    </li>
                </ul>

                <div class="imp-history-side__actions">
                    <a class="btn btn-primary" href="<?= BASE_URL ?>importacoes">Abrir fluxo de importação</a>
                    <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes/configuracoes">Ajustar perfis</a>
                </div>
            </article>
        </aside>
    </div>
</section>