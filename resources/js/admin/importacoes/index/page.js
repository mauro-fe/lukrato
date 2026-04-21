import {
    bootImportacoesPage,
    normalizeSourceType,
} from '../app.js';
import { createImportacoesIndexApi } from '../api/index.js';
import { initCustomize } from '../customize.js';
import {
    createEmptyPreviewSummary,
    detectImportTargetFromOfxContents,
    detectSourceTypeFromFile,
    formatImportTargetLabel,
    isTruthyFlag,
    normalizeImportTarget,
    normalizeMessages,
    normalizeProfileConfig,
    parsePositiveInt,
    readTextFromFile,
    summarizeRows,
} from './helpers.js';
import {
    buildAdvancedDescription,
    buildContextGuide,
    buildFileNote,
    buildPathGuide,
    buildReadinessGuide,
    buildTemplateMeta,
    renderProfileDisplay,
} from './guides.js';
import { createImportacoesPreviewManager } from './preview.js';

const STATUS_MESSAGES = {
    idle: 'Selecione alvo, formato e arquivo para montar o preview.',
    file_selected: 'Arquivo selecionado. Clique em "Preparar preview".',
    loading_preview: 'Preparando preview e validando o conteudo do arquivo.',
    preview_ready: 'Preview pronto. Revise as linhas, categorize se quiser e confirme.',
    preview_error: 'Nao foi possivel preparar o preview. Revise os dados e tente novamente.',
    confirming: 'Seu arquivo esta sendo importado...',
    confirmed: 'Concluido.',
};

const STATUS_BADGES = {
    idle: 'Aguardando arquivo',
    file_selected: 'Arquivo selecionado',
    loading_preview: 'Carregando preview',
    preview_ready: 'Preview pronto',
    preview_error: 'Erro no preview',
    confirming: 'Confirmando',
    confirmed: 'Confirmado',
};

const HISTORY_STATUS_LABELS = {
    processing: 'Processando',
    processed: 'Processado',
    processed_with_duplicates: 'Com duplicados',
    processed_duplicates_only: 'Somente duplicados',
    processed_with_errors: 'Com erros',
    failed: 'Falhou',
};

const HISTORY_TARGET_LABELS = {
    conta: 'Conta',
    cartao: 'Cartão',
};

const IMPORT_LIMIT_LABELS = {
    import_conta_ofx: 'OFX de conta',
    import_conta_csv: 'CSV de lançamentos',
    import_cartao_ofx: 'Fatura/cartão (OFX ou CSV)',
};

export function initImportacoesIndexPage() {
    const context = bootImportacoesPage('index');

    if (context) {
        initCustomize();

        const sourceInputs = Array.from(context.root.querySelectorAll('[data-imp-source-type]'));
        const targetInputs = Array.from(context.root.querySelectorAll('[data-imp-target-type]'));
        const accountField = context.root.querySelector('[data-imp-account-field]');
        const accountSelect = context.root.querySelector('[data-imp-account-select-main]');
        const accountWarning = context.root.querySelector('[data-imp-account-warning]');
        const accountLink = context.root.querySelector('[data-imp-account-link]');
        const cardField = context.root.querySelector('[data-imp-card-field]');
        const cardSelect = context.root.querySelector('[data-imp-card-select-main]');
        const cardWarning = context.root.querySelector('[data-imp-card-warning]');
        const cardLink = context.root.querySelector('[data-imp-card-link]');
        const targetSourceHint = context.root.querySelector('[data-imp-target-source-hint]');
        const heroContextLabel = context.root.querySelector('[data-imp-hero-context-label]');
        const heroSourceLabel = context.root.querySelector('[data-imp-hero-source-label]');
        const heroBatchCount = context.root.querySelector('[data-imp-hero-batch-count]');
        const heroPendingCount = context.root.querySelector('[data-imp-hero-pending-count]');
        const flowStepSetup = context.root.querySelector('[data-imp-flow-step="setup"]');
        const flowStepSetupCopy = context.root.querySelector('[data-imp-flow-step-copy="setup"]');
        const flowStepFile = context.root.querySelector('[data-imp-flow-step="file"]');
        const flowStepFileCopy = context.root.querySelector('[data-imp-flow-step-copy="file"]');
        const flowStepPreview = context.root.querySelector('[data-imp-flow-step="preview"]');
        const flowStepPreviewCopy = context.root.querySelector('[data-imp-flow-step-copy="preview"]');
        const fileDrop = context.root.querySelector('[data-imp-file-drop]');
        const fileInput = context.root.querySelector('[data-imp-file-input]');
        const fileStageBadge = context.root.querySelector('[data-imp-file-stage-badge]');
        const selectedFileLabel = context.root.querySelector('[data-imp-selected-file]');
        const fileNote = context.root.querySelector('[data-imp-file-note]');
        const submitButton = context.root.querySelector('[data-imp-submit]');
        const previewState = context.root.querySelector('[data-imp-preview-state]');
        const previewBadge = context.root.querySelector('[data-imp-preview-badge]');
        const previewOverview = context.root.querySelector('[data-imp-preview-overview]');
        const previewTarget = context.root.querySelector('[data-imp-preview-target]');
        const previewContextLabel = context.root.querySelector('[data-imp-preview-context-label]');
        const previewSourceType = context.root.querySelector('[data-imp-preview-source-type]');
        const previewFileName = context.root.querySelector('[data-imp-preview-file-name]');
        const previewReadinessBadge = context.root.querySelector('[data-imp-preview-readiness-badge]');
        const previewReadinessTitle = context.root.querySelector('[data-imp-preview-readiness-title]');
        const previewReadinessCopy = context.root.querySelector('[data-imp-preview-readiness-copy]');
        const previewWarningChip = context.root.querySelector('[data-imp-preview-warning-chip]');
        const previewErrorChip = context.root.querySelector('[data-imp-preview-error-chip]');
        const previewPendingChip = context.root.querySelector('[data-imp-preview-pending-chip]');
        const previewTotalRows = context.root.querySelector('[data-imp-preview-total-rows]');
        const previewCategorized = context.root.querySelector('[data-imp-preview-categorized]');
        const previewUncategorized = context.root.querySelector('[data-imp-preview-uncategorized]');
        const previewUserRuleSuggested = context.root.querySelector('[data-imp-preview-user-rule-suggested]');
        const previewGlobalRuleSuggested = context.root.querySelector('[data-imp-preview-global-rule-suggested]');
        const previewWarnings = context.root.querySelector('[data-imp-preview-warnings]');
        const previewErrors = context.root.querySelector('[data-imp-preview-errors]');
        const previewEmpty = context.root.querySelector('[data-imp-preview-empty]');
        const previewEmptyTitle = context.root.querySelector('[data-imp-preview-empty-title]');
        const previewEmptyCopy = context.root.querySelector('[data-imp-preview-empty-copy]');
        const previewTools = context.root.querySelector('[data-imp-preview-tools]');
        const previewTableWrap = context.root.querySelector('[data-imp-preview-table-wrap]');
        const previewRowsBody = context.root.querySelector('[data-imp-preview-rows]');
        const previewFooter = context.root.querySelector('[data-imp-preview-footer]');
        const confirmTitle = context.root.querySelector('[data-imp-confirm-title]');
        const confirmStrip = context.root.querySelector('[data-imp-confirm-strip]');
        const confirmCheckContext = context.root.querySelector('[data-imp-confirm-check="context"]');
        const confirmCheckReview = context.root.querySelector('[data-imp-confirm-check="review"]');
        const confirmCheckConfirm = context.root.querySelector('[data-imp-confirm-check="confirm"]');
        const confirmCopyContext = context.root.querySelector('[data-imp-confirm-copy="context"]');
        const confirmCopyReview = context.root.querySelector('[data-imp-confirm-copy="review"]');
        const confirmCopyConfirm = context.root.querySelector('[data-imp-confirm-copy="confirm"]');
        const previewNextStep = context.root.querySelector('[data-imp-preview-next-step]');
        const categorizePreviewButton = context.root.querySelector('[data-imp-categorize-preview]');
        const categorizePreviewHelper = context.root.querySelector('[data-imp-categorize-helper]');
        const confirmButton = context.root.querySelector('[data-imp-confirm]');
        const pendingOnlyToggle = context.root.querySelector('[data-imp-filter-pending-only]');
        const advancedPanel = context.root.querySelector('[data-imp-advanced-panel]');
        const advancedDetails = context.root.querySelector('[data-imp-advanced-details]');
        const advancedDescription = context.root.querySelector('[data-imp-advanced-description]');
        const advancedSummaryCopy = context.root.querySelector('[data-imp-advanced-summary-copy]');
        const advancedModeBadge = context.root.querySelector('[data-imp-advanced-mode-badge]');
        const advancedTemplateChip = context.root.querySelector('[data-imp-advanced-template-chip]');
        const advancedTemplateTitle = context.root.querySelector('[data-imp-advanced-template-title]');
        const advancedTemplateCopy = context.root.querySelector('[data-imp-advanced-template-copy]');
        const advancedLinkedAccountNote = context.root.querySelector('[data-imp-advanced-linked-account-note]');
        const advancedTemplateAutoLink = context.root.querySelector('[data-imp-advanced-template-auto]');
        const advancedTemplateManualLink = context.root.querySelector('[data-imp-advanced-template-manual]');
        const advancedSummaryContext = context.root.querySelector('[data-imp-advanced-summary-context]');
        const advancedAccountName = context.root.querySelector('[data-imp-advanced-account-name]');
        const advancedSourceType = context.root.querySelector('[data-imp-advanced-source-type]');
        const advancedMappingMode = context.root.querySelector('[data-imp-advanced-mapping-mode]');
        const advancedHasHeader = context.root.querySelector('[data-imp-advanced-has-header]');
        const advancedStartRow = context.root.querySelector('[data-imp-advanced-start-row]');
        const advancedDelimiter = context.root.querySelector('[data-imp-advanced-delimiter]');
        const advancedDateFormat = context.root.querySelector('[data-imp-advanced-date-format]');
        const advancedDecimal = context.root.querySelector('[data-imp-advanced-decimal]');
        const advancedColumnMap = context.root.querySelector('[data-imp-advanced-column-map]');
        const profileBadge = context.root.querySelector('[data-imp-profile-badge]');
        const profileAccountName = context.root.querySelector('[data-imp-profile-account-name]');
        const profileSourceType = context.root.querySelector('[data-imp-profile-source-type]');
        const profileCsvMode = context.root.querySelector('[data-imp-profile-csv-mode]');
        const profileCsvDelimiter = context.root.querySelector('[data-imp-profile-csv-delimiter]');
        const profileCsvDateFormat = context.root.querySelector('[data-imp-profile-csv-date-format]');
        const profileCsvDecimal = context.root.querySelector('[data-imp-profile-csv-decimal]');
        const profileContextNote = context.root.querySelector('[data-imp-profile-context-note]');
        const planBadge = context.root.querySelector('[data-imp-plan-badge]');
        const planSummary = context.root.querySelector('[data-imp-plan-summary]');
        const historyBadge = context.root.querySelector('[data-imp-history-badge]');
        const historyContent = context.root.querySelector('[data-imp-history-content]');
        const configLinks = Array.from(context.root.querySelectorAll('[data-imp-config-link]'));
        const guidePathCard = context.root.querySelector('[data-imp-guide-path-card]');
        const guidePathTitle = context.root.querySelector('[data-imp-guide-path-title]');
        const guidePathCopy = context.root.querySelector('[data-imp-guide-path-copy]');
        const guideContextCard = context.root.querySelector('[data-imp-guide-context-card]');
        const guideContextTitle = context.root.querySelector('[data-imp-guide-context-title]');
        const guideContextCopy = context.root.querySelector('[data-imp-guide-context-copy]');
        const guideReadinessCard = context.root.querySelector('[data-imp-guide-readiness-card]');
        const guideReadinessTitle = context.root.querySelector('[data-imp-guide-readiness-title]');
        const guideReadinessCopy = context.root.querySelector('[data-imp-guide-readiness-copy]');
        const form = context.root.querySelector('#imp-upload-form');
        const quotaWarning = context.root.querySelector('[data-imp-quota-warning]');
        const configPageBaseUrl = String(context.root.dataset.impConfigPageBaseUrl || '').trim();
        const confirmAsyncDefault = isTruthyFlag(context.root.dataset.impConfirmAsyncDefault || '0');
        const api = createImportacoesIndexApi({ form, scope: context.root });

        let activeJobId = null;
        let latestHistoryItems = null;
        let pollingTimer = null;
        let fileDropDragDepth = 0;
        let fileInspectionToken = 0;
        let pageInitRequestToken = 0;
        let profileRequestToken = 0;
        let profileLoadError = '';
        let profileLoadState = 'idle';
        let planLimitsRequest = null;
        let planTier = '';
        let upgradeUrl = '/assinatura';

        let importLimitsByBucket = null;
        let activeProfileConfig = null;
        const profileCache = new Map();

        const previewManager = createImportacoesPreviewManager({
            previewRowsBody,
            isContaOfxPreviewActive: () => isContaOfxPreviewActive(),
            loadCategories: () => api.loadCategories(),
            loadSubcategories: (categoriaId) => api.loadSubcategories(categoriaId),
            onPreviewRowUpdate,
        });

        const {
            syncPreviewRowState,
            normalizeRows,
            ensureCategoryOptionsLoaded,
            prefetchSubcategoryOptions,
            clearPreviewRows,
            renderPreviewRows,
            mergeSuggestedRowsIntoPreview,
            buildRowOverrides,
            setCategoryCatalogError,
            clearCategoryCatalogError,
            hasCategory,
        } = previewManager;

        context.onStateChange(renderCurrentState);

        context.setState({
            selectedImportTarget: currentImportTarget(),
            selectedSourceType: currentSourceType(),
            selectedAccountId: resolveSelectedAccountId(),
            selectedCardId: resolveSelectedCardId(),
            previewSummary: createEmptyPreviewSummary(),
        });

        renderCurrentState(context.state);
        void hydratePageInit();

        sourceInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!input.checked) {
                    return;
                }

                resetPreviewState({
                    selectedSourceType: normalizeSourceType(input.value, 'ofx'),
                    sourceAutoAdjustedToDetectedFile: false,
                });
                void hydratePageInit({
                    sourceType: normalizeSourceType(input.value, 'ofx'),
                });
            });
        });

        targetInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!input.checked) {
                    return;
                }

                const nextImportTarget = normalizeImportTarget(input.value, 'conta');
                const nextPatch = {
                    selectedImportTarget: nextImportTarget,
                    targetAutoAdjustedToDetectedFile: false,
                };

                if (nextImportTarget === 'cartao') {
                    nextPatch.selectedCardId = resolveSelectedCardId();
                } else {
                    nextPatch.selectedAccountId = resolveSelectedAccountId();
                }

                resetPreviewState(nextPatch);
                void ensureProfileConfigLoaded(resolveActiveConfigAccountId(nextImportTarget, nextPatch));
                void hydratePageInit({
                    importTarget: nextImportTarget,
                    accountId: nextPatch.selectedAccountId,
                    cardId: nextPatch.selectedCardId,
                });
            });
        });

        accountSelect?.addEventListener('change', () => {
            resetPreviewState({
                selectedAccountId: resolveSelectedAccountId(),
            });
            void ensureProfileConfigLoaded(resolveActiveConfigAccountId('conta'));
            void hydratePageInit({
                importTarget: 'conta',
                accountId: resolveSelectedAccountId(),
            });
        });

        cardSelect?.addEventListener('change', () => {
            resetPreviewState({
                selectedCardId: resolveSelectedCardId(),
            });
            void ensureProfileConfigLoaded(resolveActiveConfigAccountId('cartao'));
            void hydratePageInit({
                importTarget: 'cartao',
                cardId: resolveSelectedCardId(),
            });
        });

        fileInput?.addEventListener('change', async () => {
            const file = fileInput.files?.[0] || null;
            await inspectSelectedFile(file);
        });

        bindFileDropEvents();

        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await handlePreviewRequest();
        });

        categorizePreviewButton?.addEventListener('click', async () => {
            await handleCategorizePreview();
        });

        pendingOnlyToggle?.addEventListener('change', () => {
            context.setState({
                showOnlyPendingCategories: pendingOnlyToggle.checked,
            });
        });

        confirmButton?.addEventListener('click', async () => {
            await handleConfirm();
        });

        window.addEventListener('beforeunload', stopJobPolling);

        function currentSourceType() {
            const checked = sourceInputs.find((input) => input.checked);
            return normalizeSourceType(checked?.value || context.state.selectedSourceType || 'ofx', 'ofx');
        }

        function currentImportTarget() {
            const checked = targetInputs.find((input) => input.checked);
            return normalizeImportTarget(checked?.value || context.state.selectedImportTarget || 'conta', 'conta');
        }

        function resolveSelectedAccountId() {
            return parsePositiveInt(accountSelect?.value ?? context.state.selectedAccountId ?? null);
        }

        function resolveSelectedCardId() {
            return parsePositiveInt(cardSelect?.value ?? context.state.selectedCardId ?? null);
        }

        function findAccountOptionById(accountId) {
            const normalizedAccountId = parsePositiveInt(accountId);
            if (!accountSelect || !normalizedAccountId) {
                return null;
            }

            return Array.from(accountSelect.options).find(
                (option) => parsePositiveInt(option.value) === normalizedAccountId,
            ) || null;
        }

        function findCardOptionById(cardId) {
            const normalizedCardId = parsePositiveInt(cardId);
            if (!cardSelect || !normalizedCardId) {
                return null;
            }

            return Array.from(cardSelect.options).find(
                (option) => parsePositiveInt(option.value) === normalizedCardId,
            ) || null;
        }

        function resolveCardLinkedAccountId(cardId = context.state.selectedCardId) {
            const option = findCardOptionById(cardId) || (cardSelect?.selectedIndex >= 0 ? cardSelect.options[cardSelect.selectedIndex] : null);
            return parsePositiveInt(option?.dataset?.linkedAccountId ?? null);
        }

        function resolveActiveConfigAccountId(importTarget = context.state.selectedImportTarget, overrides = {}) {
            if (normalizeImportTarget(importTarget, 'conta') === 'cartao') {
                return resolveCardLinkedAccountId(overrides.selectedCardId ?? context.state.selectedCardId);
            }

            return parsePositiveInt(overrides.selectedAccountId ?? accountSelect?.value ?? context.state.selectedAccountId ?? null);
        }

        function resolveActiveConfigAccountLabel() {
            const accountId = resolveActiveConfigAccountId();
            const option = findAccountOptionById(accountId);
            if (option) {
                return String(option.text || `Conta #${accountId}`);
            }

            return accountId ? `Conta #${accountId}` : 'Conta nao selecionada';
        }

        function currentAccountLabel() {
            if (!accountSelect || accountSelect.selectedIndex < 0) {
                return 'Conta nao selecionada';
            }

            return String(accountSelect.options[accountSelect.selectedIndex]?.text || 'Conta nao selecionada');
        }

        function currentCardLabel() {
            if (!cardSelect || cardSelect.selectedIndex < 0) {
                return 'Cartao nao selecionado';
            }

            return String(cardSelect.options[cardSelect.selectedIndex]?.text || 'Cartao nao selecionado');
        }

        function currentContextLabel() {
            return isCardTarget() ? currentCardLabel() : currentAccountLabel();
        }

        function currentTargetLabel(importTarget = context.state.selectedImportTarget) {
            return formatImportTargetLabel(importTarget);
        }

        function isCardTarget(importTarget = context.state.selectedImportTarget) {
            return normalizeImportTarget(importTarget, 'conta') === 'cartao';
        }

        function isContextSelected(importTarget = context.state.selectedImportTarget) {
            if (isCardTarget(importTarget)) {
                return Boolean(parsePositiveInt(context.state.selectedCardId ?? null));
            }

            return Boolean(parsePositiveInt(context.state.selectedAccountId ?? null));
        }

        function isContaOfxPreviewActive(
            importTarget = context.state.selectedImportTarget,
            sourceType = context.state.selectedSourceType,
        ) {
            return normalizeImportTarget(importTarget, 'conta') === 'conta'
                && normalizeSourceType(sourceType, 'ofx') === 'ofx';
        }

        function resolveQuotaBucket(importTarget, sourceType) {
            if (normalizeImportTarget(importTarget, 'conta') === 'cartao') {
                return 'import_cartao_ofx';
            }

            return normalizeSourceType(sourceType, 'ofx') === 'csv'
                ? 'import_conta_csv'
                : 'import_conta_ofx';
        }

        function currentQuota() {
            const normalizedPlanTier = String(planTier || '').trim().toLowerCase();
            if (normalizedPlanTier === '') {
                return {
                    allowed: true,
                    limit: null,
                    used: null,
                    remaining: null,
                    bucket: null,
                    message: '',
                };
            }

            if (normalizedPlanTier !== 'free') {
                return {
                    allowed: true,
                    limit: null,
                    used: null,
                    remaining: null,
                    bucket: null,
                    message: '',
                };
            }

            const bucket = resolveQuotaBucket(context.state.selectedImportTarget, context.state.selectedSourceType);
            const quota = importLimitsByBucket?.[bucket];
            if (!quota || typeof quota !== 'object') {
                return {
                    allowed: true,
                    limit: null,
                    used: null,
                    remaining: null,
                    bucket,
                    message: '',
                };
            }

            return {
                allowed: quota.allowed !== false,
                limit: Number.isFinite(Number(quota.limit)) ? Number(quota.limit) : quota.limit ?? null,
                used: Number.isFinite(Number(quota.used)) ? Number(quota.used) : quota.used ?? null,
                remaining: Number.isFinite(Number(quota.remaining)) ? Number(quota.remaining) : quota.remaining ?? null,
                bucket,
                message: String(quota.message || '').trim(),
            };
        }

        function markCurrentBucketAsUsed() {
            const bucket = resolveQuotaBucket(context.state.selectedImportTarget, context.state.selectedSourceType);
            const quota = importLimitsByBucket?.[bucket];
            if (!quota || typeof quota !== 'object') {
                return;
            }

            const limit = Number(quota.limit);
            const used = Number(quota.used ?? 0);
            if (!Number.isFinite(limit)) {
                return;
            }

            const nextUsed = used + 1;
            quota.used = nextUsed;
            quota.remaining = Math.max(0, limit - nextUsed);
            quota.allowed = quota.remaining > 0;
        }

        function normalizeDetectedImportTarget(value) {
            const normalized = String(value || '').trim().toLowerCase();
            return normalized === 'cartao' || normalized === 'conta' ? normalized : '';
        }

        function normalizePreviewSummaryState(summary = {}, fileName = null) {
            const normalizedSummary = summary && typeof summary === 'object' ? summary : {};
            const resolvedFileName = fileName ?? normalizedSummary.fileName ?? context.state.selectedFile?.name ?? '';

            return {
                ...createEmptyPreviewSummary(resolvedFileName),
                ...normalizedSummary,
            };
        }

        function mergePreviewSummary(summaryPatch = {}, fileName = null) {
            return normalizePreviewSummaryState({
                ...normalizePreviewSummaryState(context.state.previewSummary, fileName),
                ...(summaryPatch && typeof summaryPatch === 'object' ? summaryPatch : {}),
            }, fileName);
        }

        function resolveConfigPageUrl(importTarget = context.state.selectedImportTarget, overrides = {}) {
            const baseUrl = configPageBaseUrl || 'importacoes/configuracoes';
            const accountId = resolveActiveConfigAccountId(importTarget, overrides);

            if (!accountId) {
                return baseUrl;
            }

            const separator = baseUrl.includes('?') ? '&' : '?';
            return `${baseUrl}${separator}conta_id=${encodeURIComponent(String(accountId))}`;
        }

        function buildPageInitParams(overrides = {}) {
            return {
                import_target: normalizeImportTarget(
                    overrides.importTarget ?? context.state.selectedImportTarget,
                    'conta',
                ),
                source_type: normalizeSourceType(
                    overrides.sourceType ?? context.state.selectedSourceType,
                    'ofx',
                ),
                conta_id: parsePositiveInt(
                    overrides.accountId ?? accountSelect?.value ?? context.state.selectedAccountId ?? null,
                ),
                cartao_id: parsePositiveInt(
                    overrides.cardId ?? cardSelect?.value ?? context.state.selectedCardId ?? null,
                ),
            };
        }

        function replaceSelectOptions(select, items, selectedId, getLabel, getDataset = () => ({})) {
            if (!(select instanceof HTMLSelectElement)) {
                return;
            }

            const nextItems = Array.isArray(items) ? items : [];
            const preferredId = parsePositiveInt(selectedId ?? null);
            const fragment = document.createDocumentFragment();

            nextItems.forEach((item) => {
                const optionId = parsePositiveInt(item?.id ?? null);
                if (!optionId) {
                    return;
                }

                const option = document.createElement('option');
                option.value = String(optionId);
                option.textContent = getLabel(item);

                Object.entries(getDataset(item)).forEach(([key, value]) => {
                    if (value === '' || value === null || value === undefined) {
                        return;
                    }

                    option.dataset[key] = String(value);
                });

                if (preferredId && optionId === preferredId) {
                    option.selected = true;
                }

                fragment.appendChild(option);
            });

            select.replaceChildren(fragment);

            if (select.options.length === 0) {
                return;
            }

            const hasPreferred = preferredId
                ? Array.from(select.options).some((option) => parsePositiveInt(option.value) === preferredId)
                : false;

            select.value = hasPreferred
                ? String(preferredId)
                : String(select.options[0].value || '');
        }

        function populateAccountOptions(accounts, selectedAccountId) {
            replaceSelectOptions(
                accountSelect,
                accounts,
                selectedAccountId,
                (account) => String(account?.nome || 'Conta sem nome'),
            );
        }

        function populateCardOptions(cards, selectedCardId) {
            replaceSelectOptions(
                cardSelect,
                cards,
                selectedCardId,
                (card) => String(card?.nome || 'Cartão sem nome'),
                (card) => ({
                    linkedAccountId: parsePositiveInt(card?.conta_id ?? null) || '',
                }),
            );
        }

        function createStatusBadge(status, label) {
            const badge = document.createElement('span');
            badge.className = 'imp-status-badge';
            badge.dataset.status = String(status || 'idle');
            badge.textContent = String(label || '');
            return badge;
        }

        function computeHistoryMetrics(items = []) {
            return (Array.isArray(items) ? items : []).reduce((summary, item) => {
                summary.batches += 1;

                const status = String(item?.status || '').trim().toLowerCase();
                if (status === 'processing') {
                    summary.pending += 1;
                } else if (status !== '') {
                    summary.processed += 1;
                }

                return summary;
            }, {
                batches: 0,
                pending: 0,
                processed: 0,
            });
        }

        function renderHeroSummary() {
            if (heroContextLabel) {
                heroContextLabel.textContent = currentContextLabel();
            }

            if (!Array.isArray(latestHistoryItems)) {
                return;
            }

            const metrics = computeHistoryMetrics(latestHistoryItems);

            if (heroBatchCount) {
                heroBatchCount.textContent = String(metrics.batches);
            }

            if (heroPendingCount) {
                heroPendingCount.textContent = String(metrics.pending);
            }
        }

        function applyFlowStep(stepElement, copyElement, nextState, nextCopy) {
            if (stepElement) {
                stepElement.dataset.state = String(nextState || 'idle');
            }

            if (copyElement) {
                copyElement.textContent = String(nextCopy || '');
            }
        }

        function renderFlowSteps(state, previewSummary) {
            const setupComplete = isContextSelected(state.selectedImportTarget);
            const hasFileSelected = state.selectedFile instanceof File;
            const previewReady = state.previewStatus === 'preview_ready';
            const previewWarning = state.previewStatus === 'preview_error';
            const previewCompleted = state.previewStatus === 'confirmed';
            const previewBusy = state.previewStatus === 'loading_preview' || state.previewStatus === 'confirming';

            applyFlowStep(
                flowStepSetup,
                flowStepSetupCopy,
                hasFileSelected || previewBusy || previewReady || previewWarning || previewCompleted ? 'complete' : 'active',
                setupComplete
                    ? `${currentTargetLabel()} · ${currentContextLabel()} · ${String(state.selectedSourceType || 'ofx').toUpperCase()}`
                    : 'Escolha conta ou cartão e defina o formato do arquivo.',
            );

            applyFlowStep(
                flowStepFile,
                flowStepFileCopy,
                previewReady || previewCompleted ? 'complete' : (hasFileSelected || previewBusy || previewWarning ? 'active' : 'idle'),
                hasFileSelected
                    ? `Arquivo pronto: ${state.selectedFile.name}`
                    : 'Envie OFX ou CSV para liberar a leitura do preview.',
            );

            let previewStepState = 'idle';
            let previewStepCopy = 'O preview aparece abaixo depois da leitura do arquivo.';

            if (previewBusy) {
                previewStepState = 'active';
                previewStepCopy = state.previewStatus === 'confirming'
                    ? String(state.jobProgressMessage || 'Importando o arquivo...')
                    : 'Preparando preview e validando o conteúdo do arquivo.';
            } else if (previewReady) {
                previewStepState = 'active';
                previewStepCopy = state.previewCanConfirm
                    ? 'Preview pronto. Revise, categorize se quiser e confirme quando estiver seguro.'
                    : 'Preview gerado com bloqueios. Revise as mensagens antes de confirmar.';
            } else if (previewWarning) {
                previewStepState = 'warning';
                previewStepCopy = Array.isArray(state.previewErrors) && state.previewErrors.length > 0
                    ? state.previewErrors[0]
                    : 'O arquivo precisa de ajustes antes da confirmação.';
            } else if (previewCompleted) {
                previewStepState = 'complete';
                previewStepCopy = getCompletedMessage(previewSummary);
            }

            applyFlowStep(flowStepPreview, flowStepPreviewCopy, previewStepState, previewStepCopy);
        }

        function renderFileStageBadge(state) {
            if (!fileStageBadge) {
                return;
            }

            let nextStatus = 'idle';
            let nextLabel = 'Aguardando arquivo';

            if (state.previewStatus === 'loading_preview') {
                nextStatus = 'loading_preview';
                nextLabel = 'Lendo arquivo';
            } else if (state.previewStatus === 'preview_ready') {
                nextStatus = 'preview_ready';
                nextLabel = 'Preview pronto';
            } else if (state.previewStatus === 'preview_error') {
                nextStatus = 'preview_error';
                nextLabel = 'Revisar arquivo';
            } else if (state.previewStatus === 'confirming') {
                nextStatus = 'confirming';
                nextLabel = 'Confirmando';
            } else if (state.previewStatus === 'confirmed') {
                nextStatus = 'confirmed';
                nextLabel = 'Concluído';
            } else if (state.selectedFile instanceof File) {
                nextStatus = 'file_selected';
                nextLabel = 'Arquivo pronto';
            }

            fileStageBadge.dataset.status = nextStatus;
            fileStageBadge.textContent = nextLabel;
        }

        function setPreviewChip(element, label, tone = 'neutral') {
            if (!element) {
                return;
            }

            element.dataset.tone = tone;
            element.textContent = String(label || '');
        }

        function applyConfirmCheck(element, copyElement, nextState, nextCopy) {
            if (element) {
                element.dataset.state = String(nextState || 'idle');
            }

            if (copyElement) {
                copyElement.textContent = String(nextCopy || '');
            }
        }

        function renderPreviewDecisionState(state, previewSummary, quota) {
            const warningsCount = Array.isArray(state.previewWarnings) ? state.previewWarnings.length : 0;
            const errorsCount = Array.isArray(state.previewErrors) ? state.previewErrors.length : 0;
            const pendingCount = Number(previewSummary.uncategorizedRows || 0);
            const hasRows = Number(previewSummary.totalRows || 0) > 0;
            const canCategorize = isContaOfxPreviewActive();

            let badgeStatus = 'idle';
            let badgeLabel = 'Aguardando preview';
            let title = 'Prepare um arquivo para revisar o lote';
            let copy = 'O preview vai dizer se o lote já pode ser confirmado ou se ainda precisa de revisão.';

            if (!quota.allowed) {
                badgeStatus = 'preview_error';
                badgeLabel = 'Limite atingido';
                title = 'Limite do plano atingido para este fluxo';
                copy = quota.message || 'Faça upgrade para preparar e confirmar novos lotes.';
            } else if (state.previewStatus === 'loading_preview') {
                badgeStatus = 'loading_preview';
                badgeLabel = 'Lendo lote';
                title = 'Lendo e validando o arquivo';
                copy = 'O sistema está analisando formato, linhas e consistência antes de liberar a revisão.';
            } else if (state.previewStatus === 'preview_error') {
                badgeStatus = 'preview_error';
                badgeLabel = 'Revisar lote';
                title = 'O lote precisa de revisão antes de avançar';
                copy = errorsCount > 0
                    ? state.previewErrors[0]
                    : 'Corrija o arquivo ou o contexto selecionado e gere o preview novamente.';
            } else if (state.previewStatus === 'preview_ready') {
                badgeStatus = state.previewCanConfirm ? 'preview_ready' : 'preview_error';
                badgeLabel = state.previewCanConfirm ? 'Pronto para decisão' : 'Com bloqueios';
                title = state.previewCanConfirm
                    ? (pendingCount > 0 && canCategorize
                        ? 'Preview pronto com pendências opcionais'
                        : 'Lote pronto para confirmação')
                    : 'Preview gerado, mas ainda com bloqueios';
                copy = state.previewCanConfirm
                    ? (pendingCount > 0 && canCategorize
                        ? `${pendingCount} linha(s) seguem sem categoria. Você pode confirmar agora ou revisar essas linhas antes.`
                        : 'O lote já pode ser confirmado. Use a tabela para uma última revisão antes de persistir.')
                    : (errorsCount > 0
                        ? state.previewErrors[0]
                        : 'Revise os avisos e ajustes pendentes antes de confirmar o lote.');
            } else if (state.previewStatus === 'confirming') {
                badgeStatus = 'confirming';
                badgeLabel = 'Confirmando';
                title = 'Confirmando importação';
                copy = String(state.jobProgressMessage || getProcessingMessage());
            } else if (state.previewStatus === 'confirmed') {
                badgeStatus = 'confirmed';
                badgeLabel = 'Concluído';
                title = 'Importação concluída';
                copy = getCompletedMessage(previewSummary);
            } else if (hasRows) {
                badgeStatus = 'file_selected';
                badgeLabel = 'Arquivo pronto';
                title = 'Arquivo pronto para gerar preview';
                copy = 'Clique em "Preparar preview" para validar as linhas antes da confirmação.';
            }

            if (previewReadinessBadge) {
                previewReadinessBadge.dataset.status = badgeStatus;
                previewReadinessBadge.textContent = badgeLabel;
            }

            if (previewReadinessTitle) {
                previewReadinessTitle.textContent = title;
            }

            if (previewReadinessCopy) {
                previewReadinessCopy.textContent = copy;
            }

            setPreviewChip(
                previewWarningChip,
                `${warningsCount} aviso${warningsCount === 1 ? '' : 's'}`,
                warningsCount > 0 ? 'warning' : 'neutral',
            );
            setPreviewChip(
                previewErrorChip,
                `${errorsCount} erro${errorsCount === 1 ? '' : 's'}`,
                errorsCount > 0 ? 'danger' : 'neutral',
            );
            setPreviewChip(
                previewPendingChip,
                canCategorize
                    ? `${pendingCount} sem categoria`
                    : 'Categorização opcional',
                canCategorize
                    ? (pendingCount > 0 ? 'warning' : 'success')
                    : 'neutral',
            );

            if (confirmTitle) {
                confirmTitle.textContent = state.previewStatus === 'confirmed'
                    ? 'Importação finalizada'
                    : (state.previewCanConfirm ? 'Checklist pronto para confirmar' : 'Checklist de revisão antes da confirmação');
            }

            applyConfirmCheck(
                confirmCheckContext,
                confirmCopyContext,
                state.selectedFile instanceof File && isContextSelected(state.selectedImportTarget) ? 'ready' : 'idle',
                state.selectedFile instanceof File && isContextSelected(state.selectedImportTarget)
                    ? `${currentTargetLabel()} · ${currentContextLabel()} · ${String(state.selectedSourceType || 'ofx').toUpperCase()}`
                    : 'Selecione contexto, formato e arquivo válidos para iniciar o preview.',
            );

            let reviewState = 'idle';
            let reviewCopy = 'O preview mostrará linhas, avisos e eventuais ajustes necessários.';
            if (state.previewStatus === 'loading_preview') {
                reviewState = 'active';
                reviewCopy = 'Lendo o lote e preparando a tabela de revisão.';
            } else if (state.previewStatus === 'preview_error') {
                reviewState = 'warning';
                reviewCopy = errorsCount > 0
                    ? `${errorsCount} erro(s) impedem a confirmação deste lote.`
                    : 'O preview não ficou pronto. Revise arquivo e contexto.';
            } else if (state.previewStatus === 'preview_ready') {
                reviewState = pendingCount > 0 && canCategorize ? 'warning' : 'ready';
                reviewCopy = pendingCount > 0 && canCategorize
                    ? `${pendingCount} linha(s) ainda sem categoria. A confirmação continua opcionalmente liberada.`
                    : `${previewSummary.totalRows || 0} linha(s) prontas para revisão final.`;
            } else if (state.previewStatus === 'confirmed') {
                reviewState = 'complete';
                reviewCopy = getCompletedMessage(previewSummary);
            }
            applyConfirmCheck(confirmCheckReview, confirmCopyReview, reviewState, reviewCopy);

            let confirmState = 'idle';
            let confirmCopy = 'A confirmação só será liberada quando o lote estiver pronto.';
            if (!quota.allowed) {
                confirmState = 'warning';
                confirmCopy = quota.message || 'Faça upgrade para liberar a confirmação deste fluxo.';
            } else if (state.previewStatus === 'confirming') {
                confirmState = 'active';
                confirmCopy = String(state.jobProgressMessage || getProcessingMessage());
            } else if (state.previewStatus === 'confirmed') {
                confirmState = 'complete';
                confirmCopy = getCompletedMessage(previewSummary);
            } else if (state.previewStatus === 'preview_ready' && state.previewCanConfirm === true) {
                confirmState = 'ready';
                confirmCopy = pendingCount > 0 && canCategorize
                    ? 'Você pode confirmar agora ou revisar as pendências opcionais antes de persistir.'
                    : 'A confirmação está liberada. Persistir agora grava os dados no sistema.';
            } else if (state.previewStatus === 'preview_ready') {
                confirmState = 'warning';
                confirmCopy = errorsCount > 0
                    ? 'O preview ainda possui bloqueios que precisam ser corrigidos.'
                    : 'Revise o lote antes de liberar a confirmação.';
            }
            applyConfirmCheck(confirmCheckConfirm, confirmCopyConfirm, confirmState, confirmCopy);
        }

        function renderPlanSummaryCard() {
            if (!planSummary) {
                return;
            }

            const normalizedPlanTier = String(planTier || '').trim().toLowerCase();
            if (normalizedPlanTier === '' && !importLimitsByBucket) {
                return;
            }

            const effectivePlanTier = normalizedPlanTier || 'free';

            if (planBadge) {
                planBadge.dataset.status = effectivePlanTier === 'free' ? 'idle' : 'preview_ready';
                planBadge.textContent = effectivePlanTier.toUpperCase();
            }

            planSummary.replaceChildren();

            if (effectivePlanTier !== 'free') {
                const paragraph = document.createElement('p');
                paragraph.className = 'imp-card-text';
                paragraph.textContent = 'Plano pago ativo: importações OFX/CSV liberadas sem limite prático.';
                planSummary.appendChild(paragraph);
                return;
            }

            const definitionList = document.createElement('dl');
            definitionList.className = 'imp-definition-list';

            Object.entries(IMPORT_LIMIT_LABELS).forEach(([bucketKey, bucketLabel]) => {
                const term = document.createElement('dt');
                term.textContent = bucketLabel;

                const detail = document.createElement('dd');
                const bucket = importLimitsByBucket?.[bucketKey];
                const remaining = Number(bucket?.remaining);
                detail.textContent = Number.isFinite(remaining)
                    ? `${remaining} restante(s)`
                    : 'Ilimitado';

                definitionList.append(term, detail);
            });

            const upgradeLink = document.createElement('a');
            upgradeLink.className = 'imp-link';
            upgradeLink.href = upgradeUrl;
            upgradeLink.textContent = 'Fazer upgrade';

            planSummary.append(definitionList, upgradeLink);
        }

        function renderHistorySummaryCard() {
            if (historyBadge && Array.isArray(latestHistoryItems)) {
                const metrics = computeHistoryMetrics(latestHistoryItems);
                historyBadge.textContent = `${metrics.processed} processados`;
            }

            if (!historyContent || !Array.isArray(latestHistoryItems)) {
                return;
            }

            historyContent.replaceChildren();

            if (latestHistoryItems.length === 0) {
                const emptyState = document.createElement('p');
                emptyState.className = 'imp-card-text';
                emptyState.textContent = 'Nenhum lote confirmado ainda. O histórico é atualizado assim que uma importação é confirmada.';
                historyContent.appendChild(emptyState);
                return;
            }

            const list = document.createElement('ul');
            list.className = 'imp-history-mini-list';

            latestHistoryItems.forEach((historyItem) => {
                const status = String(historyItem?.status || 'processed').trim().toLowerCase();
                const target = normalizeImportTarget(historyItem?.import_target || 'conta', 'conta');
                const item = document.createElement('li');

                const id = document.createElement('span');
                id.className = 'imp-history-mini-list__id';
                id.textContent = `#${Number.parseInt(String(historyItem?.batch_id || 0), 10) || 0}`;

                const fileName = document.createElement('span');
                fileName.className = 'imp-history-mini-list__file';
                fileName.textContent = String(historyItem?.filename || 'arquivo');

                item.append(
                    id,
                    fileName,
                    createStatusBadge(status, HISTORY_STATUS_LABELS[status] || status.toUpperCase()),
                    createStatusBadge('idle', HISTORY_TARGET_LABELS[target] || 'Conta'),
                );

                list.appendChild(item);
            });

            historyContent.appendChild(list);
        }

        function applyPageInitPayload(payload) {
            const data = payload && typeof payload === 'object' ? payload : {};
            const nextImportTarget = normalizeImportTarget(data.importTarget || context.state.selectedImportTarget, 'conta');
            const nextSourceType = normalizeSourceType(
                data.sourceType || context.state.selectedSourceType,
                context.state.selectedSourceType || 'ofx',
            );
            const nextSelectedAccountId = parsePositiveInt(data.selectedAccountId ?? accountSelect?.value ?? null);
            const nextSelectedCardId = parsePositiveInt(data.selectedCardId ?? cardSelect?.value ?? null);

            populateAccountOptions(data.accounts, nextSelectedAccountId);
            populateCardOptions(data.cards, nextSelectedCardId);

            if (data.planLimits && typeof data.planLimits === 'object') {
                planTier = String(
                    data.planLimits.plan || (data.planLimits.is_pro === true ? 'pro' : 'free'),
                ).trim().toLowerCase();
                upgradeUrl = String(data.planLimits.upgrade_url || upgradeUrl || '/assinatura').trim() || '/assinatura';
                importLimitsByBucket = data.planLimits.importacoes && typeof data.planLimits.importacoes === 'object'
                    ? data.planLimits.importacoes
                    : {};
            }

            if (data.importQuota && typeof data.importQuota === 'object') {
                const bucket = String(data.importQuota.bucket || '').trim();
                if (bucket !== '') {
                    importLimitsByBucket = importLimitsByBucket && typeof importLimitsByBucket === 'object'
                        ? importLimitsByBucket
                        : {};
                    importLimitsByBucket[bucket] = {
                        ...(importLimitsByBucket[bucket] && typeof importLimitsByBucket[bucket] === 'object'
                            ? importLimitsByBucket[bucket]
                            : {}),
                        ...data.importQuota,
                    };
                }

                if (data.importQuota.upgrade_url) {
                    upgradeUrl = String(data.importQuota.upgrade_url).trim() || upgradeUrl;
                }
            }

            latestHistoryItems = Array.isArray(data.latestHistoryItems) ? data.latestHistoryItems : [];

            const normalizedProfile = normalizeProfileConfig(data.profileConfig);
            if (normalizedProfile?.contaId) {
                profileCache.set(String(normalizedProfile.contaId), normalizedProfile);
                activeProfileConfig = normalizedProfile;
                profileLoadState = 'ready';
                profileLoadError = '';
            } else if (!resolveActiveConfigAccountId(nextImportTarget, {
                selectedAccountId: nextSelectedAccountId,
                selectedCardId: nextSelectedCardId,
            })) {
                activeProfileConfig = null;
                profileLoadState = 'idle';
                profileLoadError = '';
            }

            context.setState({
                selectedImportTarget: nextImportTarget,
                selectedSourceType: nextSourceType,
                selectedAccountId: nextSelectedAccountId,
                selectedCardId: nextSelectedCardId,
            });

            if (!normalizedProfile) {
                const activeAccountId = resolveActiveConfigAccountId(nextImportTarget, {
                    selectedAccountId: nextSelectedAccountId,
                    selectedCardId: nextSelectedCardId,
                });

                if (activeAccountId) {
                    void ensureProfileConfigLoaded(activeAccountId);
                }
            }
        }

        async function hydratePageInit(overrides = {}) {
            const currentRequestToken = ++pageInitRequestToken;

            try {
                const response = await api.loadPageInit(buildPageInitParams(overrides));
                if (currentRequestToken !== pageInitRequestToken) {
                    return;
                }

                applyPageInitPayload(response?.data ?? response ?? {});
            } catch {
                if (currentRequestToken !== pageInitRequestToken) {
                    return;
                }

                void ensurePlanLimitsLoaded();
                void ensureProfileConfigLoaded(resolveActiveConfigAccountId());
            }
        }

        function renderMessages(container, messages) {
            if (!container) {
                return;
            }

            const normalizedMessages = normalizeMessages(messages);
            container.innerHTML = '';
            container.hidden = normalizedMessages.length === 0;

            normalizedMessages.forEach((message) => {
                const item = document.createElement('li');
                item.textContent = message;
                container.appendChild(item);
            });
        }

        function applyGuideCard(cardElement, titleElement, copyElement, guide) {
            if (!guide) {
                return;
            }

            if (cardElement) {
                cardElement.dataset.state = String(guide.state || 'info');
            }

            if (titleElement) {
                titleElement.textContent = String(guide.title || '');
            }

            if (copyElement) {
                copyElement.textContent = String(guide.copy || '');
            }
        }

        function setStatusMessage(message) {
            if (previewState) {
                previewState.textContent = String(message || '');
            }
        }

        function resolveErrorMessages(error, fallback = 'Nao foi possivel concluir a operacao.') {
            const collected = normalizeMessages([
                ...(Array.isArray(error?.messages) ? error.messages : []),
                error?.message,
                error?.payload?.message,
            ]);

            return collected.length > 0 ? collected : [fallback];
        }

        function canSubmit() {
            const quota = currentQuota();
            return quota.allowed
                && context.state.previewStatus !== 'loading_preview'
                && context.state.previewStatus !== 'confirming'
                && context.state.selectedFile instanceof File
                && isContextSelected();
        }

        function getProcessingMessage(progress = null) {
            const totalRows = Number(progress?.totalRows ?? 0);
            const processedRows = Number(progress?.processedRows ?? 0);
            if (totalRows > 0 && processedRows > 0) {
                return `Importando ${processedRows}/${totalRows} linha(s)...`;
            }

            return 'Seu arquivo esta sendo importado...';
        }

        function getCompletedMessage(summary = normalizePreviewSummaryState(context.state.previewSummary)) {
            const parts = [];

            if (Number(summary.importedRows || 0) > 0) {
                parts.push(`${summary.importedRows} importada(s)`);
            }

            if (Number(summary.duplicateRows || 0) > 0) {
                parts.push(`${summary.duplicateRows} duplicada(s)`);
            }

            if (Number(summary.errorRows || 0) > 0) {
                parts.push(`${summary.errorRows} com erro`);
            }

            return parts.length > 0 ? `Concluido: ${parts.join(', ')}.` : 'Concluido.';
        }

        function onPreviewRowUpdate(rowKey, patchOrUpdater) {
            const nextRows = (Array.isArray(context.state.previewRows) ? context.state.previewRows : []).map((row) => {
                if (String(row?.rowKey || '') !== String(rowKey || '')) {
                    return row;
                }

                const patch = typeof patchOrUpdater === 'function'
                    ? patchOrUpdater(row)
                    : patchOrUpdater;

                return syncPreviewRowState({
                    ...row,
                    ...(patch && typeof patch === 'object' ? patch : {}),
                });
            });

            context.setState({
                previewRows: nextRows,
                previewSummary: mergePreviewSummary(summarizeRows(nextRows)),
            });
        }

        function stopJobPolling() {
            if (pollingTimer) {
                window.clearTimeout(pollingTimer);
                pollingTimer = null;
            }
        }

        function updateJobProgressMessage(message) {
            context.setState({
                jobProgressMessage: String(message || getProcessingMessage()),
            });
        }

        async function ensurePlanLimitsLoaded(force = false) {
            if (!force && planLimitsRequest) {
                return planLimitsRequest;
            }

            if (!force && importLimitsByBucket && String(planTier || '').trim() !== '') {
                return importLimitsByBucket;
            }

            planLimitsRequest = (async () => {
                try {
                    const response = await api.loadPlanLimits();
                    const payload = response?.data ?? response ?? {};

                    planTier = String(
                        payload.plan || (payload.is_pro === true ? 'pro' : 'free')
                    ).trim().toLowerCase();
                    upgradeUrl = String(payload.upgrade_url || upgradeUrl || '/assinatura').trim() || '/assinatura';
                    importLimitsByBucket = payload.importacoes && typeof payload.importacoes === 'object'
                        ? payload.importacoes
                        : {};
                } catch {
                    if (!importLimitsByBucket || typeof importLimitsByBucket !== 'object') {
                        importLimitsByBucket = {};
                    }

                    if (String(planTier || '').trim() === '') {
                        planTier = 'free';
                    }
                } finally {
                    planLimitsRequest = null;
                    renderCurrentState(context.state);
                }

                return importLimitsByBucket;
            })();

            return planLimitsRequest;
        }

        async function ensureProfileConfigLoaded(accountId = resolveActiveConfigAccountId()) {
            const normalizedAccountId = parsePositiveInt(accountId);
            if (!normalizedAccountId) {
                activeProfileConfig = null;
                profileLoadState = 'idle';
                profileLoadError = '';
                renderCurrentState(context.state);
                return null;
            }

            const cacheKey = String(normalizedAccountId);
            if (profileCache.has(cacheKey)) {
                activeProfileConfig = profileCache.get(cacheKey) || null;
                profileLoadState = 'ready';
                profileLoadError = '';
                renderCurrentState(context.state);
                return activeProfileConfig;
            }

            profileLoadState = 'loading';
            profileLoadError = '';
            renderCurrentState(context.state);

            const currentRequestToken = ++profileRequestToken;

            try {
                const response = await api.loadProfileConfig(normalizedAccountId);
                if (currentRequestToken !== profileRequestToken) {
                    return null;
                }

                const rawProfile = response?.data?.configuracao
                    ?? response?.data?.config
                    ?? response?.data?.profile
                    ?? response?.data
                    ?? null;

                activeProfileConfig = normalizeProfileConfig(rawProfile);
                if (activeProfileConfig?.contaId) {
                    profileCache.set(String(activeProfileConfig.contaId), activeProfileConfig);
                }

                profileLoadState = activeProfileConfig ? 'ready' : 'error';
                profileLoadError = activeProfileConfig
                    ? ''
                    : 'Nao foi possivel carregar a configuracao CSV desta conta.';
                renderCurrentState(context.state);
                return activeProfileConfig;
            } catch (error) {
                if (currentRequestToken !== profileRequestToken) {
                    return null;
                }

                activeProfileConfig = null;
                profileLoadState = 'error';
                profileLoadError = resolveErrorMessages(
                    error,
                    'Nao foi possivel carregar a configuracao CSV desta conta.',
                )[0];
                renderCurrentState(context.state);
                return null;
            }
        }

        function resetPreviewState(nextPatch = {}, { clearFile = false } = {}) {
            const selectedFile = clearFile
                ? null
                : (Object.prototype.hasOwnProperty.call(nextPatch, 'selectedFile')
                    ? nextPatch.selectedFile
                    : context.state.selectedFile);
            const selectedFileName = selectedFile?.name || '';

            context.setState({
                previewStatus: selectedFile ? 'file_selected' : 'idle',
                previewRows: [],
                previewWarnings: [],
                previewErrors: [],
                previewCanConfirm: false,
                jobProgressMessage: '',
                showOnlyPendingCategories: false,
                previewSummary: createEmptyPreviewSummary(selectedFileName),
                ...(clearFile ? {
                    selectedFile: null,
                    selectedFileDetectedSourceType: '',
                    selectedFileDetectedImportTarget: '',
                    sourceAutoAdjustedToDetectedFile: false,
                    targetAutoAdjustedToDetectedFile: false,
                } : {}),
                ...nextPatch,
            });

            if (!selectedFile) {
                clearCategoryCatalogError();
                clearPreviewRows();
            }
        }

        async function inspectSelectedFile(file) {
            stopJobPolling();

            const inspectionToken = ++fileInspectionToken;
            if (!(file instanceof File)) {
                resetPreviewState({
                    selectedFile: null,
                    selectedFileDetectedSourceType: '',
                    selectedFileDetectedImportTarget: '',
                    sourceAutoAdjustedToDetectedFile: false,
                    targetAutoAdjustedToDetectedFile: false,
                    fileDropActive: false,
                }, { clearFile: true });
                return;
            }

            const detectedSourceType = detectSourceTypeFromFile(file);

            resetPreviewState({
                selectedFile: file,
                selectedFileDetectedSourceType: detectedSourceType,
                selectedFileDetectedImportTarget: '',
                sourceAutoAdjustedToDetectedFile: false,
                targetAutoAdjustedToDetectedFile: false,
                fileDropActive: false,
            });

            const nextPatch = {};
            let nextImportTarget = context.state.selectedImportTarget;

            if (detectedSourceType && detectedSourceType !== context.state.selectedSourceType) {
                nextPatch.selectedSourceType = detectedSourceType;
                nextPatch.sourceAutoAdjustedToDetectedFile = true;
            }

            if (detectedSourceType === 'ofx') {
                try {
                    const contents = await readTextFromFile(file);
                    if (inspectionToken !== fileInspectionToken) {
                        return;
                    }

                    const detectedImportTarget = normalizeDetectedImportTarget(
                        detectImportTargetFromOfxContents(contents),
                    );
                    nextPatch.selectedFileDetectedImportTarget = detectedImportTarget;

                    if (detectedImportTarget && detectedImportTarget !== context.state.selectedImportTarget) {
                        nextImportTarget = detectedImportTarget;
                        nextPatch.selectedImportTarget = detectedImportTarget;
                        nextPatch.targetAutoAdjustedToDetectedFile = true;

                        if (detectedImportTarget === 'cartao') {
                            nextPatch.selectedCardId = resolveSelectedCardId();
                        } else {
                            nextPatch.selectedAccountId = resolveSelectedAccountId();
                        }
                    }
                } catch {
                    if (inspectionToken !== fileInspectionToken) {
                        return;
                    }
                }
            }

            if (inspectionToken !== fileInspectionToken) {
                return;
            }

            context.setState({
                ...nextPatch,
                previewStatus: 'file_selected',
                previewSummary: createEmptyPreviewSummary(file.name),
            });

            void ensureProfileConfigLoaded(resolveActiveConfigAccountId(nextImportTarget, nextPatch));
        }

        function buildPreviewPayload({ categorizePreview = false } = {}) {
            const payload = {
                source_type: context.state.selectedSourceType,
                import_target: context.state.selectedImportTarget,
                filename: context.state.selectedFile?.name || '',
                categorize_preview: categorizePreview ? '1' : '0',
                async: '0',
            };

            if (isCardTarget()) {
                payload.cartao_id = context.state.selectedCardId;
            } else {
                payload.conta_id = context.state.selectedAccountId;
            }

            const rowOverrides = buildRowOverrides(context.state.previewRows);
            if (Object.keys(rowOverrides).length > 0) {
                payload.row_overrides = rowOverrides;
            }

            return payload;
        }

        function buildConfirmPayload() {
            return {
                ...buildPreviewPayload({
                    categorizePreview: context.state.previewSummary?.categorizationApplied === true,
                }),
                async: confirmAsyncDefault ? '1' : '0',
            };
        }

        function normalizePreviewResponse(response, payload) {
            const body = typeof response?.data === 'object' && response?.data !== null ? response.data : {};
            const preview = typeof body.preview === 'object' && body.preview !== null ? body.preview : body;
            const rows = normalizeRows(preview.rows ?? []);
            const rowSummary = summarizeRows(rows);
            const totalRows = Number.parseInt(String(preview.total_rows ?? rows.length), 10);
            const warnings = normalizeMessages(preview.warnings ?? body.warnings ?? []);
            const errors = normalizeMessages(preview.errors ?? body.errors ?? []);

            return {
                importTarget: normalizeImportTarget(preview.import_target || payload.import_target || 'conta', 'conta'),
                cardId: parsePositiveInt(preview.cartao_id ?? payload.cartao_id ?? null),
                detectedImportTarget: normalizeDetectedImportTarget(preview.detected_import_target),
                targetMismatch: Boolean(preview.target_mismatch === true),
                rows,
                warnings,
                errors,
                canConfirm: preview.can_confirm === true,
                summary: normalizePreviewSummaryState({
                    fileName: String(preview.filename || payload.filename || ''),
                    totalRows: Number.isFinite(totalRows) ? totalRows : rows.length,
                    importedRows: 0,
                    duplicateRows: 0,
                    errorRows: 0,
                    categorizedRows: rowSummary.categorizedRows,
                    uncategorizedRows: rowSummary.uncategorizedRows,
                    userRuleSuggestedRows: rowSummary.userRuleSuggestedRows,
                    globalRuleSuggestedRows: rowSummary.globalRuleSuggestedRows,
                    categorizationApplied: isTruthyFlag(payload?.categorize_preview),
                }, payload.filename || ''),
            };
        }

        function normalizeConfirmResponse(response) {
            const body = typeof response?.data === 'object' && response?.data !== null ? response.data : {};
            const summary = typeof body.summary === 'object' && body.summary !== null ? body.summary : {};
            const batch = typeof body.batch === 'object' && body.batch !== null ? body.batch : {};
            const job = typeof body.job === 'object' && body.job !== null ? body.job : null;

            if (job && Number.parseInt(String(job.id ?? 0), 10) > 0) {
                return {
                    mode: 'async',
                    jobId: Number.parseInt(String(job.id ?? 0), 10) || 0,
                    jobStatus: String(job.status || 'queued').trim().toLowerCase(),
                    importTarget: normalizeImportTarget(job.import_target || context.state.selectedImportTarget || 'conta', 'conta'),
                };
            }

            return {
                mode: 'sync',
                batchId: Number.parseInt(String(batch.id ?? 0), 10) || 0,
                importTarget: normalizeImportTarget(batch.import_target || context.state.selectedImportTarget || 'conta', 'conta'),
                totalRows: Number.parseInt(String(summary.total_rows ?? batch.total_rows ?? 0), 10) || 0,
                importedRows: Number.parseInt(String(summary.imported_rows ?? batch.imported_rows ?? 0), 10) || 0,
                duplicateRows: Number.parseInt(String(summary.duplicate_rows ?? batch.duplicate_rows ?? 0), 10) || 0,
                errorRows: Number.parseInt(String(summary.error_rows ?? batch.error_rows ?? 0), 10) || 0,
            };
        }

        function normalizeJobStatusResponse(response) {
            const body = typeof response?.data === 'object' && response?.data !== null ? response.data : {};
            const job = typeof body.job === 'object' && body.job !== null ? body.job : {};
            const summary = typeof body.summary === 'object' && body.summary !== null ? body.summary : {};
            const batch = typeof body.batch === 'object' && body.batch !== null ? body.batch : {};

            return {
                status: String(body.status || job.status || '').trim().toLowerCase(),
                jobId: Number.parseInt(String(job.id ?? 0), 10) || 0,
                batchId: Number.parseInt(String(batch.id ?? job.result_batch_id ?? 0), 10) || 0,
                importTarget: normalizeImportTarget(
                    batch.import_target || job.import_target || context.state.selectedImportTarget || 'conta',
                    'conta',
                ),
                totalRows: Number.parseInt(String(summary.total_rows ?? job.total_rows ?? batch.total_rows ?? 0), 10) || 0,
                processedRows: Number.parseInt(String(summary.processed_rows ?? job.processed_rows ?? 0), 10) || 0,
                importedRows: Number.parseInt(String(summary.imported_rows ?? job.imported_rows ?? batch.imported_rows ?? 0), 10) || 0,
                duplicateRows: Number.parseInt(String(summary.duplicate_rows ?? job.duplicate_rows ?? batch.duplicate_rows ?? 0), 10) || 0,
                errorRows: Number.parseInt(String(summary.error_rows ?? job.error_rows ?? batch.error_rows ?? 0), 10) || 0,
                message: String(body.message || job.error_summary || '').trim(),
            };
        }

        async function applyPreviewResult(response, payload) {
            const normalized = normalizePreviewResponse(response, payload);
            let nextRows = normalized.rows;

            if (isTruthyFlag(payload.categorize_preview)) {
                nextRows = mergeSuggestedRowsIntoPreview(context.state.previewRows, nextRows)
                    .map((row) => syncPreviewRowState(row));
            }

            const nextSummary = normalizePreviewSummaryState({
                ...normalized.summary,
                ...summarizeRows(nextRows),
            }, normalized.summary.fileName);

            if (isContaOfxPreviewActive(normalized.importTarget, payload.source_type)) {
                try {
                    await ensureCategoryOptionsLoaded();
                    await prefetchSubcategoryOptions(nextRows);
                    clearCategoryCatalogError();
                } catch (error) {
                    const categoryError = resolveErrorMessages(
                        error,
                        'Nao foi possivel carregar catalogo de categorias para o preview.',
                    )[0];
                    setCategoryCatalogError(categoryError);
                    normalized.warnings = [...normalized.warnings, categoryError];
                }
            } else {
                clearCategoryCatalogError();
            }

            context.setState({
                selectedImportTarget: normalized.importTarget,
                selectedCardId: normalized.cardId ?? context.state.selectedCardId,
                previewStatus: 'preview_ready',
                previewRows: nextRows,
                previewWarnings: normalized.warnings,
                previewErrors: normalized.errors,
                previewCanConfirm: normalized.canConfirm,
                jobProgressMessage: '',
                previewSummary: nextSummary,
            });
        }

        function applyPreviewError(error, payload = {}) {
            const nextErrors = resolveErrorMessages(error, 'Nao foi possivel concluir a etapa.');

            context.setState({
                previewStatus: 'preview_error',
                previewWarnings: [],
                previewErrors: nextErrors,
                previewCanConfirm: false,
                jobProgressMessage: '',
                previewSummary: mergePreviewSummary({
                    fileName: payload.filename || context.state.selectedFile?.name || context.state.previewSummary?.fileName || '',
                }),
            });
        }

        function applyConfirmedResult(normalized) {
            const warnings = [];
            const errors = [];

            if (normalized.duplicateRows > 0) {
                warnings.push(`${normalized.duplicateRows} linha(s) ignorada(s) por duplicidade.`);
            }

            if (normalized.errorRows > 0) {
                errors.push(`${normalized.errorRows} linha(s) falharam ao persistir.`);
            }

            if (normalized.importedRows > 0) {
                markCurrentBucketAsUsed();
            }

            context.setState({
                selectedImportTarget: normalized.importTarget,
                previewStatus: 'confirmed',
                previewWarnings: warnings,
                previewErrors: errors,
                previewCanConfirm: false,
                jobProgressMessage: '',
                previewSummary: mergePreviewSummary({
                    totalRows: normalized.totalRows,
                    importedRows: normalized.importedRows,
                    duplicateRows: normalized.duplicateRows,
                    errorRows: normalized.errorRows,
                }),
            });
        }

        function scheduleJobPolling(payload) {
            stopJobPolling();

            const poll = async () => {
                if (!activeJobId) {
                    return;
                }

                try {
                    const response = await api.requestJobStatus(activeJobId);
                    const normalized = normalizeJobStatusResponse(response);

                    if (normalized.status === 'queued' || normalized.status === 'processing') {
                        updateJobProgressMessage(normalized.message || getProcessingMessage(normalized));
                        pollingTimer = window.setTimeout(poll, 2000);
                        return;
                    }

                    stopJobPolling();

                    if (normalized.status === 'completed') {
                        applyConfirmedResult(normalized);
                        activeJobId = null;
                        return;
                    }

                    activeJobId = null;
                    applyPreviewError(
                        new Error(normalized.message || `Lote #${normalized.jobId || 0} falhou durante o processamento.`),
                        payload,
                    );
                } catch (error) {
                    stopJobPolling();
                    activeJobId = null;
                    applyPreviewError(error, payload);
                }
            };

            void poll();
        }

        async function handlePreviewRequest() {
            if (!canSubmit()) {
                return;
            }

            const payload = buildPreviewPayload();

            context.setState({
                previewStatus: 'loading_preview',
                previewWarnings: [],
                previewErrors: [],
                previewCanConfirm: false,
                jobProgressMessage: '',
                previewSummary: mergePreviewSummary({
                    fileName: context.state.selectedFile?.name || '',
                }),
            });

            try {
                const response = await api.requestPreview(payload, context.state.selectedFile);
                await applyPreviewResult(response, payload);
            } catch (error) {
                applyPreviewError(error, payload);
            }
        }

        async function handleCategorizePreview() {
            if (!isContaOfxPreviewActive() || context.state.previewStatus !== 'preview_ready') {
                return;
            }

            const payload = buildPreviewPayload({ categorizePreview: true });

            context.setState({
                previewStatus: 'loading_preview',
                previewWarnings: [],
                previewErrors: [],
                jobProgressMessage: '',
            });

            try {
                const response = await api.requestPreview(payload, context.state.selectedFile);
                await applyPreviewResult(response, payload);
            } catch (error) {
                applyPreviewError(error, payload);
            }
        }

        async function handleConfirm() {
            if (context.state.previewStatus !== 'preview_ready' || context.state.previewCanConfirm !== true) {
                return;
            }

            const payload = buildConfirmPayload();

            context.setState({
                previewStatus: 'confirming',
                previewWarnings: [],
                previewErrors: [],
                previewCanConfirm: false,
                jobProgressMessage: getProcessingMessage(),
            });

            try {
                const response = await api.requestConfirm(payload, context.state.selectedFile);
                const normalized = normalizeConfirmResponse(response);

                if (normalized.mode === 'async') {
                    activeJobId = normalized.jobId;
                    updateJobProgressMessage(getProcessingMessage());
                    scheduleJobPolling(payload);
                    return;
                }

                applyConfirmedResult(normalized);
            } catch (error) {
                activeJobId = null;
                stopJobPolling();
                applyPreviewError(error, payload);
            }
        }

        function bindFileDropEvents() {
            if (!fileDrop) {
                return;
            }

            fileDrop.addEventListener('dragenter', (event) => {
                event.preventDefault();
                fileDropDragDepth += 1;
                context.setState({ fileDropActive: true });
            });

            fileDrop.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (context.state.fileDropActive !== true) {
                    context.setState({ fileDropActive: true });
                }
            });

            fileDrop.addEventListener('dragleave', (event) => {
                event.preventDefault();
                fileDropDragDepth = Math.max(0, fileDropDragDepth - 1);
                if (fileDropDragDepth === 0) {
                    context.setState({ fileDropActive: false });
                }
            });

            fileDrop.addEventListener('drop', async (event) => {
                event.preventDefault();
                fileDropDragDepth = 0;
                context.setState({ fileDropActive: false });
                const file = event.dataTransfer?.files?.[0] || null;
                await inspectSelectedFile(file);
            });
        }

        function renderCurrentState(state) {
            const quota = currentQuota();
            const previewSummary = normalizePreviewSummaryState(state.previewSummary, state.selectedFile?.name || '');
            const templateMeta = buildTemplateMeta(state.selectedImportTarget);
            const profileDisplay = renderProfileDisplay({
                activeProfileConfig,
                profileLoadState,
                profileLoadError,
                importTarget: state.selectedImportTarget,
                resolveActiveConfigAccountId,
                resolveActiveConfigAccountLabel,
            });
            const activeConfigAccountId = resolveActiveConfigAccountId();
            const pathGuide = buildPathGuide(state);
            const contextGuide = buildContextGuide(state, {
                activeConfigAccountId,
                profileDisplay,
                currentAccountLabel,
                currentCardLabel,
            });
            const readinessGuide = buildReadinessGuide(state, quota, {
                isContextSelected,
                isContaOfxPreviewActive,
                getCompletedMessage,
                getProcessingMessage,
            });
            const fileSelectionNote = buildFileNote(state);
            const allRows = Array.isArray(state.previewRows) ? state.previewRows : [];
            const filteredRows = state.showOnlyPendingCategories
                ? allRows.filter((row) => !hasCategory(row))
                : allRows;
            const hasRows = allRows.length > 0;
            const hasSelectedFile = state.selectedFile instanceof File;
            const hasRenderableRows = filteredRows.length > 0;
            const showPreviewOverview = state.previewStatus !== 'idle' || hasRows || hasSelectedFile;
            const showReviewChrome = hasRows || ['loading_preview', 'preview_ready', 'preview_error', 'confirming', 'confirmed'].includes(state.previewStatus);
            const showPreviewFooter = ['preview_ready', 'preview_error', 'confirming', 'confirmed'].includes(state.previewStatus);
            const interactionsLocked = state.previewStatus === 'loading_preview' || state.previewStatus === 'confirming';

            sourceInputs.forEach((input) => {
                input.checked = normalizeSourceType(input.value, 'ofx') === state.selectedSourceType;
                input.disabled = interactionsLocked;
            });

            targetInputs.forEach((input) => {
                input.checked = normalizeImportTarget(input.value, 'conta') === state.selectedImportTarget;
                input.disabled = interactionsLocked;
            });

            if (accountSelect) {
                const nextAccountId = parsePositiveInt(state.selectedAccountId ?? null);
                if (nextAccountId) {
                    const option = findAccountOptionById(nextAccountId);
                    if (option) {
                        accountSelect.value = String(nextAccountId);
                    }
                }
                accountSelect.disabled = interactionsLocked || accountSelect.options.length === 0;
            }

            if (cardSelect) {
                const nextCardId = parsePositiveInt(state.selectedCardId ?? null);
                if (nextCardId) {
                    const option = findCardOptionById(nextCardId);
                    if (option) {
                        cardSelect.value = String(nextCardId);
                    }
                }
                cardSelect.disabled = interactionsLocked || cardSelect.options.length === 0;
            }

            if (accountField) {
                accountField.hidden = isCardTarget(state.selectedImportTarget);
            }

            if (cardField) {
                cardField.hidden = !isCardTarget(state.selectedImportTarget);
            }

            if (accountWarning) {
                accountWarning.hidden = Boolean(accountSelect && accountSelect.options.length > 0);
            }

            if (accountLink) {
                accountLink.hidden = Boolean(accountSelect && accountSelect.options.length > 0);
            }

            if (accountSelect) {
                accountSelect.hidden = !(accountSelect.options.length > 0);
            }

            if (cardWarning) {
                cardWarning.hidden = Boolean(cardSelect && cardSelect.options.length > 0);
            }

            if (cardLink) {
                cardLink.hidden = Boolean(cardSelect && cardSelect.options.length > 0);
            }

            if (cardSelect) {
                cardSelect.hidden = !(cardSelect.options.length > 0);
            }

            if (targetSourceHint) {
                targetSourceHint.hidden = !isCardTarget(state.selectedImportTarget);
            }

            if (heroSourceLabel) {
                heroSourceLabel.textContent = String(state.selectedSourceType || 'ofx').toUpperCase();
            }

            if (fileDrop) {
                fileDrop.dataset.hasFile = state.selectedFile ? 'true' : 'false';
                fileDrop.dataset.previewStatus = state.previewStatus;
                fileDrop.dataset.dragActive = state.fileDropActive ? 'true' : 'false';
            }

            if (fileInput) {
                fileInput.disabled = interactionsLocked;
            }

            if (selectedFileLabel) {
                selectedFileLabel.textContent = state.selectedFile
                    ? `Arquivo selecionado: ${state.selectedFile.name}`
                    : 'Nenhum arquivo selecionado.';
            }

            if (fileNote) {
                if (fileSelectionNote) {
                    fileNote.hidden = false;
                    fileNote.dataset.state = fileSelectionNote.state;
                    fileNote.textContent = fileSelectionNote.text;
                } else {
                    fileNote.hidden = true;
                    fileNote.dataset.state = 'info';
                    fileNote.textContent = '';
                }
            }

            applyGuideCard(guidePathCard, guidePathTitle, guidePathCopy, pathGuide);
            applyGuideCard(guideContextCard, guideContextTitle, guideContextCopy, contextGuide);
            applyGuideCard(guideReadinessCard, guideReadinessTitle, guideReadinessCopy, readinessGuide);
            renderFlowSteps(state, previewSummary);
            renderFileStageBadge(state);

            if (advancedDescription) {
                advancedDescription.textContent = buildAdvancedDescription(state.selectedImportTarget, state.selectedSourceType);
            }

            if (advancedSummaryCopy) {
                advancedSummaryCopy.textContent = state.selectedSourceType === 'csv'
                    ? 'Modelos e perfil CSV disponíveis para este fluxo.'
                    : 'Abra só se precisar de modelo CSV ou ajuste fino.';
            }

            if (advancedPanel) {
                if (state.selectedSourceType === 'csv') {
                    advancedPanel.open = true;
                } else if (state.previewStatus === 'idle' && !hasSelectedFile) {
                    advancedPanel.open = false;
                }
            }

            if (advancedModeBadge) {
                advancedModeBadge.dataset.status = state.selectedSourceType === 'csv' ? 'preview_ready' : 'idle';
                advancedModeBadge.textContent = state.selectedSourceType === 'csv' ? 'CSV ativo' : 'Opcional';
            }

            if (advancedTemplateChip) {
                advancedTemplateChip.textContent = templateMeta.chip;
            }

            if (advancedTemplateTitle) {
                advancedTemplateTitle.textContent = templateMeta.title;
            }

            if (advancedTemplateCopy) {
                advancedTemplateCopy.textContent = templateMeta.copy;
            }

            if (advancedLinkedAccountNote) {
                advancedLinkedAccountNote.textContent = !activeConfigAccountId
                    ? 'Selecione uma conta para revisar o perfil CSV usado neste fluxo.'
                    : (profileLoadError || templateMeta.contextNote);
            }

            if (advancedTemplateAutoLink) {
                advancedTemplateAutoLink.href = templateMeta.autoHref;
                advancedTemplateAutoLink.textContent = templateMeta.autoLabel;
            }

            if (advancedTemplateManualLink) {
                advancedTemplateManualLink.href = templateMeta.manualHref;
                advancedTemplateManualLink.textContent = templateMeta.manualLabel;
            }

            if (advancedSummaryContext) {
                advancedSummaryContext.textContent = templateMeta.summaryContext;
            }

            if (advancedDetails) {
                advancedDetails.open = state.selectedSourceType === 'csv';
            }

            if (previewOverview) {
                previewOverview.hidden = !showPreviewOverview;
            }

            if (advancedAccountName) {
                advancedAccountName.textContent = profileDisplay.accountLabel;
            }

            if (advancedSourceType) {
                advancedSourceType.textContent = profileDisplay.sourceTypeLabel;
            }

            if (advancedMappingMode) {
                advancedMappingMode.textContent = profileDisplay.mappingModeLabel;
            }

            if (advancedHasHeader) {
                advancedHasHeader.textContent = profileDisplay.hasHeaderLabel;
            }

            if (advancedStartRow) {
                advancedStartRow.textContent = profileDisplay.startRowLabel;
            }

            if (advancedDelimiter) {
                advancedDelimiter.textContent = profileDisplay.delimiterLabel;
            }

            if (advancedDateFormat) {
                advancedDateFormat.textContent = profileDisplay.dateFormatLabel;
            }

            if (advancedDecimal) {
                advancedDecimal.textContent = profileDisplay.decimalLabel;
            }

            if (advancedColumnMap) {
                advancedColumnMap.textContent = profileDisplay.columnMapSummary;
            }

            if (profileBadge) {
                profileBadge.textContent = templateMeta.badge;
                profileBadge.dataset.status = !activeConfigAccountId
                    ? 'idle'
                    : (profileDisplay.sourceTypeLabel === 'Carregando...' ? 'file_selected' : 'preview_ready');
            }

            if (profileAccountName) {
                profileAccountName.textContent = profileDisplay.accountLabel;
            }

            if (profileSourceType) {
                profileSourceType.textContent = profileDisplay.sourceTypeLabel;
            }

            if (profileCsvMode) {
                profileCsvMode.textContent = profileDisplay.mappingModeLabel;
            }

            if (profileCsvDelimiter) {
                profileCsvDelimiter.textContent = profileDisplay.delimiterLabel;
            }

            if (profileCsvDateFormat) {
                profileCsvDateFormat.textContent = profileDisplay.dateFormatLabel;
            }

            if (profileCsvDecimal) {
                profileCsvDecimal.textContent = profileDisplay.decimalLabel;
            }

            if (profileContextNote) {
                profileContextNote.textContent = !activeConfigAccountId
                    ? 'Selecione uma conta para revisar o perfil CSV usado neste fluxo.'
                    : (profileLoadError || templateMeta.contextNote);
            }

            configLinks.forEach((link) => {
                link.href = resolveConfigPageUrl();
            });

            renderHeroSummary();
            renderPlanSummaryCard();
            renderHistorySummaryCard();

            if (previewBadge) {
                previewBadge.dataset.status = state.previewStatus;
                previewBadge.textContent = STATUS_BADGES[state.previewStatus] || STATUS_BADGES.idle;
            }

            if (previewTarget) {
                previewTarget.textContent = currentTargetLabel();
            }

            if (previewContextLabel) {
                previewContextLabel.textContent = currentContextLabel();
            }

            if (previewSourceType) {
                previewSourceType.textContent = String(state.selectedSourceType || 'ofx').toUpperCase();
            }

            if (previewFileName) {
                previewFileName.textContent = previewSummary.fileName || '-';
            }

            if (previewTotalRows) {
                previewTotalRows.textContent = String(previewSummary.totalRows || 0);
            }

            if (previewCategorized) {
                previewCategorized.textContent = String(previewSummary.categorizedRows || 0);
            }

            if (previewUncategorized) {
                previewUncategorized.textContent = String(previewSummary.uncategorizedRows || 0);
            }

            if (previewUserRuleSuggested) {
                previewUserRuleSuggested.textContent = String(previewSummary.userRuleSuggestedRows || 0);
            }

            if (previewGlobalRuleSuggested) {
                previewGlobalRuleSuggested.textContent = String(previewSummary.globalRuleSuggestedRows || 0);
            }

            renderPreviewDecisionState(state, previewSummary, quota);

            renderMessages(previewWarnings, state.previewWarnings);
            renderMessages(previewErrors, state.previewErrors);

            if (quotaWarning) {
                quotaWarning.innerHTML = '';

                if (!quota.allowed) {
                    quotaWarning.hidden = false;
                    quotaWarning.append(document.createTextNode(`${quota.message || 'Limite de importação atingido para o plano atual.'} `));

                    const link = document.createElement('a');
                    link.className = 'imp-link';
                    link.href = upgradeUrl;
                    link.textContent = 'Fazer upgrade';
                    quotaWarning.appendChild(link);
                } else {
                    quotaWarning.hidden = true;
                }
            }

            if (submitButton) {
                submitButton.disabled = !canSubmit();
                submitButton.textContent = state.previewStatus === 'loading_preview' ? 'Preparando...' : 'Preparar preview';
            }

            if (confirmButton) {
                confirmButton.disabled = !quota.allowed
                    || !(state.previewStatus === 'preview_ready' && state.previewCanConfirm === true);
                confirmButton.textContent = state.previewStatus === 'confirming'
                    ? 'Confirmando...'
                    : 'Confirmar importacao';
            }

            if (previewTools) {
                previewTools.hidden = !hasRows || !isContaOfxPreviewActive();
            }

            if (confirmStrip) {
                confirmStrip.hidden = !showReviewChrome;
            }

            if (categorizePreviewButton) {
                categorizePreviewButton.disabled = !hasRows
                    || !isContaOfxPreviewActive()
                    || state.previewStatus !== 'preview_ready';
                categorizePreviewButton.textContent = previewSummary.categorizationApplied
                    ? 'Reaplicar categorizacao'
                    : 'Categorizar linhas';
            }

            if (categorizePreviewHelper) {
                categorizePreviewHelper.textContent = previewSummary.categorizationApplied
                    ? `${previewSummary.userRuleSuggestedRows || 0} linha(s) sugerida(s) por regra do usuario e ${previewSummary.globalRuleSuggestedRows || 0} por regra global.`
                    : 'Opcional: aplica sugestoes automaticas por regra do usuario e regra global sem bloquear a confirmacao.';
            }

            if (pendingOnlyToggle) {
                pendingOnlyToggle.checked = state.showOnlyPendingCategories === true;
                pendingOnlyToggle.disabled = !hasRows || !isContaOfxPreviewActive() || interactionsLocked;
            }

            if (previewTableWrap) {
                previewTableWrap.hidden = !hasRenderableRows;
            }

            if (previewFooter) {
                previewFooter.hidden = !showPreviewFooter;
            }

            if (previewEmpty) {
                previewEmpty.hidden = hasRenderableRows;
            }

            if (previewEmptyTitle) {
                if (!hasSelectedFile && state.previewStatus === 'idle') {
                    previewEmptyTitle.textContent = 'Envie um arquivo para gerar o preview';
                } else if (hasSelectedFile && state.previewStatus === 'file_selected') {
                    previewEmptyTitle.textContent = 'Arquivo pronto para gerar o preview';
                } else if (state.previewStatus === 'loading_preview') {
                    previewEmptyTitle.textContent = 'Estamos preparando a revisão do lote';
                } else if (hasRows && state.showOnlyPendingCategories && (previewSummary.uncategorizedRows || 0) === 0) {
                    previewEmptyTitle.textContent = 'Nenhuma linha pendente neste filtro';
                } else {
                    previewEmptyTitle.textContent = 'O preview aparece aqui depois do preparo';
                }
            }

            if (previewEmptyCopy) {
                if (!hasSelectedFile && state.previewStatus === 'idle') {
                    previewEmptyCopy.textContent = 'Escolha o contexto e envie OFX ou CSV para montar o preview.';
                } else if (hasSelectedFile && state.previewStatus === 'file_selected') {
                    previewEmptyCopy.textContent = 'Arquivo pronto. Clique em "Preparar preview".';
                } else if (state.previewStatus === 'loading_preview') {
                    previewEmptyCopy.textContent = 'Lendo o arquivo e preparando a revisão.';
                } else if (hasRows && state.showOnlyPendingCategories && (previewSummary.uncategorizedRows || 0) === 0) {
                    previewEmptyCopy.textContent = 'Todas as linhas visíveis já estão categorizadas. Desative o filtro para revisar o lote completo.';
                } else {
                    previewEmptyCopy.textContent = 'Resumo do arquivo, validações, linhas normalizadas e categorização opcional serão exibidos aqui.';
                }
            } else if (previewEmpty) {
                previewEmpty.textContent = hasRows && state.showOnlyPendingCategories && (previewSummary.uncategorizedRows || 0) === 0
                    ? 'Todas as linhas já estão categorizadas.'
                    : 'O preview será exibido aqui com resumo do arquivo, validações, linhas normalizadas e categorização opcional.';
            }

            if (hasRenderableRows) {
                renderPreviewRows(filteredRows);
            } else {
                clearPreviewRows();
            }

            const statusMessage = !quota.allowed
                ? (quota.message || 'Limite de importação atingido para o plano atual.')
                : (
                    state.previewStatus === 'preview_error' && Array.isArray(state.previewErrors) && state.previewErrors.length > 0
                        ? state.previewErrors[0]
                        : (STATUS_MESSAGES[state.previewStatus] || STATUS_MESSAGES.idle)
                );

            setStatusMessage(statusMessage);

            if (previewNextStep) {
                if (state.previewStatus === 'preview_ready') {
                    const blockingMessage = Array.isArray(state.previewErrors) && state.previewErrors.length > 0
                        ? state.previewErrors[0]
                        : 'Preview retornou bloqueios. Ajuste os dados e envie novamente.';

                    previewNextStep.textContent = state.previewCanConfirm
                        ? ((previewSummary.categorizationApplied !== true && isContaOfxPreviewActive())
                            ? 'Preview valido. Voce pode confirmar agora ou clicar em "Categorizar linhas" para sugerir categorias antes da importacao.'
                            : ((previewSummary.uncategorizedRows || 0) > 0
                                ? `${previewSummary.uncategorizedRows} linha(s) ainda sem categoria. Voce pode confirmar agora ou revisar antes da importacao.`
                                : 'Preview valido. Clique em "Confirmar importacao" para persistir os dados.'))
                        : blockingMessage;
                } else if (state.previewStatus === 'confirming') {
                    previewNextStep.textContent = String(state.jobProgressMessage || getProcessingMessage());
                } else if (state.previewStatus === 'preview_error') {
                    previewNextStep.textContent = quota.allowed
                        ? (state.previewErrors[0] || 'Nao foi possivel concluir a etapa. Revise os dados e tente novamente.')
                        : 'Limite do plano atingido para este fluxo. Faca upgrade para continuar.';
                } else if (state.previewStatus === 'confirmed') {
                    previewNextStep.textContent = getCompletedMessage(previewSummary);
                } else {
                    previewNextStep.textContent = 'Revise o preview para liberar a confirmacao.';
                }
            }
        }
    }
}