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
$currentPlan = strtolower(trim((string) ($planLimits['plan'] ?? 'free')));
$upgradeUrl = trim((string) ($planLimits['upgrade_url'] ?? '/assinatura'));
$configPageBaseUrl = trim((string) ($configPageBaseUrl ?? (BASE_URL . 'importacoes/configuracoes')));
$confirmAsyncDefault = (bool) ($confirmAsyncDefault ?? false);
$latestHistoryItems = is_array($latestHistoryItems ?? null) ? $latestHistoryItems : [];
$initialSourceType = strtolower(trim((string) ($profileConfig['source_type'] ?? 'ofx')));
if (!in_array($initialSourceType, ['ofx', 'csv'], true)) {
    $initialSourceType = 'ofx';
}

$profileOptions = is_array($profileConfig['options'] ?? null) ? $profileConfig['options'] : [];
$csvMappingMode = strtolower(trim((string) ($profileOptions['csv_mapping_mode'] ?? 'auto')));
$csvMappingModeLabel = $csvMappingMode === 'manual' ? 'Manual' : 'Automático';
$csvHasHeaderValue = $profileOptions['csv_has_header'] ?? true;
if (is_bool($csvHasHeaderValue)) {
    $csvHasHeader = $csvHasHeaderValue;
} else {
    $csvHasHeaderRaw = strtolower(trim((string) $csvHasHeaderValue));
    $csvHasHeader = !in_array($csvHasHeaderRaw, ['0', 'false', 'nao', 'não', 'off'], true);
}
$csvHasHeaderLabel = $csvHasHeader ? 'Sim' : 'Não';
$csvStartRow = (int) ($profileOptions['csv_start_row'] ?? ($csvHasHeader ? 2 : 1));
if ($csvStartRow <= 0) {
    $csvStartRow = $csvHasHeader ? 2 : 1;
}
$csvDelimiterValue = (string) ($profileOptions['csv_delimiter'] ?? ';');
$csvDelimiterLabel = $csvDelimiterValue === "\t" ? 'TAB' : ($csvDelimiterValue !== '' ? $csvDelimiterValue : ';');
$csvDateFormatLabel = trim((string) ($profileOptions['csv_date_format'] ?? 'd/m/Y')) ?: 'd/m/Y';
$csvDecimalLabel = trim((string) ($profileOptions['csv_decimal_separator'] ?? ',')) === '.' ? '.' : ',';
$csvColumnMap = is_array($profileOptions['csv_column_map'] ?? null) ? $profileOptions['csv_column_map'] : [];
$csvColumnSummaryLabels = $importTarget === 'cartao'
    ? ['data' => 'Data', 'descricao' => 'Descrição', 'valor' => 'Valor', 'observacao' => 'Observação', 'id_externo' => 'ID externo']
    : ['tipo' => 'Tipo', 'data' => 'Data', 'descricao' => 'Descrição', 'valor' => 'Valor', 'categoria' => 'Categoria', 'subcategoria' => 'Subcategoria'];
$csvColumnSummaryParts = [];
foreach ($csvColumnSummaryLabels as $field => $label) {
    $columnReference = strtoupper(trim((string) ($csvColumnMap[$field] ?? '')));
    if ($columnReference === '') {
        continue;
    }

    $csvColumnSummaryParts[] = $label . ': ' . $columnReference;
}
$csvColumnMapSummary = $csvColumnSummaryParts !== [] ? implode(' | ', $csvColumnSummaryParts) : 'Padrão Lukrato';

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

$configUrl = $configPageBaseUrl !== '' ? $configPageBaseUrl : (BASE_URL . 'importacoes/configuracoes');
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
    'import_cartao_ofx' => 'Fatura/cartão (OFX ou CSV)',
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
$hasInitialContextOptions = $importTarget === 'cartao' ? $cards !== [] : $accounts !== [];
$activeAccountLabel = (string) ($activeAccount['nome'] ?? ($selectedAccountId > 0 ? 'Conta #' . $selectedAccountId : 'Conta não selecionada'));
$advancedBadgeLabel = $initialSourceType === 'csv' ? 'CSV ativo' : 'OFX automático';
$advancedDescription = $initialSourceType === 'csv'
    ? ($importTarget === 'cartao'
        ? 'Se a fatura vier em CSV, basta manter data, descrição e valor legíveis. Sem coluna de tipo, valor positivo vira despesa e negativo vira estorno; abra o avançado só se cabeçalho, delimitador ou data fugirem do padrão.'
        : 'Para CSV de conta no padrão Lukrato, use tipo;data;descricao;valor com ;, datas em dd/mm/yyyy, valores com vírgula e remova linhas vazias ou incompletas no fim. Abra o avançado só se o arquivo fugir disso.')
    : ($importTarget === 'cartao'
        ? 'OFX de fatura entra automático. Compras parceladas podem vir no MEMO, como "Parcela 3/6", sem exigir mapeamento manual.'
        : 'OFX bancário entra automático, mesmo quando o banco marca tudo como TRNTYPE genérico. O Lukrato usa data, valor e histórico do extrato.');
$advancedTemplateTitle = $importTarget === 'cartao'
    ? 'Modelo recomendado para CSV de cartão/fatura'
    : 'Modelo recomendado para CSV de conta';
$advancedTemplateCopy = $importTarget === 'cartao'
    ? 'O modelo automático cobre data, descrição e valor. Use o manual se a operadora exportar observação, ID externo ou colunas extras fora do padrão esperado.'
    : 'O modelo rápido segue o padrão tipo;data;descricao;valor com ;, dd/mm/yyyy e valores como 149,90. O manual adiciona categoria, subcategoria, observação e ID externo.';
$advancedContextNote = $importTarget === 'cartao'
    ? 'A configuração CSV usa a conta vinculada ao cartão selecionado.'
    : 'A configuração CSV usa a conta selecionada neste fluxo.';
$profileCardBadgeLabel = $importTarget === 'cartao' ? 'Conta vinculada' : 'Conta ativa';
$guidePathTitle = $initialSourceType === 'csv'
    ? ($importTarget === 'cartao' ? 'CSV de fatura guiado' : 'CSV de conta no padrão Lukrato')
    : ($importTarget === 'cartao' ? 'OFX de fatura do cartão' : 'OFX de extrato bancário');

$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$importacoesPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'importacoes'
    ? $layoutPageCapabilities
    : [];

$importacoesCustomizerCapabilities = is_array($importacoesPageCapabilities['customizer'] ?? null)
    ? $importacoesPageCapabilities['customizer']
    : [];

$importacoesForcedPreferences = is_array($importacoesCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $importacoesCustomizerCapabilities['forcedPreferences']
    : [];

$showImportacoesHero = (bool) ($importacoesForcedPreferences['toggleImpHero'] ?? true);
$showImportacoesSidebar = (bool) ($importacoesForcedPreferences['toggleImpSidebar'] ?? true);
$showImportacoesSidebarOnPage = $hasInitialContextOptions && $showImportacoesSidebar;
?>

<section class="imp-page" data-importacoes-page="index" data-lk-help-page="importacoes"
    data-imp-config-page-base-url="<?= escape($configPageBaseUrl) ?>"
    data-imp-active-account-id="<?= $selectedAccountId ?>" data-imp-active-card-id="<?= $selectedCardId ?>"
    data-imp-import-target="<?= escape($importTarget) ?>" data-imp-source-type="<?= escape($initialSourceType) ?>"
    data-imp-confirm-async-default="<?= $confirmAsyncDefault ? '1' : '0' ?>">
    <div class="imp-index-layout <?= $showImportacoesSidebarOnPage ? '' : 'imp-index-layout--single-column' ?>">
        <div class="imp-index-main">
            <article class="imp-flow-card imp-surface surface-card surface-card--interactive" id="impFlowSection">
                <header class="imp-card-head imp-card-head--split" id="impHeroSection"
                    <?= !$showImportacoesHero ? ' style="display:none;"' : '' ?>>
                    <div>
                        <p class="imp-card-eyebrow" data-imp-flow-eyebrow>
                            <?= $hasInitialContextOptions ? 'Passo 1' : 'Antes de importar' ?>
                        </p>
                        <h2 class="imp-card-title" data-imp-flow-title>
                            <?= $hasInitialContextOptions ? 'Envie seu arquivo' : ($importTarget === 'cartao' ? 'Cadastre um cartão' : 'Crie sua primeira conta') ?>
                        </h2>
                        <p class="imp-card-text" data-imp-flow-copy>
                            <?= $hasInitialContextOptions ? 'Escolha o contexto, o formato e o arquivo.' : ($importTarget === 'cartao' ? 'Depois você envia a fatura.' : 'Depois você envia o arquivo.') ?>
                        </p>
                    </div>
                    <div class="imp-flow-actions">
                        <span class="imp-status-badge" data-status="idle" data-imp-flow-badge>
                            <?= $hasInitialContextOptions ? 'Preparar' : 'Contexto' ?>
                        </span>
                        <button class="btn btn-primary imp-flow-submit-compact" type="submit" form="imp-upload-form"
                            data-imp-compact-submit hidden>
                            Preparar preview
                        </button>
                        <button class="btn btn-ghost imp-flow-edit" type="button" data-imp-edit-setup hidden>
                            Alterar
                        </button>
                    </div>
                </header>

                <form class="imp-flow-form" id="imp-upload-form" novalidate>
                    <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>

                    <div class="imp-flow-topline">
                        <fieldset class="imp-format-switch" aria-label="Alvo da importação">
                            <legend class="imp-field__label">Alvo</legend>
                            <label class="imp-format-switch__item surface-card" for="imp-target-conta">
                                <input id="imp-target-conta" type="radio" name="import_target" value="conta"
                                    data-imp-target-type <?= $importTarget === 'conta' ? 'checked' : '' ?>>
                                <span>
                                    <i data-lucide="wallet" aria-hidden="true"></i>
                                    Conta
                                </span>
                            </label>
                            <label class="imp-format-switch__item surface-card" for="imp-target-cartao">
                                <input id="imp-target-cartao" type="radio" name="import_target" value="cartao"
                                    data-imp-target-type <?= $importTarget === 'cartao' ? 'checked' : '' ?>>
                                <span>
                                    <i data-lucide="credit-card" aria-hidden="true"></i>
                                    Cartão/fatura
                                </span>
                            </label>
                        </fieldset>

                        <div class="imp-flow-grid">
                            <div class="imp-field" data-imp-account-field
                                <?= $importTarget === 'cartao' || $accounts === [] ? 'hidden' : '' ?>>
                                <label class="imp-field__label" for="imp-account-select">Conta</label>
                                <p class="imp-inline-warning" data-imp-account-warning
                                    <?= $accounts !== [] ? 'hidden' : '' ?>>
                                    Nenhuma conta ativa encontrada. Configure uma conta para liberar o preview.
                                </p>
                                <a class="imp-link" href="<?= BASE_URL ?>contas" data-imp-account-link
                                    <?= $accounts !== [] ? 'hidden' : '' ?>>Criar contas +</a>
                                <select id="imp-account-select" class="imp-field__control" name="conta_id"
                                    data-imp-account-select-main <?= $accounts === [] ? 'hidden' : '' ?>>
                                    <?php foreach ($accounts as $account) : ?>
                                    <?php $accountId = (int) ($account['id'] ?? 0); ?>
                                    <option value="<?= $accountId ?>"
                                        <?= $accountId === $selectedAccountId ? 'selected' : '' ?>>
                                        <?= escape((string) ($account['nome'] ?? 'Conta sem nome')) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="imp-field" data-imp-card-field
                                <?= $importTarget === 'conta' || $cards === [] ? 'hidden' : '' ?>>
                                <label class="imp-field__label" for="imp-card-select">Cartão</label>
                                <p class="imp-inline-warning" data-imp-card-warning
                                    <?= $cards !== [] ? 'hidden' : '' ?>>
                                    Nenhum cartão ativo encontrado. Cadastre ou restaure um cartão para importar a
                                    fatura.
                                </p>
                                <a class="imp-link" href="<?= BASE_URL ?>cartoes" data-imp-card-link
                                    <?= $cards !== [] ? 'hidden' : '' ?>>Abrir cartões</a>
                                <select id="imp-card-select" class="imp-field__control" name="cartao_id"
                                    data-imp-card-select-main <?= $cards === [] ? 'hidden' : '' ?>>
                                    <?php foreach ($cards as $card) : ?>
                                    <?php $cardId = (int) ($card['id'] ?? 0); ?>
                                    <option value="<?= $cardId ?>"
                                        data-linked-account-id="<?= (int) ($card['conta_id'] ?? 0) ?>"
                                        <?= $cardId === $selectedCardId ? 'selected' : '' ?>>
                                        <?= escape((string) ($card['nome'] ?? 'Cartão sem nome')) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <fieldset class="imp-format-switch" aria-label="Formato da importação" data-imp-format-field
                                <?= $hasInitialContextOptions ? '' : 'hidden' ?>>
                                <legend class="imp-field__label">Formato</legend>
                                <?php foreach ($supportedFormats as $format) : ?>
                                <?php
                                    $formatValue = strtolower((string) $format);
                                    $radioId = 'imp-format-' . $formatValue;
                                    ?>
                                <label class="imp-format-switch__item surface-card" for="<?= escape($radioId) ?>">
                                    <input id="<?= escape($radioId) ?>" type="radio" name="source_type"
                                        value="<?= escape($formatValue) ?>" data-imp-source-type
                                        <?= $initialSourceType === $formatValue ? 'checked' : '' ?>>
                                    <span>
                                        <i data-lucide="<?= $formatValue === 'csv' ? 'sheet' : 'file-code-2' ?>"
                                            aria-hidden="true"></i>
                                        <?= escape((string) $format) ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </div>
                    </div>

                    <section class="imp-flow-blocker surface-card" data-imp-context-blocker
                        <?= $hasInitialContextOptions ? 'hidden' : '' ?>>
                        <p class="imp-card-eyebrow">Antes de importar</p>
                        <h3 class="imp-card-title" data-imp-context-blocker-title>
                            <?= $importTarget === 'cartao' ? 'Cadastre um cartão' : 'Crie sua primeira conta' ?>
                        </h3>
                        <p class="imp-card-text" data-imp-context-blocker-copy>
                            <?= $importTarget === 'cartao' ? 'Depois você envia a fatura.' : 'Depois você envia o arquivo.' ?>
                        </p>
                        <a class="btn btn-primary"
                            href="<?= BASE_URL . ($importTarget === 'cartao' ? 'cartoes' : 'contas') ?>"
                            data-imp-context-blocker-link>
                            <?= $importTarget === 'cartao' ? 'Criar cartões +' : 'Criar contas +' ?>
                        </a>
                    </section>

                    <div class="imp-flow-workspace" <?= $hasInitialContextOptions ? '' : 'hidden' ?>>
                        <section class="imp-upload-stage surface-card">
                            <header class="imp-upload-stage__head">
                                <div>
                                    <p class="imp-card-eyebrow">Arquivo</p>
                                    <h3 class="imp-card-title">Selecione o arquivo</h3>
                                    <p class="imp-card-text">
                                        OFX ou CSV.
                                    </p>
                                </div>
                                <span class="imp-status-badge" data-status="idle" data-imp-file-stage-badge>
                                    Aguardando arquivo
                                </span>
                            </header>

                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-file-input">Arquivo</label>
                                <label class="imp-file-drop surface-card" for="imp-file-input" data-imp-file-drop>
                                    <input id="imp-file-input" class="imp-file-drop__input" type="file" name="file"
                                        accept=".ofx,.csv,text/csv,application/vnd.ms-excel" data-imp-file-input>
                                    <span class="imp-file-drop__icon" aria-hidden="true">
                                        <i data-lucide="upload-cloud"></i>
                                    </span>
                                    <span class="imp-file-drop__title">Arraste ou selecione o arquivo</span>
                                    <span class="imp-file-drop__hint">OFX ou CSV</span>
                                </label>
                                <p class="imp-file-selected" data-imp-selected-file>Nenhum arquivo selecionado.</p>
                                <p class="imp-file-note" data-imp-file-note hidden></p>
                            </div>

                            <div class="imp-flow-form__footer">
                                <button class="btn btn-primary" type="submit" data-imp-submit
                                    <?= $accounts === [] && $cards === [] ? 'disabled' : '' ?>>
                                    Preparar preview
                                </button>
                                <p class="imp-flow-status" data-imp-preview-state>
                                    Escolha o contexto e envie o arquivo.
                                </p>
                                <p class="imp-inline-warning imp-inline-warning--quota" data-imp-quota-warning
                                    <?= (bool) ($importQuota['allowed'] ?? true) ? 'hidden' : '' ?>>
                                    <?= escape((string) ($importQuota['message'] ?? 'Limite de importação atingido para o plano atual.')) ?>
                                    <a class="imp-link" href="<?= escape($upgradeUrl) ?>">Fazer upgrade</a>
                                </p>
                            </div>
                        </section>
                    </div>

                    <details class="imp-advanced-panel surface-card" data-imp-advanced-panel>
                        <summary class="imp-advanced-panel__summary">
                            <div class="imp-advanced-panel__summary-head">
                                <div>
                                    <p class="imp-card-eyebrow">Opcional</p>
                                    <h3 class="imp-card-title">CSV avançado</h3>
                                    <p class="imp-card-text" data-imp-advanced-summary-copy>
                                        Abra só se precisar.
                                    </p>
                                </div>
                                <div class="imp-advanced-panel__summary-meta">
                                    <span class="imp-status-badge"
                                        data-status="<?= $initialSourceType === 'csv' ? 'preview_ready' : 'idle' ?>"
                                        data-imp-advanced-mode-badge><?= escape($advancedBadgeLabel) ?></span>
                                    <span class="imp-advanced-panel__toggle">Ver opções</span>
                                </div>
                            </div>
                        </summary>

                        <div class="imp-advanced-panel__body">
                            <p class="imp-card-text" data-imp-advanced-description>
                                <?= escape($advancedDescription) ?>
                            </p>

                            <div class="imp-advanced-panel__grid">
                                <div class="imp-advanced-callout surface-card">
                                    <span class="imp-chip" data-imp-advanced-template-chip>
                                        <?= $importTarget === 'cartao' ? 'Modelo de fatura' : 'Modelo de conta' ?>
                                    </span>
                                    <strong class="imp-advanced-callout__title" data-imp-advanced-template-title>
                                        <?= escape($advancedTemplateTitle) ?>
                                    </strong>
                                    <p class="imp-card-text" data-imp-advanced-template-copy>
                                        <?= escape($advancedTemplateCopy) ?>
                                    </p>
                                    <p class="imp-muted" data-imp-advanced-linked-account-note>
                                        <?= escape($advancedContextNote) ?>
                                    </p>
                                </div>

                                <div class="imp-advanced-actions">
                                    <a class="btn btn-ghost" href="#" data-imp-advanced-template-auto
                                        data-no-transition="true" download>
                                        <?= $importTarget === 'cartao' ? 'Baixar modelo rápido de fatura' : 'Baixar modelo rápido de conta' ?>
                                    </a>
                                    <a class="btn btn-ghost" href="#" data-imp-advanced-template-manual
                                        data-no-transition="true" download>
                                        <?= $importTarget === 'cartao' ? 'Baixar modelo completo de fatura' : 'Baixar modelo completo de conta' ?>
                                    </a>
                                    <a class="btn btn-secondary" href="<?= escape($configUrl) ?>" data-imp-config-link>
                                        Editar configuração avançada
                                    </a>
                                </div>
                            </div>

                            <details class="imp-advanced-details" data-imp-advanced-details
                                <?= $initialSourceType === 'csv' ? 'open' : '' ?>>
                                <summary>
                                    <span>Ver configuração CSV aplicada</span>
                                    <small data-imp-advanced-summary-context>
                                        <?= escape($importTarget === 'cartao' ? 'Conta vinculada ao cartão selecionado' : 'Conta selecionada') ?>
                                    </small>
                                </summary>

                                <dl class="imp-definition-list imp-definition-list--stack imp-advanced-profile">
                                    <div>
                                        <dt>Conta base</dt>
                                        <dd data-imp-advanced-account-name><?= escape($activeAccountLabel) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Origem padrão</dt>
                                        <dd data-imp-advanced-source-type>
                                            <?= strtoupper(escape((string) ($profileConfig['source_type'] ?? 'ofx'))) ?>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Modo CSV</dt>
                                        <dd data-imp-advanced-mapping-mode><?= escape($csvMappingModeLabel) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Com cabeçalho</dt>
                                        <dd data-imp-advanced-has-header><?= escape($csvHasHeaderLabel) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Linha inicial</dt>
                                        <dd data-imp-advanced-start-row><?= $csvStartRow ?></dd>
                                    </div>
                                    <div>
                                        <dt>Delimitador</dt>
                                        <dd data-imp-advanced-delimiter><?= escape($csvDelimiterLabel) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Formato de data</dt>
                                        <dd data-imp-advanced-date-format><?= escape($csvDateFormatLabel) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Separador decimal</dt>
                                        <dd data-imp-advanced-decimal><?= escape($csvDecimalLabel) ?></dd>
                                    </div>
                                    <div class="imp-advanced-profile__item imp-advanced-profile__item--full">
                                        <dt>Mapeamento principal</dt>
                                        <dd data-imp-advanced-column-map><?= escape($csvColumnMapSummary) ?></dd>
                                    </div>
                                </dl>
                            </details>
                        </div>
                    </details>
                </form>
            </article>

            <article class="imp-preview-card imp-surface surface-card surface-card--interactive" id="impPreviewSection"
                data-imp-preview-region hidden>
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Passo 2</p>
                        <h2 class="imp-card-title">Revise</h2>
                        <p class="imp-card-text">
                            Confira antes de confirmar.
                        </p>
                    </div>
                    <span class="imp-status-badge" data-status="idle" data-imp-preview-badge>Aguardando arquivo</span>
                </header>

                <div class="imp-preview-overview" data-imp-preview-overview>
                    <article class="imp-preview-readiness surface-card" data-imp-preview-readiness-card>
                        <div class="imp-preview-readiness__head">
                            <span class="imp-preview-readiness__eyebrow">Status</span>
                            <span class="imp-status-badge" data-status="idle" data-imp-preview-readiness-badge>
                                Aguardando preview
                            </span>
                        </div>
                        <strong class="imp-preview-readiness__title" data-imp-preview-readiness-title>
                            Prepare um arquivo para revisar o lote
                        </strong>
                        <p class="imp-preview-readiness__copy" data-imp-preview-readiness-copy>
                            O preview vai dizer se o lote já pode ser confirmado ou se ainda precisa de revisão.
                        </p>
                        <div class="imp-preview-readiness__chips" aria-label="Resumo rápido do preview">
                            <span class="imp-preview-chip" data-tone="neutral" data-imp-preview-warning-chip>0
                                avisos</span>
                            <span class="imp-preview-chip" data-tone="neutral" data-imp-preview-error-chip>0
                                erros</span>
                            <span class="imp-preview-chip" data-tone="neutral" data-imp-preview-pending-chip>0 sem
                                categoria</span>
                        </div>
                    </article>

                    <dl class="imp-preview-summary">
                        <div class="surface-card">
                            <dt>Alvo</dt>
                            <dd data-imp-preview-target><?= escape($historyTargetLabelMap[$importTarget] ?? 'Conta') ?>
                            </dd>
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
                </div>

                <ul class="imp-message-list imp-message-list--warning" data-imp-preview-warnings hidden></ul>
                <ul class="imp-message-list imp-message-list--error" data-imp-preview-errors hidden></ul>

                <div class="imp-preview-empty surface-card" data-imp-preview-empty>
                    <span class="imp-preview-empty__icon" aria-hidden="true">
                        <i data-lucide="scan-search"></i>
                    </span>
                    <strong class="imp-preview-empty__title" data-imp-preview-empty-title>Envie um arquivo para
                        revisar</strong>
                    <p class="imp-preview-empty__copy" data-imp-preview-empty-copy>
                        O preview aparece aqui.
                    </p>
                </div>

                <div class="imp-preview-tools" data-imp-preview-tools hidden>
                    <div class="imp-preview-tools__primary">
                        <button class="btn btn-secondary" type="button" data-imp-categorize-preview>
                            Categorizar linhas
                        </button>
                        <p class="imp-muted" data-imp-categorize-helper>
                            Opcional.
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

                <div class="imp-confirm-strip surface-card" data-imp-confirm-strip>
                    <div class="imp-confirm-strip__head">
                        <span class="imp-confirm-strip__eyebrow">Antes de confirmar</span>
                        <strong class="imp-confirm-strip__title" data-imp-confirm-title>
                            Confira o lote
                        </strong>
                    </div>
                    <div class="imp-confirm-strip__grid">
                        <article class="imp-confirm-check" data-state="idle" data-imp-confirm-check="context">
                            <strong class="imp-confirm-check__title">Contexto</strong>
                            <span class="imp-confirm-check__copy" data-imp-confirm-copy="context">
                                Escolha o destino e o arquivo.
                            </span>
                        </article>
                        <article class="imp-confirm-check" data-state="idle" data-imp-confirm-check="review">
                            <strong class="imp-confirm-check__title">Revisão</strong>
                            <span class="imp-confirm-check__copy" data-imp-confirm-copy="review">
                                Veja se está tudo certo.
                            </span>
                        </article>
                        <article class="imp-confirm-check" data-state="idle" data-imp-confirm-check="confirm">
                            <strong class="imp-confirm-check__title">Confirmação</strong>
                            <span class="imp-confirm-check__copy" data-imp-confirm-copy="confirm">
                                Confirme quando liberar.
                            </span>
                        </article>
                    </div>
                </div>

                <footer class="imp-preview-card__footer" data-imp-preview-footer>
                    <div class="imp-preview-card__next">
                        <span class="imp-preview-card__next-eyebrow">Agora</span>
                        <p class="imp-muted" data-imp-preview-next-step>
                            Revise e confirme.
                        </p>
                    </div>
                    <button class="btn btn-primary" type="button" data-imp-confirm disabled>
                        Confirmar importação
                    </button>
                </footer>
            </article>
        </div>

        <aside class="imp-index-side" id="impIndexSideSection" data-imp-side-panel
            <?= $showImportacoesSidebarOnPage ? '' : 'hidden style="display:none;"' ?>>
            <article class="imp-side-card imp-side-card--support imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Resumo</p>
                        <h3 class="imp-card-title">Apoio</h3>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Apoio</span>
                </header>
                <section class="imp-side-section">
                    <div class="imp-side-section__head">
                        <span class="imp-side-section__title">Plano</span>
                        <span class="imp-status-badge" data-imp-plan-badge
                            data-status="<?= $currentPlan === 'free' ? 'idle' : 'preview_ready' ?>">
                            <?= strtoupper(escape($currentPlan)) ?>
                        </span>
                    </div>
                    <div data-imp-plan-summary>
                        <?php if ($currentPlan !== 'free') : ?>
                        <p class="imp-card-text">Plano pago ativo. OFX e CSV liberados sem limite prático.</p>
                        <?php else : ?>
                        <dl class="imp-definition-list">
                            <?php foreach ($importLimitLabelMap as $bucketKey => $bucketLabel) : ?>
                            <?php $bucket = is_array($importLimitBuckets[$bucketKey] ?? null) ? $importLimitBuckets[$bucketKey] : []; ?>
                            <?php $bucketRemaining = $bucket['remaining'] ?? null; ?>
                            <dt><?= escape($bucketLabel) ?></dt>
                            <dd><?= is_numeric($bucketRemaining) ? (int) $bucketRemaining . ' restante(s)' : 'Ilimitado' ?>
                            </dd>
                            <?php endforeach; ?>
                        </dl>
                        <a class="imp-link" href="<?= escape($upgradeUrl) ?>">Fazer upgrade</a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="imp-side-section">
                    <div class="imp-side-section__head">
                        <span class="imp-side-section__title">Perfil CSV</span>
                        <span class="imp-status-badge" data-status="preview_ready"
                            data-imp-profile-badge><?= escape($profileCardBadgeLabel) ?></span>
                    </div>
                    <dl class="imp-definition-list imp-definition-list--stack imp-side-definition-list">
                        <div>
                            <dt>Conta</dt>
                            <dd data-imp-profile-account-name><?= escape($activeAccountLabel) ?></dd>
                        </div>
                        <div>
                            <dt>Formato</dt>
                            <dd data-imp-profile-source-type>
                                <?= strtoupper(escape((string) ($profileConfig['source_type'] ?? 'ofx'))) ?></dd>
                        </div>
                        <div>
                            <dt>Modo CSV</dt>
                            <dd data-imp-profile-csv-mode><?= escape($csvMappingModeLabel) ?></dd>
                        </div>
                        <div>
                            <dt>Delimitador</dt>
                            <dd data-imp-profile-csv-delimiter><?= escape($csvDelimiterLabel) ?></dd>
                        </div>
                        <div>
                            <dt>Data</dt>
                            <dd data-imp-profile-csv-date-format><?= escape($csvDateFormatLabel) ?></dd>
                        </div>
                        <div>
                            <dt>Decimal</dt>
                            <dd data-imp-profile-csv-decimal><?= escape($csvDecimalLabel) ?></dd>
                        </div>
                    </dl>
                    <p class="imp-muted" data-imp-profile-context-note><?= escape($advancedContextNote) ?></p>
                </section>

                <div class="imp-side-actions">
                    <a class="btn btn-ghost" href="<?= escape($configUrl) ?>" data-imp-config-link>Gerenciar perfil</a>
                    <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes/historico">Ver histórico</a>
                </div>
            </article>

            <article class="imp-side-card imp-side-card--history imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Últimos lotes</p>
                        <h3 class="imp-card-title">Histórico</h3>
                    </div>
                    <span class="imp-status-badge" data-status="processed"
                        data-imp-history-badge><?= $heroProcessedCount ?> processados</span>
                </header>
                <div data-imp-history-content>
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
                        Ainda não há lotes.
                    </p>
                    <?php endif; ?>
                </div>
                <a class="imp-link" href="<?= BASE_URL ?>importacoes/historico">Ver histórico</a>
            </article>
        </aside>
    </div>

    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</section>