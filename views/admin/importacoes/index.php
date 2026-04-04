<?php

$supportedFormats = is_array($supportedFormats ?? null) ? $supportedFormats : [];
$supportedFormats = $supportedFormats !== [] ? $supportedFormats : ['OFX', 'CSV'];
$accounts = is_array($accounts ?? null) ? $accounts : [];
$cards = is_array($cards ?? null) ? $cards : [];
$selectedAccountId = (int) ($selectedAccountId ?? 0);
$selectedCardId = (int) ($selectedCardId ?? 0);
$importTarget = strtolower(trim((string) ($importTarget ?? 'conta')));
if (!in_array($importTarget, ['conta', 'cartao'], true)) {
    $importTarget = 'conta';
}

$profileConfig = is_array($profileConfig ?? null) ? $profileConfig : null;
$planLimits = is_array($planLimits ?? null) ? $planLimits : [];
$importQuota = is_array($importQuota ?? null) ? $importQuota : [];
$importLimitBuckets = is_array($planLimits['importacoes'] ?? null) ? $planLimits['importacoes'] : [];
$importLimitsJson = json_encode($importLimitBuckets, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$importLimitsJson = is_string($importLimitsJson) ? $importLimitsJson : '{}';
$importLimitsEncoded = base64_encode($importLimitsJson);
$currentPlan = strtolower(trim((string) ($planLimits['plan'] ?? 'free')));
$upgradeUrl = trim((string) ($planLimits['upgrade_url'] ?? '/assinatura'));
$previewEndpoint = trim((string) ($previewEndpoint ?? ''));
$confirmEndpoint = trim((string) ($confirmEndpoint ?? ''));
$jobStatusEndpointBase = trim((string) ($jobStatusEndpointBase ?? ''));
$confirmAsyncDefault = (bool) ($confirmAsyncDefault ?? false);
$latestHistoryItems = is_array($latestHistoryItems ?? null) ? $latestHistoryItems : [];
$initialSourceType = strtolower(trim((string) ($profileConfig['source_type'] ?? 'ofx')));
if (!in_array($initialSourceType, ['ofx', 'csv'], true)) {
    $initialSourceType = 'ofx';
}
if ($importTarget === 'cartao') {
    $initialSourceType = 'ofx';
}

$activeAccount = null;
foreach ($accounts as $account) {
    if ((int) ($account['id'] ?? 0) === $selectedAccountId) {
        $activeAccount = $account;
        break;
    }
}

$activeCard = null;
foreach ($cards as $card) {
    if ((int) ($card['id'] ?? 0) === $selectedCardId) {
        $activeCard = $card;
        break;
    }
}

$configUrl = BASE_URL . 'importacoes/configuracoes';
if ($selectedAccountId > 0) {
    $configUrl .= '?conta_id=' . $selectedAccountId;
}

$heroBatchCount = count($latestHistoryItems);
$heroPendingCount = 0;
$heroProcessedCount = 0;
$historyStatusLabelMap = [
    'processing' => 'Processando',
    'processed' => 'Processado',
    'processed_with_duplicates' => 'Com duplicados',
    'processed_duplicates_only' => 'Somente duplicados',
    'processed_with_errors' => 'Com erros',
    'failed' => 'Falhou',
];
$historyTargetLabelMap = [
    'conta' => 'Conta',
    'cartao' => 'Cartão',
];
$importLimitLabelMap = [
    'import_conta_ofx' => 'OFX de conta',
    'import_conta_csv' => 'CSV de lançamentos',
    'import_cartao_ofx' => 'OFX de fatura/cartão',
];


foreach ($latestHistoryItems as $historyItem) {
    $status = strtolower(trim((string) ($historyItem['status'] ?? '')));
    if ($status === 'processing') {
        $heroPendingCount++;
    } elseif ($status !== '') {
        $heroProcessedCount++;
    }
}

$activeContextLabel = $importTarget === 'cartao'
    ? (string) ($activeCard['nome'] ?? 'Cartão não selecionado')
    : (string) ($activeAccount['nome'] ?? 'Conta não selecionada');
?>

<section class="imp-page" data-importacoes-page="index" data-imp-preview-endpoint="<?= escape($previewEndpoint) ?>"
    data-imp-confirm-endpoint="<?= escape($confirmEndpoint) ?>" data-imp-active-account-id="<?= $selectedAccountId ?>"
    data-imp-active-card-id="<?= $selectedCardId ?>" data-imp-import-target="<?= escape($importTarget) ?>"
    data-imp-plan="<?= escape($currentPlan) ?>" data-imp-upgrade-url="<?= escape($upgradeUrl) ?>"
    data-imp-import-limits="<?= escape($importLimitsEncoded) ?>"
    data-imp-job-status-endpoint-base="<?= escape($jobStatusEndpointBase) ?>"
    data-imp-categories-endpoint="<?= escape(BASE_URL . 'api/categorias') ?>"
    data-imp-subcategories-endpoint-base="<?= escape(BASE_URL . 'api/categorias') ?>"
    data-imp-confirm-async-default="<?= $confirmAsyncDefault ? '1' : '0' ?>">
    <header
        class="imp-page-hero imp-page-hero--compact imp-surface surface-card surface-card--interactive surface-card--clip">
        <div class="imp-page-hero__content">
            <p class="imp-page-hero__eyebrow">Importações</p>
            <h1 class="imp-page-hero__title">
                <?= $importTarget === 'cartao' ? 'Importar OFX de cartão/fatura' : 'Importar OFX e CSV de conta' ?>
            </h1>
            <p class="imp-page-hero__lead">
                Fluxo único de upload, preview e confirmação real no módulo de Importações.
            </p>
            <div class="imp-page-hero__meta-inline imp-page-hero__meta-inline--chips">
                <span class="imp-chip">Alvo: <?= escape($historyTargetLabelMap[$importTarget] ?? 'Conta') ?></span>
                <span class="imp-chip">Preview</span>
                <span class="imp-chip">Confirmação</span>
                <span class="imp-chip">Histórico</span>
            </div>
        </div>

        <aside class="imp-page-hero__aside imp-page-hero__aside--compact">
            <dl class="imp-hero-definition">
                <div class="surface-card">
                    <dt>Contexto ativo</dt>
                    <dd><?= escape($activeContextLabel) ?></dd>
                </div>
                <div class="surface-card">
                    <dt>Lotes no painel</dt>
                    <dd><?= $heroBatchCount ?></dd>
                </div>
                <div class="surface-card">
                    <dt>Pendentes</dt>
                    <dd><?= $heroPendingCount ?></dd>
                </div>
            </dl>
            <div class="imp-page-hero__actions">
                <a class="btn btn-secondary" href="<?= escape($configUrl) ?>">Configurar conta</a>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes/historico">Histórico</a>
            </div>
        </aside>
    </header>

    <div class="imp-index-layout">
        <div class="imp-index-main">
            <article class="imp-flow-card imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Preparar preview</p>
                        <h2 class="imp-card-title">Alvo, formato e arquivo</h2>
                        <p class="imp-card-text">
                            Selecione o alvo da importação, revise o arquivo e monte o preview antes da confirmação.
                            OFX bancário entra em Conta; OFX de fatura entra em Cartão/fatura.
                        </p>
                    </div>
                    <span class="imp-status-badge" data-status="file_selected">Etapa 1</span>
                </header>

                <form class="imp-flow-form" id="imp-upload-form" novalidate>
                    <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

                    <fieldset class="imp-format-switch" aria-label="Alvo da importação">
                        <legend class="imp-field__label">Alvo</legend>
                        <label class="imp-format-switch__item" for="imp-target-conta">
                            <input id="imp-target-conta" type="radio" name="import_target" value="conta"
                                data-imp-target-type <?= $importTarget === 'conta' ? 'checked' : '' ?>>
                            <span>
                                <i data-lucide="wallet" aria-hidden="true"></i>
                                Conta
                            </span>
                        </label>
                        <label class="imp-format-switch__item" for="imp-target-cartao">
                            <input id="imp-target-cartao" type="radio" name="import_target" value="cartao"
                                data-imp-target-type <?= $importTarget === 'cartao' ? 'checked' : '' ?>>
                            <span>
                                <i data-lucide="credit-card" aria-hidden="true"></i>
                                Cartão/fatura
                            </span>
                        </label>
                    </fieldset>

                    <div class="imp-flow-grid">
                        <div class="imp-field" data-imp-account-field <?= $importTarget === 'cartao' ? 'hidden' : '' ?>>
                            <label class="imp-field__label" for="imp-account-select">Conta vinculada</label>
                            <?php if ($accounts === []) : ?>
                                <p class="imp-inline-warning" data-imp-account-warning>
                                    Nenhuma conta ativa encontrada. Configure uma conta para liberar o preview.
                                </p>
                                <a class="imp-link" href="<?= BASE_URL ?>contas">Abrir contas</a>
                            <?php else : ?>
                                <select id="imp-account-select" class="imp-field__control" name="conta_id"
                                    data-imp-account-select-main>
                                    <?php foreach ($accounts as $account) : ?>
                                        <?php $accountId = (int) ($account['id'] ?? 0); ?>
                                        <option value="<?= $accountId ?>"
                                            <?= $accountId === $selectedAccountId ? 'selected' : '' ?>>
                                            <?= escape((string) ($account['nome'] ?? 'Conta sem nome')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="imp-field" data-imp-card-field <?= $importTarget === 'conta' ? 'hidden' : '' ?>>
                            <label class="imp-field__label" for="imp-card-select">Cartão vinculado</label>
                            <?php if ($cards === []) : ?>
                                <p class="imp-inline-warning" data-imp-card-warning>
                                    Nenhum cartão ativo encontrado. Cadastre ou restaure um cartão para importar fatura OFX.
                                </p>
                                <a class="imp-link" href="<?= BASE_URL ?>cartoes">Abrir cartões</a>
                            <?php else : ?>
                                <select id="imp-card-select" class="imp-field__control" name="cartao_id"
                                    data-imp-card-select-main>
                                    <?php foreach ($cards as $card) : ?>
                                        <?php $cardId = (int) ($card['id'] ?? 0); ?>
                                        <option value="<?= $cardId ?>" <?= $cardId === $selectedCardId ? 'selected' : '' ?>>
                                            <?= escape((string) ($card['nome'] ?? 'Cartão sem nome')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <fieldset class="imp-format-switch" aria-label="Formato da importação">
                            <legend class="imp-field__label">Formato</legend>
                            <?php foreach ($supportedFormats as $format) : ?>
                                <?php
                                $formatValue = strtolower((string) $format);
                                $radioId = 'imp-format-' . $formatValue;
                                $isDisabledForCard = $importTarget === 'cartao' && $formatValue !== 'ofx';
                                ?>
                                <label
                                    class="imp-format-switch__item<?= $isDisabledForCard ? ' imp-format-switch__item--disabled' : '' ?>"
                                    for="<?= escape($radioId) ?>">
                                    <input id="<?= escape($radioId) ?>" type="radio" name="source_type"
                                        value="<?= escape($formatValue) ?>" data-imp-source-type
                                        <?= $isDisabledForCard ? 'disabled' : '' ?>
                                        <?= $initialSourceType === $formatValue ? 'checked' : '' ?>>
                                    <span>
                                        <i data-lucide="<?= $formatValue === 'csv' ? 'sheet' : 'file-code-2' ?>"
                                            aria-hidden="true"></i>
                                        <?= escape((string) $format) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="imp-inline-warning imp-inline-warning--quota" data-imp-quota-warning
                            <?= (bool) ($importQuota['allowed'] ?? true) ? 'hidden' : '' ?>>
                            <?= escape((string) ($importQuota['message'] ?? 'Limite de importação atingido para o plano atual.')) ?>
                            <a class="imp-link" href="<?= escape($upgradeUrl) ?>">Fazer upgrade</a>
                        </p>
                        <p class="imp-muted imp-target-source-hint" data-imp-target-source-hint
                            <?= $importTarget === 'cartao' ? '' : 'hidden' ?>>
                            No fluxo de cartão/fatura, apenas OFX está disponível nesta etapa.
                        </p>
                    </div>

                    <div class="imp-field">
                        <label class="imp-field__label" for="imp-file-input">Arquivo</label>
                        <label class="imp-file-drop surface-card" for="imp-file-input" data-imp-file-drop>
                            <input id="imp-file-input" class="imp-file-drop__input" type="file" name="file"
                                accept=".ofx,.csv,text/csv,application/vnd.ms-excel" data-imp-file-input>
                            <span class="imp-file-drop__icon" aria-hidden="true">
                                <i data-lucide="upload-cloud"></i>
                            </span>
                            <span class="imp-file-drop__title">Selecionar arquivo OFX ou CSV</span>
                            <span class="imp-file-drop__hint">A importação só persiste dados após a confirmação
                                final.</span>
                        </label>
                        <p class="imp-file-selected" data-imp-selected-file>Nenhum arquivo selecionado.</p>
                    </div>

                    <div class="imp-flow-form__footer">
                        <button class="btn btn-primary" type="submit" data-imp-submit
                            <?= $accounts === [] && $cards === [] ? 'disabled' : '' ?>>
                            Preparar preview
                        </button>
                        <p class="imp-flow-status" data-imp-preview-state>
                            Selecione alvo, formato e arquivo para montar o preview.
                        </p>
                    </div>
                </form>
            </article>

            <article class="imp-preview-card imp-surface surface-card surface-card--interactive"
                data-imp-preview-region>
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Revisão final</p>
                        <h2 class="imp-card-title">Preview e confirmação</h2>
                        <p class="imp-card-text">
                            Valide warnings, erros e linhas normalizadas antes de persistir os dados.
                        </p>
                    </div>
                    <span class="imp-status-badge" data-status="idle" data-imp-preview-badge>Aguardando arquivo</span>
                </header>

                <dl class="imp-preview-summary">
                    <div class="surface-card">
                        <dt>Alvo</dt>
                        <dd data-imp-preview-target><?= escape($historyTargetLabelMap[$importTarget] ?? 'Conta') ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Contexto ativo</dt>
                        <dd data-imp-preview-context-label><?= escape($activeContextLabel) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Formato</dt>
                        <dd data-imp-preview-source-type><?= strtoupper(escape((string) $initialSourceType)) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Arquivo</dt>
                        <dd data-imp-preview-file-name>-</dd>
                    </div>
                    <div class="surface-card">
                        <dt>Total de linhas</dt>
                        <dd data-imp-preview-total-rows>0</dd>
                    </div>
                    <div class="surface-card">
                        <dt>Categorizadas</dt>
                        <dd data-imp-preview-categorized>0</dd>
                    </div>
                    <div class="surface-card">
                        <dt>Sem categoria</dt>
                        <dd data-imp-preview-uncategorized>0</dd>
                    </div>
                </dl>

                <ul class="imp-message-list imp-message-list--warning" data-imp-preview-warnings hidden></ul>
                <ul class="imp-message-list imp-message-list--error" data-imp-preview-errors hidden></ul>

                <div class="imp-preview-empty surface-card" data-imp-preview-empty>
                    O preview será exibido aqui com resumo do arquivo, validações, linhas normalizadas e categorização opcional.
                </div>

                <div class="imp-preview-tools" data-imp-preview-tools hidden>
                    <div class="imp-preview-tools__primary">
                        <button class="btn btn-secondary" type="button" data-imp-categorize-preview>
                            Categorizar linhas
                        </button>
                        <p class="imp-muted" data-imp-categorize-helper>
                            Opcional: aplica sugestões automáticas por regra do usuário e regra global sem bloquear a confirmação.
                        </p>
                    </div>
                    <div class="imp-preview-tools__secondary">
                        <div class="imp-preview-metrics" aria-label="Resumo das sugestões automáticas">
                            <span class="imp-preview-metric" data-source="user_rule">
                                Regra do usuário
                                <strong data-imp-preview-user-rule-suggested>0</strong>
                            </span>
                            <span class="imp-preview-metric" data-source="rule">
                                Regra global
                                <strong data-imp-preview-global-rule-suggested>0</strong>
                            </span>
                        </div>
                    </div>
                    <label class="imp-preview-filter" for="imp-filter-pending-only">
                        <input id="imp-filter-pending-only" type="checkbox" data-imp-filter-pending-only>
                        <span>Mostrar apenas linhas sem categoria</span>
                    </label>
                </div>

                <div class="imp-preview-table-wrap" data-imp-preview-table-wrap hidden>
                    <table class="imp-preview-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Subcategoria</th>
                                <th>Origem</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody data-imp-preview-rows></tbody>
                    </table>
                </div>

                <footer class="imp-preview-card__footer">
                    <button class="btn btn-primary" type="button" data-imp-confirm disabled>
                        Confirmar importação
                    </button>
                    <p class="imp-muted" data-imp-preview-next-step>
                        Revise o preview e confirme para persistir os dados no sistema.
                    </p>
                </footer>
            </article>
        </div>

        <aside class="imp-index-side">
            <article class="imp-side-card imp-surface surface-card surface-card--interactive" data-imp-plan-card>
                <header class="imp-card-head imp-card-head--split">
                    <h3 class="imp-card-title">Plano e quota</h3>
                    <span class="imp-status-badge"
                        data-status="<?= $currentPlan === 'free' ? 'idle' : 'preview_ready' ?>">
                        <?= strtoupper(escape($currentPlan)) ?>
                    </span>
                </header>
                <?php if ($currentPlan !== 'free') : ?>
                    <p class="imp-card-text">Plano pago ativo: importações OFX/CSV liberadas sem limite prático.</p>
                <?php else : ?>
                    <dl class="imp-definition-list">
                        <?php foreach ($importLimitLabelMap as $bucketKey => $bucketLabel) : ?>
                            <?php $bucket = is_array($importLimitBuckets[$bucketKey] ?? null) ? $importLimitBuckets[$bucketKey] : []; ?>
                            <?php $bucketRemaining = $bucket['remaining'] ?? null; ?>
                            <dt><?= escape($bucketLabel) ?></dt>
                            <dd><?= is_numeric($bucketRemaining) ? (int) $bucketRemaining . ' restante(s)' : 'Ilimitado' ?></dd>
                        <?php endforeach; ?>
                    </dl>
                    <a class="imp-link" href="<?= escape($upgradeUrl) ?>">Fazer upgrade</a>
                <?php endif; ?>
            </article>
            <article class="imp-side-card imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <h3 class="imp-card-title">Perfil ativo</h3>
                    <span class="imp-status-badge" data-status="preview_ready">Conta</span>
                </header>
                <?php if ($profileConfig !== null) : ?>
                    <dl class="imp-definition-list">
                        <dt>Conta</dt>
                        <dd><?= (int) ($profileConfig['conta_id'] ?? 0) ?></dd>
                        <dt>Origem padrão</dt>
                        <dd><?= strtoupper(escape((string) ($profileConfig['source_type'] ?? 'ofx'))) ?></dd>
                        <dt>Agência</dt>
                        <dd><?= escape((string) ($profileConfig['agencia'] ?? '')) ?: 'Opcional' ?></dd>
                        <dt>Número</dt>
                        <dd><?= escape((string) ($profileConfig['numero_conta'] ?? '')) ?: 'Opcional' ?></dd>
                    </dl>
                <?php else : ?>
                    <p class="imp-card-text">Sem perfil base. Configure uma conta para liberar o fluxo completo.</p>
                <?php endif; ?>
                <a class="imp-link" href="<?= escape($configUrl) ?>">Gerenciar perfil e configuração</a>
            </article>

            <article class="imp-side-card imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <h3 class="imp-card-title">Histórico de lotes</h3>
                    <span class="imp-status-badge" data-status="processed"><?= $heroProcessedCount ?> processados</span>
                </header>
                <?php if ($latestHistoryItems !== []) : ?>
                    <ul class="imp-history-mini-list">
                        <?php foreach ($latestHistoryItems as $historyItem) : ?>
                            <?php $status = strtolower((string) ($historyItem['status'] ?? 'processed')); ?>
                            <?php $target = strtolower((string) ($historyItem['import_target'] ?? 'conta')); ?>
                            <li>
                                <span class="imp-history-mini-list__id">#<?= (int) ($historyItem['batch_id'] ?? 0) ?></span>
                                <span
                                    class="imp-history-mini-list__file"><?= escape((string) ($historyItem['filename'] ?? 'arquivo')) ?></span>
                                <span class="imp-status-badge" data-status="<?= escape($status) ?>">
                                    <?= escape($historyStatusLabelMap[$status] ?? strtoupper($status)) ?>
                                </span>
                                <span class="imp-status-badge" data-status="idle">
                                    <?= escape($historyTargetLabelMap[$target] ?? 'Conta') ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="imp-card-text">
                        Nenhum lote confirmado ainda. O histórico é atualizado assim que uma importação é confirmada.
                    </p>
                <?php endif; ?>
                <a class="imp-link" href="<?= BASE_URL ?>importacoes/historico">Ver histórico</a>
            </article>
        </aside>
    </div>
</section>