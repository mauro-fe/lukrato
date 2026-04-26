<?php

declare(strict_types=1);

$accounts = is_array($accounts ?? null) ? $accounts : [];
$selectedAccountId = (int) ($selectedAccountId ?? 0);
$profileConfig = is_array($profileConfig ?? null) ? $profileConfig : null;
$profileOptions = is_array($profileConfig['options'] ?? null) ? $profileConfig['options'] : [];
$currentSourceType = strtolower(trim((string) ($profileConfig['source_type'] ?? 'ofx')));
if (!in_array($currentSourceType, ['ofx', 'csv'], true)) {
    $currentSourceType = 'ofx';
}
$csvMappingMode = strtolower(trim((string) ($profileOptions['csv_mapping_mode'] ?? 'auto')));
if (!in_array($csvMappingMode, ['auto', 'manual'], true)) {
    $csvMappingMode = 'auto';
}
$csvDelimiter = trim((string) ($profileOptions['csv_delimiter'] ?? ';'));
$csvHasHeader = (bool) ($profileOptions['csv_has_header'] ?? true);
$csvStartRow = (int) ($profileOptions['csv_start_row'] ?? ($csvHasHeader ? 2 : 1));
if ($csvStartRow <= 0) {
    $csvStartRow = $csvHasHeader ? 2 : 1;
}
$csvDateFormat = trim((string) ($profileOptions['csv_date_format'] ?? 'd/m/Y'));
$csvDecimalSeparator = trim((string) ($profileOptions['csv_decimal_separator'] ?? ','));
$csvColumnMap = is_array($profileOptions['csv_column_map'] ?? null) ? $profileOptions['csv_column_map'] : [];
$csvColumnTipo = strtoupper(trim((string) ($csvColumnMap['tipo'] ?? 'A')));
$csvColumnData = strtoupper(trim((string) ($csvColumnMap['data'] ?? 'B')));
$csvColumnDescricao = strtoupper(trim((string) ($csvColumnMap['descricao'] ?? 'C')));
$csvColumnValor = strtoupper(trim((string) ($csvColumnMap['valor'] ?? 'D')));
$csvColumnCategoria = strtoupper(trim((string) ($csvColumnMap['categoria'] ?? 'E')));
$csvColumnSubcategoria = strtoupper(trim((string) ($csvColumnMap['subcategoria'] ?? 'F')));
$csvColumnObservacao = strtoupper(trim((string) ($csvColumnMap['observacao'] ?? 'G')));
$csvColumnIdExterno = strtoupper(trim((string) ($csvColumnMap['id_externo'] ?? 'H')));
$importacoesUrl = BASE_URL . 'importacoes';
if ($selectedAccountId > 0) {
    $importacoesUrl .= '?conta_id=' . $selectedAccountId;
}

$activeAccountName = 'Não definida';
foreach ($accounts as $account) {
    if ((int) ($account['id'] ?? 0) === $selectedAccountId) {
        $activeAccountName = (string) ($account['nome'] ?? 'Conta sem nome');
        break;
    }
}

$summaryContaId = (int) ($profileConfig['conta_id'] ?? $selectedAccountId);
$summarySourceType = (string) ($profileConfig['source_type'] ?? $currentSourceType);
$summaryAgencia = trim((string) ($profileConfig['agencia'] ?? '')) ?: 'Opcional';
$summaryNumeroConta = trim((string) ($profileConfig['numero_conta'] ?? '')) ?: 'Opcional';
$summaryCsvMappingMode = $csvMappingMode === 'manual' ? 'manual' : 'auto';
$summaryCsvDelimiter = $csvDelimiter !== '' ? $csvDelimiter : ';';
$summarySourceTypeLabel = strtoupper($summarySourceType !== '' ? $summarySourceType : $currentSourceType);
$summaryCsvMappingModeLabel = $summaryCsvMappingMode === 'manual' ? 'Manual' : 'Automático';
$summaryCsvHasHeaderLabel = $csvHasHeader ? 'Com cabeçalho' : 'Sem cabeçalho';
$summaryCsvDateFormatLabel = $csvDateFormat !== '' ? $csvDateFormat : 'd/m/Y';
$summaryCsvDecimalLabel = $csvDecimalSeparator !== '' ? $csvDecimalSeparator : ',';
?>

<section class="imp-config-page" data-importacoes-page="configuracoes" data-lk-help-page="importacoes_configuracoes"
    data-imp-active-account-id="<?= $selectedAccountId ?>">
    <header id="impConfigHeroSection"
        class="imp-page-hero imp-page-hero--compact imp-surface surface-card surface-card--interactive surface-card--clip">
        <div class="imp-page-hero__content">
            <p class="imp-page-hero__eyebrow">Importações</p>
            <h1 class="imp-page-hero__title">Configurações por conta</h1>
            <p class="imp-page-hero__lead">
                Ajuste o perfil que o fluxo principal usa para montar preview, confirmar e registrar histórico.
            </p>
            <div class="imp-page-hero__meta-inline imp-page-hero__meta-inline--chips">
                <span class="imp-chip">Conta por perfil</span>
                <span class="imp-chip">OFX e CSV</span>
                <span class="imp-chip">Preview alinhado</span>
            </div>
        </div>

        <aside class="imp-page-hero__aside imp-page-hero__aside--compact">
            <div class="imp-hero-snapshot imp-config-hero-snapshot surface-card">
                <span class="imp-hero-snapshot__eyebrow">Perfil ativo</span>
                <div class="imp-hero-snapshot__grid">
                    <div class="imp-hero-snapshot__item">
                        <span>Conta</span>
                        <strong data-imp-account-name-label><?= escape($activeAccountName) ?></strong>
                    </div>
                    <div class="imp-hero-snapshot__item">
                        <span>Conta ID</span>
                        <strong data-imp-config-selected-account>
                            <?= $selectedAccountId > 0 ? $selectedAccountId : 'Não definida' ?></strong>
                    </div>
                    <div class="imp-hero-snapshot__item">
                        <span>Origem</span>
                        <strong data-imp-source-type-label><?= escape($summarySourceTypeLabel) ?></strong>
                    </div>
                    <div class="imp-hero-snapshot__item">
                        <span>Modo CSV</span>
                        <strong data-imp-csv-mapping-mode-label><?= escape($summaryCsvMappingModeLabel) ?></strong>
                    </div>
                </div>
                <div class="imp-page-hero__actions">
                    <a class="btn btn-ghost" href="<?= escape($importacoesUrl) ?>"
                        data-imp-config-importacoes-link>Voltar
                        para importações</a>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>importacoes/historico">Ir para histórico</a>
                </div>
            </div>
        </aside>
    </header>

    <?php if ($accounts === []) : ?>
    <article class="imp-empty-card imp-surface surface-card">
        <span class="imp-empty-card__icon" aria-hidden="true">
            <i data-lucide="wallet"></i>
        </span>
        <h3 class="imp-card-title">Nenhuma conta ativa encontrada</h3>
        <p class="imp-card-text">
            Crie uma conta em Finanças > Contas para liberar a configuração de importações.
        </p>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>contas">Criar contas +</a>
    </article>
    <?php else : ?>
    <div class="imp-config-layout">
        <div class="imp-config-main">
            <article
                class="imp-config-card imp-config-card--context imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Conta de trabalho</p>
                        <h3 class="imp-card-title">Trocar contexto do perfil</h3>
                        <p class="imp-card-text">
                            Selecione a conta que alimenta preview, confirmação e histórico nesse fluxo.
                        </p>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Conta ativa</span>
                </header>

                <div class="imp-config-context-grid">
                    <article class="imp-config-context-note surface-card">
                        <span class="imp-config-context-note__eyebrow">Aplicado agora</span>
                        <strong class="imp-config-context-note__title"
                            data-imp-account-name-label><?= escape($activeAccountName) ?></strong>
                        <p class="imp-config-context-note__copy">
                            O preview, a confirmação e o histórico desta conta passam a usar este perfil.
                        </p>
                    </article>

                    <form class="imp-config-account-form" method="get" action="<?= BASE_URL ?>importacoes/configuracoes"
                        data-imp-config-form>
                        <div class="imp-field">
                            <label class="imp-field__label" for="conta_id">Conta de destino</label>
                            <select class="imp-field__control" id="conta_id" name="conta_id" data-imp-account-select>
                                <?php foreach ($accounts as $account) : ?>
                                <?php $accountId = (int) ($account['id'] ?? 0); ?>
                                <option value="<?= $accountId ?>"
                                    <?= $accountId === $selectedAccountId ? 'selected' : '' ?>>
                                    <?= escape((string) ($account['nome'] ?? 'Conta sem nome')) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Aplicar conta</button>
                    </form>
                </div>
            </article>

            <article
                class="imp-config-card imp-config-card--profile imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Perfil de importação</p>
                        <h3 class="imp-card-title">Salvar configuração da conta</h3>
                        <p class="imp-card-text">
                            Defina como OFX e CSV devem ser lidos para esta conta antes de voltar ao fluxo principal.
                        </p>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Fluxo principal</span>
                </header>

                <form class="imp-config-save-form" data-imp-config-save-form novalidate>
                    <?= function_exists('csrf_input') ? csrf_input('default') : '' ?>
                    <input type="hidden" name="conta_id" value="<?= $selectedAccountId ?>" data-imp-conta-id-input>

                    <section class="imp-config-section surface-card">
                        <header class="imp-card-head imp-config-section__head">
                            <h4 class="imp-card-title">Dados gerais</h4>
                            <p class="imp-card-text">
                                Origem padrão, nome do perfil e referências opcionais da conta.
                            </p>
                        </header>

                        <div class="imp-field-grid">
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-source-type">Origem padrão</label>
                                <select id="imp-source-type" class="imp-field__control" name="source_type"
                                    data-imp-source-type>
                                    <option value="ofx" <?= $currentSourceType === 'ofx' ? 'selected' : '' ?>>OFX
                                    </option>
                                    <option value="csv" <?= $currentSourceType === 'csv' ? 'selected' : '' ?>>CSV
                                    </option>
                                </select>
                            </div>
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-label">Nome do perfil</label>
                                <input id="imp-label" class="imp-field__control" type="text" name="label"
                                    maxlength="100"
                                    value="<?= escape((string) ($profileConfig['label'] ?? 'Perfil base')) ?>"
                                    data-imp-label>
                            </div>
                        </div>

                        <div class="imp-field-grid">
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-agencia">Agência (opcional)</label>
                                <input id="imp-agencia" class="imp-field__control" type="text" name="agencia"
                                    inputmode="numeric" placeholder="Ex: 1234"
                                    value="<?= escape((string) ($profileConfig['agencia'] ?? '')) ?>" data-imp-agencia>
                            </div>
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-numero-conta">Número da conta
                                    (opcional)</label>
                                <input id="imp-numero-conta" class="imp-field__control" type="text" name="numero_conta"
                                    inputmode="numeric" placeholder="Ex: 98765-0"
                                    value="<?= escape((string) ($profileConfig['numero_conta'] ?? '')) ?>"
                                    data-imp-numero-conta>
                            </div>
                        </div>
                    </section>

                    <section class="imp-config-section imp-config-csv-block surface-card">
                        <header class="imp-card-head imp-config-section__head">
                            <h4 class="imp-card-title">Configuração CSV</h4>
                            <p class="imp-card-text">
                                Escolha se o sistema lê pelo cabeçalho ou por coluna/letra e ajuste o formato do
                                arquivo.
                            </p>
                        </header>

                        <fieldset class="imp-mode-switch" aria-label="Modo de mapeamento CSV">
                            <legend class="imp-field__label">Modo do CSV</legend>
                            <label class="imp-mode-switch__item" for="imp-csv-mode-auto">
                                <input id="imp-csv-mode-auto" type="radio" name="csv_mapping_mode" value="auto"
                                    data-imp-csv-mapping-mode <?= $csvMappingMode === 'auto' ? 'checked' : '' ?>>
                                <span>Automático (cabeçalho)</span>
                            </label>
                            <label class="imp-mode-switch__item" for="imp-csv-mode-manual">
                                <input id="imp-csv-mode-manual" type="radio" name="csv_mapping_mode" value="manual"
                                    data-imp-csv-mapping-mode <?= $csvMappingMode === 'manual' ? 'checked' : '' ?>>
                                <span>Manual (coluna/letra)</span>
                            </label>
                        </fieldset>

                        <div class="imp-field-grid">
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-csv-start-row">Linha inicial (base 1)</label>
                                <input id="imp-csv-start-row" class="imp-field__control" type="number"
                                    name="csv_start_row" min="1" step="1" value="<?= $csvStartRow ?>"
                                    data-imp-csv-start-row>
                            </div>
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-csv-delimiter">Delimitador CSV</label>
                                <input id="imp-csv-delimiter" class="imp-field__control" type="text"
                                    name="csv_delimiter" maxlength="3" value="<?= escape($csvDelimiter) ?>"
                                    data-imp-csv-delimiter>
                            </div>
                        </div>

                        <div class="imp-field-grid imp-field-grid--csv-options">
                            <div class="imp-field imp-field--checkbox">
                                <label class="imp-checkbox" for="imp-csv-has-header">
                                    <input id="imp-csv-has-header" type="checkbox" name="csv_has_header" value="1"
                                        data-imp-csv-has-header <?= $csvHasHeader ? 'checked' : '' ?>>
                                    <span>CSV com cabeçalho na primeira linha</span>
                                </label>
                            </div>
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-csv-date-format">Formato de data CSV</label>
                                <input id="imp-csv-date-format" class="imp-field__control" type="text"
                                    name="csv_date_format" maxlength="20" value="<?= escape($csvDateFormat) ?>"
                                    data-imp-csv-date-format>
                            </div>
                            <div class="imp-field">
                                <label class="imp-field__label" for="imp-csv-decimal-separator">Separador decimal
                                    CSV</label>
                                <input id="imp-csv-decimal-separator" class="imp-field__control" type="text"
                                    name="csv_decimal_separator" maxlength="1"
                                    value="<?= escape($csvDecimalSeparator) ?>" data-imp-csv-decimal-separator>
                            </div>
                        </div>

                        <p class="imp-muted imp-config-csv-help">
                            CSV usa apenas coluna/letra, linha inicial, delimitador e cabeçalho. CSV não tem
                            aba/planilha. Em cartão/fatura, a coluna tipo pode ficar vazia quando o sinal do valor
                            já diferenciar compra e estorno.
                        </p>

                        <div class="imp-manual-map" data-imp-csv-manual-fields
                            <?= $csvMappingMode === 'manual' ? '' : 'hidden' ?>>
                            <header class="imp-card-head imp-config-section__head">
                                <h4 class="imp-card-title">Mapeamento manual por coluna</h4>
                                <p class="imp-card-text">
                                    Obrigatórios: data, descrição e valor. Tipo continua obrigatório para conta e
                                    opcional em cartão/fatura quando a direção vier pelo valor.
                                </p>
                            </header>

                            <div class="imp-field-grid imp-field-grid--mapping">
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-tipo">Tipo (opcional para
                                        cartão/fatura)</label>
                                    <input id="imp-csv-column-tipo" class="imp-field__control" type="text"
                                        name="csv_column_tipo" maxlength="8" value="<?= escape($csvColumnTipo) ?>"
                                        data-imp-csv-column-tipo>
                                </div>
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-data">Data (obrigatório)</label>
                                    <input id="imp-csv-column-data" class="imp-field__control" type="text"
                                        name="csv_column_data" maxlength="8" value="<?= escape($csvColumnData) ?>"
                                        data-imp-csv-column-data>
                                </div>
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-descricao">Descrição
                                        (obrigatório)</label>
                                    <input id="imp-csv-column-descricao" class="imp-field__control" type="text"
                                        name="csv_column_descricao" maxlength="8"
                                        value="<?= escape($csvColumnDescricao) ?>" data-imp-csv-column-descricao>
                                </div>
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-valor">Valor
                                        (obrigatório)</label>
                                    <input id="imp-csv-column-valor" class="imp-field__control" type="text"
                                        name="csv_column_valor" maxlength="8" value="<?= escape($csvColumnValor) ?>"
                                        data-imp-csv-column-valor>
                                </div>
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-categoria">Categoria
                                        (opcional)</label>
                                    <input id="imp-csv-column-categoria" class="imp-field__control" type="text"
                                        name="csv_column_categoria" maxlength="8"
                                        value="<?= escape($csvColumnCategoria) ?>" data-imp-csv-column-categoria>
                                </div>
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-subcategoria">Subcategoria
                                        (opcional)</label>
                                    <input id="imp-csv-column-subcategoria" class="imp-field__control" type="text"
                                        name="csv_column_subcategoria" maxlength="8"
                                        value="<?= escape($csvColumnSubcategoria) ?>" data-imp-csv-column-subcategoria>
                                </div>
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-observacao">Observação
                                        (opcional)</label>
                                    <input id="imp-csv-column-observacao" class="imp-field__control" type="text"
                                        name="csv_column_observacao" maxlength="8"
                                        value="<?= escape($csvColumnObservacao) ?>" data-imp-csv-column-observacao>
                                </div>
                                <div class="imp-field">
                                    <label class="imp-field__label" for="imp-csv-column-id-externo">ID externo
                                        (opcional)</label>
                                    <input id="imp-csv-column-id-externo" class="imp-field__control" type="text"
                                        name="csv_column_id_externo" maxlength="8"
                                        value="<?= escape($csvColumnIdExterno) ?>" data-imp-csv-column-id-externo>
                                </div>
                            </div>
                        </div>

                        <details class="imp-config-templates">
                            <summary class="imp-config-templates__summary">
                                <div class="imp-config-templates__summary-head">
                                    <div>
                                        <span class="imp-card-eyebrow">Modelos CSV</span>
                                        <strong class="imp-config-templates__title">Baixar estrutura pronta para conta
                                            ou
                                            fatura</strong>
                                        <p class="imp-config-templates__copy">
                                            Abra só quando precisar exportar um modelo base para preencher fora do
                                            sistema.
                                        </p>
                                    </div>
                                    <span class="imp-config-templates__toggle">Abrir apoio</span>
                                </div>
                            </summary>

                            <div class="imp-config-templates__body">
                                <div class="imp-config-template-grid">
                                    <section class="imp-config-template-group">
                                        <span class="imp-config-template-group__title">Modelos de conta</span>
                                        <div class="imp-template-actions">
                                            <a class="btn btn-ghost" href="#" data-imp-csv-template-auto
                                                data-no-transition="true" download>
                                                Baixar modelo CSV automático
                                            </a>
                                            <a class="btn btn-ghost" href="#" data-imp-csv-template-manual
                                                data-no-transition="true" download>
                                                Baixar modelo CSV manual
                                            </a>
                                        </div>
                                    </section>

                                    <section class="imp-config-template-group">
                                        <span class="imp-config-template-group__title">Modelos de cartão/fatura</span>
                                        <div class="imp-template-actions">
                                            <a class="btn btn-ghost" href="#" data-imp-csv-template-card-auto
                                                data-no-transition="true" download>
                                                Baixar modelo fatura automático
                                            </a>
                                            <a class="btn btn-ghost" href="#" data-imp-csv-template-card-manual
                                                data-no-transition="true" download>
                                                Baixar modelo fatura manual
                                            </a>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </details>
                    </section>

                    <div class="imp-save-actions">
                        <button class="btn btn-secondary" type="submit" data-imp-save-button>
                            Salvar configuração
                        </button>
                        <p class="imp-muted" data-imp-config-save-feedback>
                            Esse perfil é reutilizado no fluxo principal sempre que a conta for selecionada.
                        </p>
                    </div>
                </form>
            </article>
        </div>

        <aside class="imp-config-side" id="impConfigSideSection">
            <article
                class="imp-config-card imp-config-side-card imp-config-side-card--summary imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Resumo do perfil</p>
                        <h3 class="imp-card-title">Perfil atual</h3>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Resumo</span>
                </header>

                <dl class="imp-definition-list imp-definition-list--stack imp-config-side-summary"
                    data-imp-profile-summary>
                    <div class="surface-card">
                        <dt>Conta ID</dt>
                        <dd data-imp-summary-conta-id><?= $summaryContaId ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Origem</dt>
                        <dd data-imp-summary-source-type><?= escape($summarySourceTypeLabel) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Modo CSV</dt>
                        <dd data-imp-summary-csv-mapping-mode><?= escape($summaryCsvMappingModeLabel) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Cabeçalho</dt>
                        <dd data-imp-summary-csv-has-header><?= escape($summaryCsvHasHeaderLabel) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Delimitador</dt>
                        <dd data-imp-summary-csv-delimiter><?= escape($summaryCsvDelimiter) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Linha inicial</dt>
                        <dd data-imp-summary-csv-start-row><?= $csvStartRow ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Formato data</dt>
                        <dd data-imp-summary-csv-date-format><?= escape($summaryCsvDateFormatLabel) ?></dd>
                    </div>
                    <div class="surface-card">
                        <dt>Decimal</dt>
                        <dd data-imp-summary-csv-decimal><?= escape($summaryCsvDecimalLabel) ?></dd>
                    </div>
                    <div class="surface-card imp-config-side-summary__item--full">
                        <dt>Agência</dt>
                        <dd data-imp-summary-agencia><?= escape($summaryAgencia) ?></dd>
                    </div>
                    <div class="surface-card imp-config-side-summary__item--full">
                        <dt>Número da conta</dt>
                        <dd data-imp-summary-numero-conta><?= escape($summaryNumeroConta) ?></dd>
                    </div>
                </dl>
            </article>

            <article
                class="imp-config-card imp-config-side-card imp-config-side-card--flow imp-surface surface-card surface-card--interactive">
                <header class="imp-card-head imp-card-head--split">
                    <div>
                        <p class="imp-card-eyebrow">Conexão</p>
                        <h3 class="imp-card-title">Conexão com o fluxo principal</h3>
                        <p class="imp-card-text">
                            Este perfil é reutilizado no momento em que o usuário prepara o lote.
                        </p>
                    </div>
                    <span class="imp-status-badge" data-status="preview_ready">Fluxo</span>
                </header>

                <ul class="imp-config-flow-list">
                    <li>
                        <strong>Preview</strong>
                        <span>Usa origem padrão, cabeçalho, delimitador e linha inicial desta conta.</span>
                    </li>
                    <li>
                        <strong>Confirmação</strong>
                        <span>Reaproveita o perfil salvo para validar e persistir o lote com menos retrabalho.</span>
                    </li>
                    <li>
                        <strong>Histórico</strong>
                        <span>Mantém o lote vinculado à mesma conta usada no momento da importação.</span>
                    </li>
                </ul>

                <div class="imp-config-side__actions">
                    <a class="btn btn-primary" href="<?= escape($importacoesUrl) ?>"
                        data-imp-config-importacoes-link>Abrir fluxo de importação</a>
                    <a class="btn btn-ghost" href="<?= BASE_URL ?>importacoes/historico">Abrir histórico</a>
                </div>
            </article>
        </aside>
    </div>
    <?php endif; ?>
</section>