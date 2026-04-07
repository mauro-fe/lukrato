import '../../../css/admin/importacoes/index.css';
import {
    appendCsrfToken,
    bootImportacoesPage,
    fetchApiJson,
    normalizeSourceType,
} from './app.js';
import { initCustomize } from './customize.js';

const context = bootImportacoesPage('index');

if (context) {
    initCustomize();

    const sourceInputs = Array.from(context.root.querySelectorAll('[data-imp-source-type]'));
    const targetInputs = Array.from(context.root.querySelectorAll('[data-imp-target-type]'));
    const accountField = context.root.querySelector('[data-imp-account-field]');
    const accountSelect = context.root.querySelector('[data-imp-account-select-main]');
    const cardField = context.root.querySelector('[data-imp-card-field]');
    const cardSelect = context.root.querySelector('[data-imp-card-select-main]');
    const targetSourceHint = context.root.querySelector('[data-imp-target-source-hint]');
    const fileDrop = context.root.querySelector('[data-imp-file-drop]');
    const fileInput = context.root.querySelector('[data-imp-file-input]');
    const selectedFileLabel = context.root.querySelector('[data-imp-selected-file]');
    const fileNote = context.root.querySelector('[data-imp-file-note]');
    const submitButton = context.root.querySelector('[data-imp-submit]');
    const previewState = context.root.querySelector('[data-imp-preview-state]');
    const previewBadge = context.root.querySelector('[data-imp-preview-badge]');
    const previewTarget = context.root.querySelector('[data-imp-preview-target]');
    const previewContextLabel = context.root.querySelector('[data-imp-preview-context-label]');
    const previewSourceType = context.root.querySelector('[data-imp-preview-source-type]');
    const previewFileName = context.root.querySelector('[data-imp-preview-file-name]');
    const previewTotalRows = context.root.querySelector('[data-imp-preview-total-rows]');
    const previewCategorized = context.root.querySelector('[data-imp-preview-categorized]');
    const previewUncategorized = context.root.querySelector('[data-imp-preview-uncategorized]');
    const previewUserRuleSuggested = context.root.querySelector('[data-imp-preview-user-rule-suggested]');
    const previewGlobalRuleSuggested = context.root.querySelector('[data-imp-preview-global-rule-suggested]');
    const previewWarnings = context.root.querySelector('[data-imp-preview-warnings]');
    const previewErrors = context.root.querySelector('[data-imp-preview-errors]');
    const previewEmpty = context.root.querySelector('[data-imp-preview-empty]');
    const previewTools = context.root.querySelector('[data-imp-preview-tools]');
    const previewTableWrap = context.root.querySelector('[data-imp-preview-table-wrap]');
    const previewRowsBody = context.root.querySelector('[data-imp-preview-rows]');
    const previewNextStep = context.root.querySelector('[data-imp-preview-next-step]');
    const categorizePreviewButton = context.root.querySelector('[data-imp-categorize-preview]');
    const categorizePreviewHelper = context.root.querySelector('[data-imp-categorize-helper]');
    const confirmButton = context.root.querySelector('[data-imp-confirm]');
    const pendingOnlyToggle = context.root.querySelector('[data-imp-filter-pending-only]');
    const advancedDescription = context.root.querySelector('[data-imp-advanced-description]');
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
    const previewEndpoint = String(context.root.dataset.impPreviewEndpoint || '').trim();
    const confirmEndpoint = String(context.root.dataset.impConfirmEndpoint || '').trim();
    const configApiEndpoint = String(context.root.dataset.impConfigEndpoint || '').trim();
    const configPageBaseUrl = String(context.root.dataset.impConfigPageBaseUrl || '').trim();
    const jobStatusEndpointBase = String(context.root.dataset.impJobStatusEndpointBase || '').trim();
    const csvTemplateAutoEndpoint = String(context.root.dataset.impCsvTemplateAutoEndpoint || '').trim();
    const csvTemplateManualEndpoint = String(context.root.dataset.impCsvTemplateManualEndpoint || '').trim();
    const csvTemplateCardAutoEndpoint = String(context.root.dataset.impCsvTemplateCardAutoEndpoint || '').trim();
    const csvTemplateCardManualEndpoint = String(context.root.dataset.impCsvTemplateCardManualEndpoint || '').trim();
    const categoriesEndpoint = String(context.root.dataset.impCategoriesEndpoint || '').trim();
    const subcategoriesEndpointBase = String(context.root.dataset.impSubcategoriesEndpointBase || '').trim();
    const quotaWarning = context.root.querySelector('[data-imp-quota-warning]');
    const planTier = String(context.root.dataset.impPlan || 'free').trim().toLowerCase();
    const upgradeUrl = String(context.root.dataset.impUpgradeUrl || '/assinatura').trim() || '/assinatura';
    const confirmAsyncDefault = ['1', 'true', 'yes', 'on'].includes(
        String(context.root.dataset.impConfirmAsyncDefault || '0').trim().toLowerCase()
    );
    let activeJobId = null;
    let pollingTimer = null;
    let fileDropDragDepth = 0;
    let fileInspectionToken = 0;

    const decodeImportLimits = () => {
        const encoded = String(context.root.dataset.impImportLimits || '').trim();
        if (!encoded) {
            return {};
        }

        try {
            const raw = atob(encoded);
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch {
            return {};
        }
    };

    const importLimitsByBucket = decodeImportLimits();
    const decodeProfileConfig = () => {
        const encoded = String(context.root.dataset.impProfileConfig || '').trim();
        if (!encoded) {
            return null;
        }

        try {
            const raw = atob(encoded);
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : null;
        } catch {
            return null;
        }
    };

    const initialProfileConfig = decodeProfileConfig();
    let categoryOptions = [];
    let categoryCatalogError = '';
    const subcategoryCache = new Map();
    let activeProfileConfig = null;
    let profileLoadState = 'idle';
    let profileLoadError = '';
    let profileRequestToken = 0;

    const STATUS_MESSAGES = {
        idle: 'Selecione alvo, formato e arquivo para montar o preview.',
        file_selected: 'Arquivo selecionado. Clique em "Preparar preview".',
        loading_preview: 'Preparando preview e validando conteúdo do arquivo.',
        preview_ready: 'Preview pronto. Revise as linhas, categorize se quiser e confirme.',
        preview_error: 'Não foi possível preparar o preview. Revise os dados e tente novamente.',
        confirming: 'Seu arquivo está sendo importado...',
        confirmed: 'Concluído.',
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

    const parsePositiveInt = (value) => {
        const parsed = Number.parseInt(String(value || ''), 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    };

    const normalizeBooleanSetting = (value, fallback = true) => {
        if (typeof value === 'boolean') {
            return value;
        }

        const normalized = String(value ?? '').trim().toLowerCase();
        if (!normalized) {
            return fallback;
        }

        return !['0', 'false', 'no', 'nao', 'não', 'off'].includes(normalized);
    };

    const normalizeProfileConfig = (profile) => {
        if (!profile || typeof profile !== 'object') {
            return null;
        }

        const options = profile.options && typeof profile.options === 'object' ? profile.options : {};
        const csvHasHeader = normalizeBooleanSetting(options.csv_has_header, true);
        const csvColumnMap = options.csv_column_map && typeof options.csv_column_map === 'object'
            ? options.csv_column_map
            : {};
        const csvStartRow = Number.parseInt(
            String(options.csv_start_row ?? (csvHasHeader ? 2 : 1)),
            10,
        );

        return {
            contaId: parsePositiveInt(profile.conta_id ?? null),
            sourceType: normalizeSourceType(profile.source_type || 'ofx'),
            csvMappingMode: String(options.csv_mapping_mode || 'auto').trim().toLowerCase() === 'manual'
                ? 'manual'
                : 'auto',
            csvHasHeader,
            csvStartRow: Number.isFinite(csvStartRow) && csvStartRow > 0 ? csvStartRow : (csvHasHeader ? 2 : 1),
            csvDelimiter: String(options.csv_delimiter || ';') || ';',
            csvDateFormat: String(options.csv_date_format || 'd/m/Y').trim() || 'd/m/Y',
            csvDecimalSeparator: String(options.csv_decimal_separator || ',').trim() === '.' ? '.' : ',',
            csvColumnMap: {
                tipo: String(csvColumnMap.tipo || '').trim().toUpperCase(),
                data: String(csvColumnMap.data || '').trim().toUpperCase(),
                descricao: String(csvColumnMap.descricao || '').trim().toUpperCase(),
                valor: String(csvColumnMap.valor || '').trim().toUpperCase(),
                categoria: String(csvColumnMap.categoria || '').trim().toUpperCase(),
                subcategoria: String(csvColumnMap.subcategoria || '').trim().toUpperCase(),
                observacao: String(csvColumnMap.observacao || '').trim().toUpperCase(),
                id_externo: String(csvColumnMap.id_externo || '').trim().toUpperCase(),
            },
        };
    };

    activeProfileConfig = normalizeProfileConfig(initialProfileConfig);
    profileLoadState = activeProfileConfig ? 'ready' : 'idle';

    const isTruthyFlag = (value) => ['1', 'true', 'yes', 'on'].includes(String(value || '0').trim().toLowerCase());

    const currentSourceType = () => {
        const checked = sourceInputs.find((input) => input.checked);
        return normalizeSourceType(checked?.value || 'ofx');
    };

    const normalizeImportTarget = (value, fallback = 'conta') => {
        const normalized = String(value || '').trim().toLowerCase();
        return normalized === 'cartao' || normalized === 'conta' ? normalized : fallback;
    };

    const currentImportTarget = () => {
        const checked = targetInputs.find((input) => input.checked);
        return normalizeImportTarget(checked?.value || 'conta');
    };

    const isCardTarget = () => context.state.selectedImportTarget === 'cartao';

    const currentAccountLabel = () => {
        if (!accountSelect || accountSelect.selectedIndex < 0) {
            return 'Conta não selecionada';
        }
        return String(accountSelect.options[accountSelect.selectedIndex]?.text || 'Conta não selecionada');
    };

    const currentCardLabel = () => {
        if (!cardSelect || cardSelect.selectedIndex < 0) {
            return 'Cartão não selecionado';
        }
        return String(cardSelect.options[cardSelect.selectedIndex]?.text || 'Cartão não selecionado');
    };

    const findAccountOptionById = (accountId) => {
        const normalizedAccountId = parsePositiveInt(accountId);
        if (!accountSelect || !normalizedAccountId) {
            return null;
        }

        return Array.from(accountSelect.options).find(
            (option) => parsePositiveInt(option.value) === normalizedAccountId,
        ) || null;
    };

    const resolveCardLinkedAccountId = () => {
        if (!cardSelect || cardSelect.selectedIndex < 0) {
            return null;
        }

        const option = cardSelect.options[cardSelect.selectedIndex];
        return parsePositiveInt(option?.dataset?.linkedAccountId ?? null);
    };

    const resolveActiveConfigAccountId = (importTarget = context.state.selectedImportTarget) => {
        if (String(importTarget || '').trim().toLowerCase() === 'cartao') {
            return resolveCardLinkedAccountId();
        }

        return accountSelect ? parsePositiveInt(accountSelect.value) : context.state.selectedAccountId;
    };

    const resolveActiveConfigAccountLabel = () => {
        const accountId = resolveActiveConfigAccountId();
        const option = findAccountOptionById(accountId);
        if (option) {
            return String(option.text || `Conta #${accountId}`);
        }

        return accountId ? `Conta #${accountId}` : 'Conta não selecionada';
    };

    const currentContextLabel = () => (isCardTarget() ? currentCardLabel() : currentAccountLabel());
    const currentTargetLabel = () => (isCardTarget() ? 'Cartão' : 'Conta');

    const resolveQuotaBucket = (importTarget, sourceType) => {
        if (importTarget === 'cartao') {
            return 'import_cartao_ofx';
        }

        return normalizeSourceType(sourceType, 'ofx') === 'csv'
            ? 'import_conta_csv'
            : 'import_conta_ofx';
    };

    const currentQuota = () => {
        if (planTier !== 'free') {
            return { allowed: true, limit: null, used: null, remaining: null, bucket: null, message: '' };
        }

        const bucket = resolveQuotaBucket(context.state.selectedImportTarget, context.state.selectedSourceType);
        const quota = importLimitsByBucket?.[bucket];
        if (!quota || typeof quota !== 'object') {
            return { allowed: true, limit: null, used: null, remaining: null, bucket, message: '' };
        }

        return {
            allowed: quota.allowed !== false,
            limit: Number.isFinite(Number(quota.limit)) ? Number(quota.limit) : quota.limit ?? null,
            used: Number.isFinite(Number(quota.used)) ? Number(quota.used) : quota.used ?? null,
            remaining: Number.isFinite(Number(quota.remaining)) ? Number(quota.remaining) : quota.remaining ?? null,
            bucket,
            message: String(quota.message || '').trim(),
        };
    };

    const formatMappingModeLabel = (mode) => (mode === 'manual' ? 'Manual' : 'Automático');

    const formatDelimiterLabel = (delimiter) => {
        const normalized = String(delimiter || '').trim();
        if (normalized === '\t') {
            return 'TAB';
        }

        return normalized || ';';
    };

    const formatSourceTypeLabel = (sourceType) => {
        const normalized = normalizeSourceType(sourceType, '');
        return normalized ? normalized.toUpperCase() : 'Arquivo';
    };

    const formatImportTargetLabel = (importTarget) => (
        normalizeImportTarget(importTarget, '') === 'cartao' ? 'Cartão/fatura' : 'Conta'
    );

    const detectSourceTypeFromFile = (file) => {
        if (!file) {
            return '';
        }

        const fileName = String(file.name || '').trim().toLowerCase();
        const mimeType = String(file.type || '').trim().toLowerCase();

        if (fileName.endsWith('.csv') || mimeType.includes('csv') || mimeType.includes('excel')) {
            return 'csv';
        }

        if (fileName.endsWith('.ofx') || fileName.endsWith('.qfx') || mimeType.includes('ofx') || mimeType.includes('qfx')) {
            return 'ofx';
        }

        return '';
    };

    const readTextFromFile = async (file) => {
        if (!file) {
            return '';
        }

        if (typeof file.text === 'function') {
            return file.text();
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result || ''));
            reader.onerror = () => reject(reader.error || new Error('Não foi possível ler o arquivo selecionado.'));
            reader.readAsText(file);
        });
    };

    const detectImportTargetFromOfxContents = (contents) => {
        const normalizedContents = String(contents || '').trim();
        if (!normalizedContents) {
            return '';
        }

        const cardTags = ['CREDITCARDMSGSRSV1', 'CCSTMTTRNRS', 'CCSTMTRS', 'CCACCTFROM'];
        const accountTags = ['BANKMSGSRSV1', 'STMTTRNRS', 'STMTRS', 'BANKACCTFROM'];
        const hasTag = (tag) => new RegExp(`<\\s*${tag}\\b`, 'i').test(normalizedContents);

        const cardMatches = cardTags.filter(hasTag);
        const accountMatches = accountTags.filter(hasTag);

        if (cardMatches.length > 0 && accountMatches.length === 0) {
            return 'cartao';
        }

        if (accountMatches.length > 0 && cardMatches.length === 0) {
            return 'conta';
        }

        return '';
    };

    const applyGuideCard = (cardElement, titleElement, copyElement, guide) => {
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
    };

    const isContextSelected = (importTarget = context.state.selectedImportTarget) => {
        const normalizedTarget = String(importTarget || '').trim().toLowerCase() === 'cartao' ? 'cartao' : 'conta';

        if (normalizedTarget === 'cartao') {
            return Boolean(context.state.selectedCardId);
        }

        return Boolean(context.state.selectedAccountId);
    };

    const buildColumnMapSummary = (profile, importTarget) => {
        const csvColumnMap = profile?.csvColumnMap && typeof profile.csvColumnMap === 'object'
            ? profile.csvColumnMap
            : {};
        const columnLabels = importTarget === 'cartao'
            ? {
                data: 'Data',
                descricao: 'Descrição',
                valor: 'Valor',
                observacao: 'Observação',
                id_externo: 'ID externo',
            }
            : {
                tipo: 'Tipo',
                data: 'Data',
                descricao: 'Descrição',
                valor: 'Valor',
                categoria: 'Categoria',
                subcategoria: 'Subcategoria',
            };

        const parts = Object.entries(columnLabels)
            .map(([key, label]) => {
                const columnReference = String(csvColumnMap[key] || '').trim().toUpperCase();
                return columnReference ? `${label}: ${columnReference}` : '';
            })
            .filter(Boolean);

        return parts.length > 0 ? parts.join(' | ') : 'Padrão Lukrato';
    };

    const buildPathTitle = (importTarget, sourceType) => {
        if (sourceType === 'csv') {
            return importTarget === 'cartao' ? 'CSV de fatura guiado' : 'CSV de conta no padrão Lukrato';
        }

        return importTarget === 'cartao' ? 'OFX de fatura do cartão' : 'OFX de extrato bancário';
    };

    const buildUploadPrompt = (importTarget, sourceType) => {
        if (sourceType === 'csv') {
            return importTarget === 'cartao'
                ? {
                    title: 'Envie o CSV da fatura',
                    copy: 'O melhor cenário aqui é data, descrição e valor legíveis; sem coluna de tipo, valor positivo vira despesa e negativo vira estorno.',
                }
                : {
                    title: 'Envie o CSV no padrão de conta',
                    copy: 'Esperamos tipo;data;descricao;valor com ;, datas dd/mm/yyyy, vírgula decimal e sem linhas incompletas no final.',
                };
        }

        return importTarget === 'cartao'
            ? {
                title: 'Envie o OFX da fatura',
                copy: 'Compras e parcelas do MEMO entram automáticas, inclusive quando vier algo como "Parcela 3/6".',
            }
            : {
                title: 'Envie o OFX do extrato',
                copy: 'Pix, depósito e histórico entram automáticos, mesmo se o banco usar TRNTYPE genérico.',
            };
    };

    const buildPreviewReadyCopy = (importTarget, sourceType, autoAdjusted = false) => {
        if (sourceType === 'csv') {
            if (importTarget === 'cartao') {
                return autoAdjusted
                    ? 'Formato sincronizado para CSV. Agora valide data, descrição e valor da fatura no preview.'
                    : 'CSV pronto. Clique em "Preparar preview" para validar a fatura antes da confirmação.';
            }

            return autoAdjusted
                ? 'Formato sincronizado para CSV. Agora valide cabeçalho, datas e valores no padrão tipo;data;descricao;valor.'
                : 'CSV pronto. Clique em "Preparar preview" para validar cabeçalho, datas e valores.';
        }

        if (importTarget === 'cartao') {
            return autoAdjusted
                ? 'Formato sincronizado para OFX. Agora clique em "Preparar preview" para ler compras e parcelas da fatura.'
                : 'OFX pronto. Clique em "Preparar preview" para ler compras e parcelas da fatura automaticamente.';
        }

        return autoAdjusted
            ? 'Formato sincronizado para OFX. Agora clique em "Preparar preview" para ler o extrato automaticamente.'
            : 'OFX pronto. Clique em "Preparar preview" para ler data, valor e histórico do extrato.';
    };

    const buildDetectedFileNoteText = (importTarget, sourceType, autoAdjusted = false) => {
        if (sourceType === 'csv') {
            if (importTarget === 'cartao') {
                return autoAdjusted
                    ? 'Detectamos um CSV e alinhamos o formato. Se vier sem coluna de tipo, valor positivo entra como despesa e negativo como estorno.'
                    : 'CSV detectado. Se vier sem coluna de tipo, valor positivo entra como despesa e negativo como estorno.';
            }

            return autoAdjusted
                ? 'Detectamos um CSV e alinhamos o formato. O melhor encaixe aqui é tipo;data;descricao;valor com ;, dd/mm/yyyy, vírgula decimal e sem linhas incompletas no final.'
                : 'CSV detectado. O melhor encaixe aqui é tipo;data;descricao;valor com ;, dd/mm/yyyy, vírgula decimal e sem linhas incompletas no final.';
        }

        if (importTarget === 'cartao') {
            return autoAdjusted
                ? 'Detectamos um OFX e alinhamos o formato. Compras e parcelas da fatura podem vir no MEMO sem exigir mapeamento manual.'
                : 'OFX detectado. Compras e parcelas da fatura podem vir no MEMO sem exigir mapeamento manual.';
        }

        return autoAdjusted
            ? 'Detectamos um OFX e alinhamos o formato. O Lukrato usa data, valor e histórico mesmo quando o banco usa TRNTYPE genérico.'
            : 'OFX detectado. O Lukrato usa data, valor e histórico mesmo quando o banco usa TRNTYPE genérico.';
    };

    const buildDetectedImportTargetNoteText = (detectedImportTarget, autoAdjusted = false) => {
        const normalizedTarget = normalizeImportTarget(detectedImportTarget, '');
        if (!normalizedTarget) {
            return '';
        }

        if (normalizedTarget === 'cartao') {
            return autoAdjusted
                ? 'O conteúdo do OFX foi reconhecido como cartão/fatura e o alvo foi ajustado automaticamente. Revise apenas o cartão antes do preview.'
                : 'O conteúdo do OFX foi reconhecido como cartão/fatura. Revise apenas o cartão antes do preview.';
        }

        return autoAdjusted
            ? 'O conteúdo do OFX foi reconhecido como conta bancária e o alvo foi ajustado automaticamente para Conta.'
            : 'O conteúdo do OFX foi reconhecido como conta bancária.';
    };

    const buildAdvancedDescription = (importTarget, sourceType) => {
        if (sourceType === 'csv') {
            return importTarget === 'cartao'
                ? 'Se a fatura vier em CSV, basta manter data, descrição e valor legíveis. Sem coluna de tipo, valor positivo vira despesa e negativo vira estorno; abra o avançado só se cabeçalho, delimitador ou data fugirem do padrão.'
                : 'Para CSV de conta no padrão Lukrato, use tipo;data;descricao;valor com ;, datas em dd/mm/yyyy, valores com vírgula e remova linhas vazias ou incompletas no fim. Abra o avançado só se o arquivo fugir disso.';
        }

        return importTarget === 'cartao'
            ? 'OFX de fatura entra automático. Compras parceladas podem vir no MEMO, como "Parcela 3/6", sem exigir mapeamento manual.'
            : 'OFX bancário entra automático, mesmo quando o banco marca tudo como TRNTYPE genérico. O Lukrato usa data, valor e histórico do extrato.';
    };

    const buildTemplateMeta = (importTarget) => {
        if (importTarget === 'cartao') {
            return {
                chip: 'Modelo de fatura',
                title: 'Modelo recomendado para CSV de cartão/fatura',
                copy: 'O modelo automático cobre data, descrição e valor. Use o manual se a operadora exportar observação, ID externo ou colunas extras fora do padrão esperado.',
                autoLabel: 'Baixar modelo rápido de fatura',
                manualLabel: 'Baixar modelo completo de fatura',
                autoHref: csvTemplateCardAutoEndpoint,
                manualHref: csvTemplateCardManualEndpoint,
                contextNote: 'A configuração CSV usa a conta vinculada ao cartão selecionado.',
                summaryContext: 'Conta vinculada ao cartão selecionado',
                badge: 'Conta vinculada',
            };
        }

        return {
            chip: 'Modelo de conta',
            title: 'Modelo recomendado para CSV de conta',
            copy: 'O modelo rápido segue o padrão tipo;data;descricao;valor com ;, dd/mm/yyyy e valores como 149,90. O manual adiciona categoria, subcategoria, observação e ID externo.',
            autoLabel: 'Baixar modelo rápido de conta',
            manualLabel: 'Baixar modelo completo de conta',
            autoHref: csvTemplateAutoEndpoint,
            manualHref: csvTemplateManualEndpoint,
            contextNote: 'A configuração CSV usa a conta selecionada neste fluxo.',
            summaryContext: 'Conta selecionada',
            badge: 'Conta ativa',
        };
    };

    const buildPathGuide = (state) => {
        const importTarget = state.selectedImportTarget;
        const sourceType = normalizeSourceType(state.selectedSourceType, 'ofx');
        const detectedSourceType = normalizeSourceType(state.selectedFileDetectedSourceType, '');
        const detectedImportTarget = normalizeImportTarget(state.selectedFileDetectedImportTarget, '');
        const title = buildPathTitle(importTarget, sourceType);

        if (state.selectedFile && detectedSourceType && detectedSourceType !== sourceType) {
            return {
                state: 'warning',
                title,
                copy: `Arquivo ${formatSourceTypeLabel(detectedSourceType)} detectado, mas o formato ativo está em ${formatSourceTypeLabel(sourceType)}. Alinhe antes do preview para evitar erro de leitura.`,
            };
        }

        if (
            state.selectedFile
            && detectedSourceType === 'ofx'
            && detectedImportTarget
            && detectedImportTarget !== importTarget
        ) {
            return {
                state: 'warning',
                title,
                copy: `O conteúdo do OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo ativo está em ${formatImportTargetLabel(importTarget)}.`,
            };
        }

        if (
            state.selectedFile
            && detectedSourceType === 'ofx'
            && detectedImportTarget
            && state.targetAutoAdjustedToDetectedFile
        ) {
            return {
                state: 'ready',
                title,
                copy: `${buildDetectedImportTargetNoteText(detectedImportTarget, true)} ${buildAdvancedDescription(importTarget, sourceType)}`,
            };
        }

        if (state.selectedFile && detectedSourceType && state.sourceAutoAdjustedToDetectedFile) {
            return {
                state: 'ready',
                title,
                copy: `Arquivo ${formatSourceTypeLabel(detectedSourceType)} detectado e o formato foi sincronizado automaticamente. ${buildAdvancedDescription(importTarget, sourceType)}`,
            };
        }

        return {
            state: state.selectedFile && detectedSourceType ? 'ready' : 'info',
            title,
            copy: buildAdvancedDescription(importTarget, sourceType),
        };
    };

    const buildContextGuide = (state, activeConfigAccountId, profileDisplay) => {
        if (state.selectedImportTarget === 'cartao') {
            if (!state.selectedCardId) {
                return {
                    state: 'warning',
                    title: 'Selecione um cartão',
                    copy: 'Escolha o cartão/fatura que vai receber o arquivo antes de preparar o preview.',
                };
            }

            if (!activeConfigAccountId) {
                return {
                    state: 'warning',
                    title: currentCardLabel(),
                    copy: 'Este cartão não tem conta vinculada para herdar o perfil CSV. OFX pode seguir, mas CSV pode exigir ajuste manual.',
                };
            }

            return {
                state: 'ready',
                title: currentCardLabel(),
                copy: state.targetAutoAdjustedToDetectedFile
                    ? `${buildDetectedImportTargetNoteText('cartao', true)} Perfil CSV herdado de ${profileDisplay.accountLabel}.`
                    : `Perfil CSV herdado de ${profileDisplay.accountLabel}.`,
            };
        }

        if (!state.selectedAccountId) {
            return {
                state: 'warning',
                title: 'Selecione uma conta',
                copy: 'Escolha a conta que vai receber o arquivo para liberar o preview.',
            };
        }

        return {
            state: 'ready',
            title: currentAccountLabel(),
            copy: state.targetAutoAdjustedToDetectedFile
                ? `${buildDetectedImportTargetNoteText('conta', true)} A conta selecionada define o contexto do preview e do perfil CSV aplicado.`
                : 'A conta selecionada define o contexto do preview e do perfil CSV aplicado.',
        };
    };

    const buildReadinessGuide = (state, quota) => {
        const detectedSourceType = normalizeSourceType(state.selectedFileDetectedSourceType, '');
        const detectedImportTarget = normalizeImportTarget(state.selectedFileDetectedImportTarget, '');
        const hasContext = isContextSelected(state.selectedImportTarget);

        if (!quota.allowed) {
            return {
                state: 'warning',
                title: 'Limite do plano atingido',
                copy: quota.message || 'Faça upgrade para continuar usando este fluxo de importação.',
            };
        }

        if (state.previewStatus === 'loading_preview') {
            return {
                state: 'info',
                title: 'Montando preview',
                copy: 'Validando o arquivo, normalizando linhas e preparando a revisão final.',
            };
        }

        if (state.previewStatus === 'confirming') {
            return {
                state: 'info',
                title: 'Importação em andamento',
                copy: String(state.jobProgressMessage || getProcessingMessage()),
            };
        }

        if (state.previewStatus === 'confirmed') {
            return {
                state: 'ready',
                title: 'Importação concluída',
                copy: getCompletedMessage(),
            };
        }

        if (state.previewStatus === 'preview_error') {
            return {
                state: 'warning',
                title: 'Ajuste antes de reenviar',
                copy: state.previewErrors[0] || 'Revise o contexto, o formato e o arquivo antes de tentar novamente.',
            };
        }

        if (state.previewStatus === 'preview_ready') {
            if (!state.previewCanConfirm) {
                return {
                    state: 'warning',
                    title: 'Preview com bloqueios',
                    copy: state.previewErrors[0] || 'O preview retornou bloqueios que precisam ser corrigidos antes da confirmação.',
                };
            }

            if (isContaOfxPreviewActive(state.selectedImportTarget, state.selectedSourceType)
                && (state.previewSummary.uncategorizedRows || 0) > 0) {
                return {
                    state: 'ready',
                    title: 'Preview pronto para revisar',
                    copy: `${state.previewSummary.uncategorizedRows} linha(s) ainda sem categoria. Você pode confirmar agora ou revisar antes da importação.`,
                };
            }

            return {
                state: 'ready',
                title: 'Preview pronto para confirmar',
                copy: 'Revise as linhas e confirme a importação quando estiver tudo certo.',
            };
        }

        if (!hasContext) {
            return {
                state: 'warning',
                title: state.selectedImportTarget === 'cartao' ? 'Falta escolher o cartão' : 'Falta escolher a conta',
                copy: 'Defina o contexto da importação antes de enviar o arquivo.',
            };
        }

        if (!state.selectedFile) {
            const uploadPrompt = buildUploadPrompt(state.selectedImportTarget, state.selectedSourceType);
            return {
                state: 'info',
                title: uploadPrompt.title,
                copy: uploadPrompt.copy,
            };
        }

        if (!detectedSourceType) {
            return {
                state: 'warning',
                title: 'Revise o tipo do arquivo',
                copy: 'Não foi possível identificar automaticamente se o arquivo é OFX ou CSV. Confira a extensão antes do preview.',
            };
        }

        if (
            detectedSourceType === 'ofx'
            && detectedImportTarget
            && detectedImportTarget !== state.selectedImportTarget
        ) {
            return {
                state: 'warning',
                title: 'OFX e alvo divergem',
                copy: `O conteúdo do OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo ativo está em ${formatImportTargetLabel(state.selectedImportTarget)}.`,
            };
        }

        if (detectedSourceType !== state.selectedSourceType) {
            return {
                state: 'warning',
                title: 'Formato e arquivo divergem',
                copy: `O arquivo parece ser ${formatSourceTypeLabel(detectedSourceType)}, mas o formato ativo está em ${formatSourceTypeLabel(state.selectedSourceType)}.`,
            };
        }

        return {
            state: 'ready',
            title: 'Pronto para gerar preview',
            copy: buildPreviewReadyCopy(
                state.selectedImportTarget,
                detectedSourceType,
                state.sourceAutoAdjustedToDetectedFile,
            ),
        };
    };

    const buildFileNote = (state) => {
        if (!state.selectedFile) {
            return null;
        }

        const detectedSourceType = normalizeSourceType(state.selectedFileDetectedSourceType, '');
        const detectedImportTarget = normalizeImportTarget(state.selectedFileDetectedImportTarget, '');
        if (!detectedSourceType) {
            return {
                state: 'warning',
                text: 'Não foi possível detectar automaticamente se o arquivo é OFX ou CSV. Revise a extensão antes do preview.',
            };
        }

        if (
            detectedSourceType === 'ofx'
            && detectedImportTarget
            && detectedImportTarget !== state.selectedImportTarget
        ) {
            return {
                state: 'warning',
                text: `O conteúdo do OFX parece ser de ${formatImportTargetLabel(detectedImportTarget)}, mas o alvo selecionado está em ${formatImportTargetLabel(state.selectedImportTarget)}.`,
            };
        }

        if (detectedSourceType === 'ofx' && detectedImportTarget && state.targetAutoAdjustedToDetectedFile) {
            return {
                state: 'ready',
                text: buildDetectedImportTargetNoteText(detectedImportTarget, true),
            };
        }

        if (detectedSourceType === 'ofx' && detectedImportTarget) {
            return {
                state: 'info',
                text: buildDetectedImportTargetNoteText(detectedImportTarget, false),
            };
        }

        if (detectedSourceType !== state.selectedSourceType) {
            return {
                state: 'warning',
                text: `Arquivo ${formatSourceTypeLabel(detectedSourceType)} detectado, mas o formato selecionado está em ${formatSourceTypeLabel(state.selectedSourceType)}.`,
            };
        }

        if (state.sourceAutoAdjustedToDetectedFile) {
            return {
                state: 'ready',
                text: buildDetectedFileNoteText(
                    state.selectedImportTarget,
                    detectedSourceType,
                    true,
                ),
            };
        }

        return {
            state: 'info',
            text: buildDetectedFileNoteText(
                state.selectedImportTarget,
                detectedSourceType,
                false,
            ),
        };
    };

    const buildConfigUrl = (accountId) => {
        if (!configPageBaseUrl) {
            return '';
        }

        return accountId ? `${configPageBaseUrl}?conta_id=${accountId}` : configPageBaseUrl;
    };

    const syncFileInputFiles = (files) => {
        if (!fileInput || !files || typeof DataTransfer !== 'function') {
            return;
        }

        try {
            const transfer = new DataTransfer();
            Array.from(files).forEach((file) => {
                if (file) {
                    transfer.items.add(file);
                }
            });
            fileInput.files = transfer.files;
        } catch {
            // Ignore browsers that do not allow setting FileList programmatically.
        }
    };

    const applySelectedFile = async (file) => {
        stopJobPolling();
        activeJobId = null;

        const inspectionToken = ++fileInspectionToken;

        const detectedSourceType = detectSourceTypeFromFile(file);
        const previousImportTarget = normalizeImportTarget(context.state.selectedImportTarget);
        const previousSourceType = normalizeSourceType(context.state.selectedSourceType, currentSourceType());
        const shouldAutoAdjustSource = Boolean(file && detectedSourceType && detectedSourceType !== previousSourceType);
        const nextSourceType = shouldAutoAdjustSource ? detectedSourceType : previousSourceType;
        let detectedImportTarget = '';
        let shouldAutoAdjustTarget = false;
        let nextImportTarget = previousImportTarget;

        if (file && detectedSourceType === 'ofx') {
            try {
                const fileContents = await readTextFromFile(file);
                if (inspectionToken !== fileInspectionToken) {
                    return;
                }

                detectedImportTarget = detectImportTargetFromOfxContents(fileContents);
                shouldAutoAdjustTarget = Boolean(detectedImportTarget && detectedImportTarget !== previousImportTarget);
                nextImportTarget = shouldAutoAdjustTarget ? detectedImportTarget : previousImportTarget;
            } catch {
                detectedImportTarget = '';
                shouldAutoAdjustTarget = false;
                nextImportTarget = previousImportTarget;
            }
        }

        if (inspectionToken !== fileInspectionToken) {
            return;
        }

        if (shouldAutoAdjustSource) {
            syncSourceInputSelection(nextSourceType);
        }

        if (shouldAutoAdjustTarget) {
            syncTargetInputSelection(nextImportTarget);
        }

        syncSourceTypeAvailability();

        context.setState({
            selectedImportTarget: nextImportTarget,
            selectedAccountId: resolveActiveConfigAccountId(nextImportTarget),
            selectedCardId: cardSelect ? parsePositiveInt(cardSelect.value) : context.state.selectedCardId,
            selectedFile: file,
            selectedFileDetectedSourceType: detectedSourceType,
            selectedFileDetectedImportTarget: detectedImportTarget,
            sourceAutoAdjustedToDetectedFile: shouldAutoAdjustSource,
            targetAutoAdjustedToDetectedFile: shouldAutoAdjustTarget,
            selectedSourceType: nextSourceType,
            fileDropActive: false,
            showOnlyPendingCategories: false,
            previewStatus: file ? 'file_selected' : 'idle',
            previewRows: [],
            previewWarnings: [],
            previewErrors: [],
            previewCanConfirm: false,
            jobProgressMessage: '',
            previewSummary: {
                fileName: file?.name || '',
                totalRows: 0,
                importedRows: 0,
                duplicateRows: 0,
                errorRows: 0,
                categorizedRows: 0,
                uncategorizedRows: 0,
                userRuleSuggestedRows: 0,
                globalRuleSuggestedRows: 0,
                categorizationApplied: false,
            },
        });

        if (file && shouldAutoAdjustTarget) {
            ensureProfileConfigLoaded(resolveActiveConfigAccountId(nextImportTarget));
        }
    };

    const renderProfileDisplay = (importTarget) => {
        const accountId = resolveActiveConfigAccountId(importTarget);
        const accountLabel = resolveActiveConfigAccountLabel();
        const matchingProfile = activeProfileConfig && activeProfileConfig.contaId === accountId
            ? activeProfileConfig
            : null;

        if (!accountId) {
            return {
                accountLabel,
                sourceTypeLabel: '-',
                mappingModeLabel: '-',
                hasHeaderLabel: '-',
                startRowLabel: '-',
                delimiterLabel: '-',
                dateFormatLabel: '-',
                decimalLabel: '-',
                columnMapSummary: 'Selecione uma conta para ver a configuração CSV aplicada.',
            };
        }

        if (!matchingProfile) {
            const loadingLabel = profileLoadState === 'loading' ? 'Carregando...' : 'Indisponível';

            return {
                accountLabel,
                sourceTypeLabel: loadingLabel,
                mappingModeLabel: loadingLabel,
                hasHeaderLabel: loadingLabel,
                startRowLabel: loadingLabel,
                delimiterLabel: loadingLabel,
                dateFormatLabel: loadingLabel,
                decimalLabel: loadingLabel,
                columnMapSummary: profileLoadError || 'Não foi possível carregar a configuração CSV desta conta.',
            };
        }

        return {
            accountLabel,
            sourceTypeLabel: String(matchingProfile.sourceType || 'ofx').toUpperCase(),
            mappingModeLabel: formatMappingModeLabel(matchingProfile.csvMappingMode),
            hasHeaderLabel: matchingProfile.csvHasHeader ? 'Sim' : 'Não',
            startRowLabel: String(matchingProfile.csvStartRow || (matchingProfile.csvHasHeader ? 2 : 1)),
            delimiterLabel: formatDelimiterLabel(matchingProfile.csvDelimiter),
            dateFormatLabel: matchingProfile.csvDateFormat || 'd/m/Y',
            decimalLabel: matchingProfile.csvDecimalSeparator || ',',
            columnMapSummary: buildColumnMapSummary(matchingProfile, importTarget),
        };
    };

    const updateConfigLinks = (accountId) => {
        const href = buildConfigUrl(accountId);
        configLinks.forEach((link) => {
            if (href) {
                link.setAttribute('href', href);
            }
        });
    };

    const ensureProfileConfigLoaded = async (accountId = resolveActiveConfigAccountId()) => {
        const normalizedAccountId = parsePositiveInt(accountId);

        if (!normalizedAccountId) {
            activeProfileConfig = null;
            profileLoadState = 'idle';
            profileLoadError = '';
            renderState();
            return;
        }

        if (activeProfileConfig?.contaId === normalizedAccountId && profileLoadState === 'ready') {
            renderState();
            return;
        }

        if (!configApiEndpoint) {
            profileLoadState = 'error';
            profileLoadError = 'Endpoint de configuração não disponível para carregar o perfil CSV.';
            renderState();
            return;
        }

        const requestToken = ++profileRequestToken;
        profileLoadState = 'loading';
        profileLoadError = '';
        renderState();

        try {
            const response = await fetchApiJson(`${configApiEndpoint}?conta_id=${normalizedAccountId}`, {
                method: 'GET',
            });

            if (requestToken !== profileRequestToken) {
                return;
            }

            activeProfileConfig = normalizeProfileConfig(response?.data || response);
            profileLoadState = 'ready';
            profileLoadError = '';
            renderState();
        } catch (error) {
            if (requestToken !== profileRequestToken) {
                return;
            }

            if (activeProfileConfig?.contaId !== normalizedAccountId) {
                activeProfileConfig = null;
            }

            profileLoadState = 'error';
            profileLoadError = String(error?.message || 'Não foi possível carregar a configuração CSV.').trim();
            renderState();
        }
    };

    const stopJobPolling = () => {
        if (pollingTimer) {
            clearTimeout(pollingTimer);
            pollingTimer = null;
        }
    };

    const updateJobProgressMessage = (message) => {
        context.setState({
            jobProgressMessage: String(message || '').trim(),
        });
    };

    const getProcessingMessage = () => 'Seu arquivo está sendo importado...';
    const getCompletedMessage = () => 'Concluído.';

    const syncSourceTypeAvailability = () => {
        let hasSelectedEnabledSource = false;
        let ofxInput = null;

        sourceInputs.forEach((input) => {
            const sourceType = normalizeSourceType(input.value, 'ofx');
            const wrapper = input.closest('.imp-format-switch__item');

            if (wrapper) {
                wrapper.classList.remove('imp-format-switch__item--disabled');
            }

            if (sourceType === 'ofx') {
                ofxInput = input;
            }

            if (input.checked) {
                hasSelectedEnabledSource = true;
            }
        });

        if (!hasSelectedEnabledSource && ofxInput) {
            ofxInput.checked = true;
        }

        if (targetSourceHint) {
            targetSourceHint.hidden = !isCardTarget();
        }
    };

    const syncTargetInputSelection = (importTarget) => {
        const normalizedTarget = String(importTarget || '').trim().toLowerCase() === 'cartao' ? 'cartao' : 'conta';

        targetInputs.forEach((input) => {
            input.checked = String(input.value || '').trim().toLowerCase() === normalizedTarget;
        });
    };

    const syncSourceInputSelection = (sourceType) => {
        const normalizedSourceType = normalizeSourceType(sourceType, 'ofx');

        sourceInputs.forEach((input) => {
            input.checked = normalizeSourceType(input.value, 'ofx') === normalizedSourceType;
        });
    };

    const buildMismatchRecoveryMessage = (detectedImportTarget) => {
        if (detectedImportTarget === 'cartao') {
            return 'O alvo foi ajustado para Cartão/fatura. Revise o cartão selecionado e prepare o preview novamente.';
        }

        return 'O alvo foi ajustado para Conta. Revise a conta selecionada e prepare o preview novamente.';
    };

    const applyDetectedImportTargetSuggestion = (normalized) => {
        const nextTarget = normalized.detectedImportTarget === 'cartao' ? 'cartao' : 'conta';
        const nextErrors = [...normalized.errors];
        const recoveryMessage = buildMismatchRecoveryMessage(nextTarget);
        const preservedSourceType = currentSourceType();

        if (!nextErrors.includes(recoveryMessage)) {
            nextErrors.push(recoveryMessage);
        }

        syncTargetInputSelection(nextTarget);
        syncSourceInputSelection(preservedSourceType);
        syncSourceTypeAvailability();

        context.setState({
            selectedImportTarget: nextTarget,
            selectedSourceType: preservedSourceType,
            selectedAccountId: resolveActiveConfigAccountId(nextTarget),
            selectedCardId: cardSelect ? parsePositiveInt(cardSelect.value) : context.state.selectedCardId,
            showOnlyPendingCategories: false,
            previewStatus: 'preview_error',
            previewRows: [],
            previewWarnings: normalized.warnings,
            previewErrors: nextErrors,
            previewCanConfirm: false,
            previewSummary: {
                ...normalized.summary,
                totalRows: 0,
                importedRows: 0,
                duplicateRows: 0,
                errorRows: 0,
                categorizedRows: 0,
                uncategorizedRows: 0,
                userRuleSuggestedRows: 0,
                globalRuleSuggestedRows: 0,
                categorizationApplied: false,
            },
        });
    };

    const setStatusMessage = (message) => {
        if (previewState) {
            previewState.textContent = message;
        }
    };

    const isContaOfxPreviewActive = (
        importTarget = context.state.selectedImportTarget,
        sourceType = context.state.selectedSourceType,
    ) => importTarget === 'conta' && normalizeSourceType(sourceType, 'ofx') === 'ofx';

    const hasCategory = (row) => Boolean(row?.categoriaId);

    const formatAmount = (value) => {
        const number = Number(value);
        if (!Number.isFinite(number)) {
            return String(value ?? '-');
        }

        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(number);
    };

    const resolveSourceKey = (row) => {
        if (!row) {
            return 'pending';
        }

        if (row.categoriaEditada && row.categoriaId) {
            return 'manual';
        }

        const normalized = String(row.categoriaSource || '').trim().toLowerCase();
        if (normalized === 'user_rule' || normalized === 'rule' || normalized === 'manual') {
            return normalized;
        }

        return row.categoriaId ? 'manual' : 'pending';
    };

    const resolveSourceLabel = (row) => {
        switch (resolveSourceKey(row)) {
            case 'user_rule':
                return 'Regra do usuário';
            case 'rule':
                return 'Regra global';
            case 'manual':
                return 'Manual';
            default:
                return 'Pendente';
        }
    };

    const countCategorizedRows = (rows) => {
        if (!Array.isArray(rows)) {
            return { categorizedRows: 0, uncategorizedRows: 0 };
        }

        const categorizedRows = rows.filter((row) => hasCategory(row)).length;
        return {
            categorizedRows,
            uncategorizedRows: Math.max(0, rows.length - categorizedRows),
        };
    };

    const countSuggestedRows = (rows) => {
        if (!Array.isArray(rows)) {
            return { userRuleSuggestedRows: 0, globalRuleSuggestedRows: 0 };
        }

        return rows.reduce((summary, row) => {
            if (!parsePositiveInt(row?.categoriaSugeridaId ?? null)) {
                return summary;
            }

            const source = String(row?.categoriaSource || '').trim().toLowerCase();
            if (source === 'user_rule') {
                summary.userRuleSuggestedRows += 1;
            } else if (source === 'rule') {
                summary.globalRuleSuggestedRows += 1;
            }

            return summary;
        }, {
            userRuleSuggestedRows: 0,
            globalRuleSuggestedRows: 0,
        });
    };

    const summarizeRows = (rows) => ({
        ...countCategorizedRows(rows),
        ...countSuggestedRows(rows),
    });

    const resolveCategoryName = (categoriaId) => {
        const normalizedId = parsePositiveInt(categoriaId);
        if (!normalizedId) {
            return '';
        }

        return String(
            categoryOptions.find((item) => String(item.id) === String(normalizedId))?.nome
            || ''
        );
    };

    const resolveSubcategoryName = (categoriaId, subcategoriaId) => {
        const normalizedCategoriaId = parsePositiveInt(categoriaId);
        const normalizedSubcategoriaId = parsePositiveInt(subcategoriaId);
        if (!normalizedCategoriaId || !normalizedSubcategoriaId) {
            return '';
        }

        const options = subcategoryCache.get(String(normalizedCategoriaId)) || [];
        return String(
            options.find((item) => String(item.id) === String(normalizedSubcategoriaId))?.nome
            || ''
        );
    };

    const syncPreviewRowState = (row) => {
        const categoriaId = parsePositiveInt(row?.categoriaId ?? null);
        const subcategoriaId = parsePositiveInt(row?.subcategoriaId ?? null);
        const categoriaSugeridaId = parsePositiveInt(row?.categoriaSugeridaId ?? null);
        const subcategoriaSugeridaId = parsePositiveInt(row?.subcategoriaSugeridaId ?? null);
        const categoriaEditada = categoriaId !== categoriaSugeridaId || subcategoriaId !== subcategoriaSugeridaId;
        const categoriaNome = String(row?.categoriaNome || resolveCategoryName(categoriaId) || '');
        const subcategoriaNome = String(row?.subcategoriaNome || resolveSubcategoryName(categoriaId, subcategoriaId) || '');

        return {
            ...row,
            categoriaId,
            subcategoriaId,
            categoriaSugeridaId,
            subcategoriaSugeridaId,
            categoriaNome,
            subcategoriaNome,
            categoriaEditada,
            status: categoriaId ? 'Pronto' : 'Pendente',
        };
    };

    const normalizePreviewRow = (row, index) => {
        const source = typeof row === 'object' && row !== null ? row : {};
        const amountValue = source.amount ?? source.valor ?? source.value ?? null;
        const type = String(source.type ?? source.entry_type ?? source.kind ?? '-').trim().toLowerCase();

        return syncPreviewRowState({
            rowKey: String(source.row_key || `preview-row-${index}`).trim(),
            date: String(source.date ?? source.occurred_on ?? source.posted_on ?? '-'),
            description: String(source.description ?? source.memo ?? source.historico ?? '-'),
            memo: String(source.memo ?? ''),
            amountValue: Number.isFinite(Number(amountValue)) ? Number(amountValue) : null,
            amountLabel: amountValue === null ? '-' : formatAmount(amountValue),
            type,
            typeLabel: String(type || '-').toUpperCase(),
            categoriaId: parsePositiveInt(source.categoria_id ?? null),
            subcategoriaId: parsePositiveInt(source.subcategoria_id ?? null),
            categoriaNome: String(source.categoria_nome ?? ''),
            subcategoriaNome: String(source.subcategoria_nome ?? ''),
            categoriaSugeridaId: parsePositiveInt(source.categoria_sugerida_id ?? source.categoria_id ?? null),
            subcategoriaSugeridaId: parsePositiveInt(source.subcategoria_sugerida_id ?? source.subcategoria_id ?? null),
            categoriaSugeridaNome: String(source.categoria_sugerida_nome ?? source.categoria_nome ?? ''),
            subcategoriaSugeridaNome: String(source.subcategoria_sugerida_nome ?? source.subcategoria_nome ?? ''),
            categoriaSource: String(source.categoria_source ?? '').trim().toLowerCase(),
            categoriaConfidence: String(source.categoria_confidence ?? '').trim().toLowerCase(),
            categoriaLearningSource: String(source.categoria_learning_source ?? '').trim().toLowerCase(),
            categoriaEditada: Boolean(source.categoria_editada === true),
            status: String(source.status ?? ''),
        });
    };

    const normalizeRows = (rows) => {
        if (!Array.isArray(rows)) {
            return [];
        }

        return rows.map((row, index) => normalizePreviewRow(row, index));
    };

    const getAvailableCategoriesForRow = (row) => {
        const bucketType = String(row?.type || '').trim().toLowerCase();
        if (!bucketType || !['receita', 'despesa'].includes(bucketType)) {
            return [...categoryOptions];
        }

        return categoryOptions.filter((item) => item.tipo === bucketType || item.tipo === 'ambas');
    };

    const ensureCategoryOptionsLoaded = async () => {
        if (categoryOptions.length > 0 || !categoriesEndpoint) {
            return categoryOptions;
        }

        const response = await fetchApiJson(categoriesEndpoint, { method: 'GET' });
        const rawCategories = Array.isArray(response?.data)
            ? response.data
            : (Array.isArray(response?.data?.categorias) ? response.data.categorias : []);

        categoryOptions = rawCategories
            .map((item) => ({
                id: parsePositiveInt(item?.id ?? null),
                nome: String(item?.nome || '').trim(),
                tipo: String(item?.tipo || '').trim().toLowerCase(),
            }))
            .filter((item) => item.id && item.nome)
            .sort((left, right) => left.nome.localeCompare(right.nome, 'pt-BR', { sensitivity: 'base' }));

        categoryCatalogError = '';
        return categoryOptions;
    };

    const ensureSubcategoryOptionsLoaded = async (categoriaId) => {
        const normalizedCategoriaId = parsePositiveInt(categoriaId);
        if (!normalizedCategoriaId || !subcategoriesEndpointBase) {
            return [];
        }

        const cacheKey = String(normalizedCategoriaId);
        if (subcategoryCache.has(cacheKey)) {
            return subcategoryCache.get(cacheKey) || [];
        }

        const response = await fetchApiJson(`${subcategoriesEndpointBase}/${normalizedCategoriaId}/subcategorias`, {
            method: 'GET',
        });
        const rawSubcategories = Array.isArray(response?.data?.subcategorias)
            ? response.data.subcategorias
            : (Array.isArray(response?.data) ? response.data : []);

        const normalized = rawSubcategories
            .map((item) => ({
                id: parsePositiveInt(item?.id ?? null),
                nome: String(item?.nome || '').trim(),
            }))
            .filter((item) => item.id && item.nome)
            .sort((left, right) => left.nome.localeCompare(right.nome, 'pt-BR', { sensitivity: 'base' }));

        subcategoryCache.set(cacheKey, normalized);
        return normalized;
    };

    const prefetchSubcategoryOptions = async (rows) => {
        const categoryIds = Array.from(new Set(
            (Array.isArray(rows) ? rows : [])
                .map((row) => parsePositiveInt(row?.categoriaId ?? null))
                .filter(Boolean)
        ));

        await Promise.all(categoryIds.map((categoriaId) => ensureSubcategoryOptionsLoaded(categoriaId)));
    };

    const updatePreviewRows = (rows) => {
        const rowSummary = summarizeRows(rows);
        context.setState({
            previewRows: rows,
            previewSummary: {
                ...context.state.previewSummary,
                totalRows: Array.isArray(rows) ? rows.length : 0,
                categorizedRows: rowSummary.categorizedRows,
                uncategorizedRows: rowSummary.uncategorizedRows,
                userRuleSuggestedRows: rowSummary.userRuleSuggestedRows,
                globalRuleSuggestedRows: rowSummary.globalRuleSuggestedRows,
            },
        });
    };

    const updatePreviewRow = (rowKey, patchOrUpdater) => {
        const nextRows = context.state.previewRows.map((row) => {
            if (String(row?.rowKey || '') !== String(rowKey || '')) {
                return row;
            }

            const nextPatch = typeof patchOrUpdater === 'function'
                ? patchOrUpdater(row)
                : patchOrUpdater;

            return syncPreviewRowState({
                ...row,
                ...(nextPatch || {}),
            });
        });

        updatePreviewRows(nextRows);
    };

    const buildRowOverrides = () => {
        if (!isContaOfxPreviewActive()) {
            return {};
        }

        return context.state.previewRows.reduce((accumulator, row) => {
            if (!row?.rowKey) {
                return accumulator;
            }

            accumulator[row.rowKey] = {
                categoria_id: row.categoriaId || null,
                subcategoria_id: row.subcategoriaId || null,
                categoria_sugerida_id: row.categoriaSugeridaId || null,
                subcategoria_sugerida_id: row.subcategoriaSugeridaId || null,
                categoria_sugerida_nome: row.categoriaSugeridaNome || null,
                subcategoria_sugerida_nome: row.subcategoriaSugeridaNome || null,
                categoria_source: row.categoriaSource || null,
                categoria_confidence: row.categoriaConfidence || null,
                user_edited: row.categoriaEditada === true,
            };

            return accumulator;
        }, {});
    };

    const normalizeMessages = (messages) => {
        if (!Array.isArray(messages)) {
            return [];
        }

        return messages
            .map((message) => String(message || '').trim())
            .filter((message) => message.length > 0);
    };

    const clearPreviewRows = () => {
        if (!previewRowsBody) {
            return;
        }

        while (previewRowsBody.firstChild) {
            previewRowsBody.removeChild(previewRowsBody.firstChild);
        }
    };

    const buildFallbackCell = (text) => {
        const span = document.createElement('span');
        span.className = 'imp-preview-cell-muted';
        span.textContent = String(text || '-');
        return span;
    };

    const buildCategorySelect = (row) => {
        if (!isContaOfxPreviewActive()) {
            return buildFallbackCell(row?.categoriaNome || '-');
        }

        if (categoryCatalogError || categoryOptions.length === 0) {
            return buildFallbackCell(row?.categoriaNome || 'Categorias indisponíveis');
        }

        const select = document.createElement('select');
        select.className = 'imp-preview-select';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Sem categoria';
        select.appendChild(emptyOption);

        const options = getAvailableCategoriesForRow(row);
        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.nome;
            select.appendChild(option);
        });

        const selectedValue = row?.categoriaId ? String(row.categoriaId) : '';
        if (selectedValue && !Array.from(select.options).some((option) => option.value === selectedValue)) {
            const fallbackOption = document.createElement('option');
            fallbackOption.value = selectedValue;
            fallbackOption.textContent = row?.categoriaNome || 'Categoria indisponível';
            select.appendChild(fallbackOption);
        }

        select.value = selectedValue;
        select.addEventListener('change', async (event) => {
            const categoriaId = parsePositiveInt(event.target.value);
            if (categoriaId) {
                try {
                    await ensureSubcategoryOptionsLoaded(categoriaId);
                } catch (error) {
                    categoryCatalogError = String(error?.message || 'Não foi possível carregar subcategorias.').trim();
                }
            }

            updatePreviewRow(row.rowKey, (currentRow) => {
                const nextCategoriaId = parsePositiveInt(categoriaId);
                const nextSubcategories = nextCategoriaId
                    ? (subcategoryCache.get(String(nextCategoriaId)) || [])
                    : [];
                const keepCurrentSubcategory = nextSubcategories.some(
                    (item) => String(item.id) === String(currentRow.subcategoriaId || ''),
                );
                const nextSubcategoriaId = keepCurrentSubcategory ? currentRow.subcategoriaId : null;

                return {
                    categoriaId: nextCategoriaId,
                    categoriaNome: resolveCategoryName(nextCategoriaId),
                    subcategoriaId: nextSubcategoriaId,
                    subcategoriaNome: resolveSubcategoryName(nextCategoriaId, nextSubcategoriaId),
                };
            });
        });

        return select;
    };

    const buildSubcategorySelect = (row) => {
        if (!isContaOfxPreviewActive()) {
            return buildFallbackCell(row?.subcategoriaNome || '-');
        }

        if (categoryCatalogError) {
            return buildFallbackCell(row?.subcategoriaNome || 'Subcategorias indisponíveis');
        }

        if (!row?.categoriaId) {
            return buildFallbackCell('Sem subcategoria');
        }

        const options = subcategoryCache.get(String(row.categoriaId)) || [];
        if (options.length === 0) {
            return buildFallbackCell('Sem subcategoria');
        }

        const select = document.createElement('select');
        select.className = 'imp-preview-select';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Sem subcategoria';
        select.appendChild(emptyOption);

        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.nome;
            select.appendChild(option);
        });

        const selectedValue = row?.subcategoriaId ? String(row.subcategoriaId) : '';
        if (selectedValue && !Array.from(select.options).some((option) => option.value === selectedValue)) {
            const fallbackOption = document.createElement('option');
            fallbackOption.value = selectedValue;
            fallbackOption.textContent = row?.subcategoriaNome || 'Subcategoria indisponível';
            select.appendChild(fallbackOption);
        }

        select.value = selectedValue;
        select.addEventListener('change', (event) => {
            const subcategoriaId = parsePositiveInt(event.target.value);
            updatePreviewRow(row.rowKey, {
                subcategoriaId,
                subcategoriaNome: resolveSubcategoryName(row.categoriaId, subcategoriaId),
            });
        });

        return select;
    };

    const renderPreviewRows = (rows) => {
        if (!previewRowsBody) {
            return;
        }

        clearPreviewRows();

        rows.forEach((row) => {
            const tr = document.createElement('tr');
            const values = [row.date, row.description, row.amountLabel, row.typeLabel];

            values.forEach((value) => {
                const td = document.createElement('td');
                td.textContent = String(value ?? '-');
                tr.appendChild(td);
            });

            const categoriaTd = document.createElement('td');
            categoriaTd.appendChild(buildCategorySelect(row));
            tr.appendChild(categoriaTd);

            const subcategoriaTd = document.createElement('td');
            subcategoriaTd.appendChild(buildSubcategorySelect(row));
            tr.appendChild(subcategoriaTd);

            const origemTd = document.createElement('td');
            const origemBadge = document.createElement('span');
            origemBadge.className = 'imp-preview-source';
            origemBadge.dataset.source = resolveSourceKey(row);
            origemBadge.textContent = resolveSourceLabel(row);
            origemTd.appendChild(origemBadge);
            tr.appendChild(origemTd);

            const statusTd = document.createElement('td');
            statusTd.textContent = String(row.status ?? '-');
            tr.appendChild(statusTd);

            previewRowsBody.appendChild(tr);
        });
    };

    const renderMessages = (listElement, messages) => {
        if (!listElement) {
            return;
        }

        while (listElement.firstChild) {
            listElement.removeChild(listElement.firstChild);
        }

        if (!Array.isArray(messages) || messages.length === 0) {
            listElement.hidden = true;
            return;
        }

        messages.forEach((message) => {
            const li = document.createElement('li');
            li.textContent = String(message);
            listElement.appendChild(li);
        });

        listElement.hidden = false;
    };

    const canSubmit = () => {
        const quota = currentQuota();
        if (!quota.allowed) {
            return false;
        }

        if (!context.state.selectedFile) {
            return false;
        }

        if (isCardTarget()) {
            return Boolean(context.state.selectedCardId);
        }

        return Boolean(context.state.selectedAccountId);
    };

    const renderState = () => {
        const { state } = context;
        const allRows = Array.isArray(state.previewRows) ? state.previewRows : [];
        const hasRows = allRows.length > 0;
        const filteredRows = state.showOnlyPendingCategories
            ? allRows.filter((row) => !hasCategory(row))
            : allRows;
        const hasRenderableRows = filteredRows.length > 0;
        const quota = currentQuota();
        const templateMeta = buildTemplateMeta(state.selectedImportTarget);
        const profileDisplay = renderProfileDisplay(state.selectedImportTarget);
        const activeConfigAccountId = resolveActiveConfigAccountId(state.selectedImportTarget);
        const pathGuide = buildPathGuide(state);
        const contextGuide = buildContextGuide(state, activeConfigAccountId, profileDisplay);
        const readinessGuide = buildReadinessGuide(state, quota);
        const fileSelectionNote = buildFileNote(state);

        if (accountField) {
            accountField.hidden = isCardTarget();
        }
        if (cardField) {
            cardField.hidden = !isCardTarget();
        }
        syncSourceTypeAvailability();
        updateConfigLinks(activeConfigAccountId);

        if (advancedDescription) {
            advancedDescription.textContent = buildAdvancedDescription(state.selectedImportTarget, state.selectedSourceType);
        }

        if (advancedModeBadge) {
            advancedModeBadge.dataset.status = state.selectedSourceType === 'csv' ? 'preview_ready' : 'idle';
            advancedModeBadge.textContent = state.selectedSourceType === 'csv' ? 'CSV ativo' : 'OFX automático';
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
            advancedLinkedAccountNote.textContent = activeConfigAccountId
                ? templateMeta.contextNote
                : 'Selecione uma conta para carregar o perfil CSV e abrir os ajustes avançados no contexto correto.';
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

        if (previewBadge) {
            previewBadge.dataset.status = state.previewStatus;
            previewBadge.textContent = STATUS_BADGES[state.previewStatus] || STATUS_BADGES.idle;
        }

        if (fileDrop) {
            fileDrop.dataset.hasFile = state.selectedFile ? 'true' : 'false';
            fileDrop.dataset.previewStatus = state.previewStatus;
            fileDrop.dataset.dragActive = state.fileDropActive ? 'true' : 'false';
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
                fileNote.textContent = '';
                fileNote.dataset.state = 'info';
            }
        }

        applyGuideCard(guidePathCard, guidePathTitle, guidePathCopy, pathGuide);
        applyGuideCard(guideContextCard, guideContextTitle, guideContextCopy, contextGuide);
        applyGuideCard(guideReadinessCard, guideReadinessTitle, guideReadinessCopy, readinessGuide);

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
            previewFileName.textContent = state.previewSummary.fileName || '-';
        }

        if (previewTotalRows) {
            previewTotalRows.textContent = String(state.previewSummary.totalRows || 0);
        }

        if (previewCategorized) {
            previewCategorized.textContent = String(state.previewSummary.categorizedRows || 0);
        }

        if (previewUncategorized) {
            previewUncategorized.textContent = String(state.previewSummary.uncategorizedRows || 0);
        }

        if (previewUserRuleSuggested) {
            previewUserRuleSuggested.textContent = String(state.previewSummary.userRuleSuggestedRows || 0);
        }

        if (previewGlobalRuleSuggested) {
            previewGlobalRuleSuggested.textContent = String(state.previewSummary.globalRuleSuggestedRows || 0);
        }

        if (quotaWarning) {
            if (!quota.allowed) {
                const quotaMessage = quota.message || 'Limite de importação atingido para o plano atual.';
                quotaWarning.hidden = false;
                quotaWarning.textContent = `${quotaMessage} `;

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
        }

        if (confirmButton) {
            confirmButton.disabled = !quota.allowed
                || !(state.previewStatus === 'preview_ready' && state.previewCanConfirm === true);
        }

        renderMessages(previewWarnings, state.previewWarnings);
        renderMessages(previewErrors, state.previewErrors);

        if (previewTools) {
            previewTools.hidden = !hasRows || !isContaOfxPreviewActive();
        }

        if (categorizePreviewButton) {
            categorizePreviewButton.disabled = !hasRows
                || !isContaOfxPreviewActive()
                || state.previewStatus !== 'preview_ready';
            categorizePreviewButton.textContent = state.previewSummary.categorizationApplied
                ? 'Reaplicar categorização'
                : 'Categorizar linhas';
        }

        if (categorizePreviewHelper) {
            categorizePreviewHelper.textContent = state.previewSummary.categorizationApplied
                ? `${state.previewSummary.userRuleSuggestedRows || 0} linha(s) sugerida(s) por regra do usuário e ${state.previewSummary.globalRuleSuggestedRows || 0} por regra global.`
                : 'Opcional: aplica sugestões automáticas por regra do usuário e regra global sem bloquear a confirmação.';
        }

        if (pendingOnlyToggle) {
            pendingOnlyToggle.checked = state.showOnlyPendingCategories === true;
            pendingOnlyToggle.disabled = !hasRows || !isContaOfxPreviewActive();
        }

        if (previewTableWrap) {
            previewTableWrap.hidden = !hasRenderableRows;
        }

        if (previewEmpty) {
            previewEmpty.hidden = hasRenderableRows;
            previewEmpty.textContent = hasRows && state.showOnlyPendingCategories && (state.previewSummary.uncategorizedRows || 0) === 0
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
                    ? ((state.previewSummary.categorizationApplied !== true && isContaOfxPreviewActive())
                        ? 'Preview válido. Você pode confirmar agora ou clicar em "Categorizar linhas" para sugerir categorias antes da importação.'
                        : ((state.previewSummary.uncategorizedRows || 0) > 0
                            ? `${state.previewSummary.uncategorizedRows} linha(s) ainda sem categoria. Você pode confirmar agora ou revisar antes da importação.`
                            : 'Preview válido. Clique em "Confirmar importação" para persistir os dados.'))
                    : blockingMessage;
            } else if (state.previewStatus === 'confirming') {
                previewNextStep.textContent = String(state.jobProgressMessage || getProcessingMessage());
            } else if (state.previewStatus === 'preview_error') {
                previewNextStep.textContent = quota.allowed
                    ? (state.previewErrors[0] || 'Não foi possível concluir a etapa. Revise os dados e tente novamente.')
                    : 'Limite do plano atingido para este fluxo. Faça upgrade para continuar.';
            } else if (state.previewStatus === 'confirmed') {
                previewNextStep.textContent = getCompletedMessage();
            } else {
                previewNextStep.textContent = 'Revise o preview para liberar a confirmação.';
            }
        }
    };

    const preparePreviewPayload = ({ categorizePreview = false } = {}) => {
        const payload = {
            source_type: context.state.selectedSourceType,
            import_target: context.state.selectedImportTarget,
            filename: context.state.selectedFile?.name || '',
            categorize_preview: categorizePreview ? '1' : '0',
        };

        if (isCardTarget()) {
            payload.cartao_id = context.state.selectedCardId;
        } else {
            payload.conta_id = context.state.selectedAccountId;
        }

        return payload;
    };

    const normalizePreviewResponse = (response, payload) => {
        const body = typeof response?.data === 'object' && response?.data !== null ? response.data : {};
        const preview = typeof body.preview === 'object' && body.preview !== null ? body.preview : body;
        const rows = normalizeRows(preview.rows ?? []);
        const rowSummary = summarizeRows(rows);
        const totalRows = Number.parseInt(String(preview.total_rows ?? rows.length), 10);
        const warnings = normalizeMessages(preview.warnings ?? body.warnings ?? []);
        const errors = normalizeMessages(preview.errors ?? body.errors ?? []);
        const canConfirm = Boolean(preview.can_confirm === true);
        const detectedImportTarget = String(preview.detected_import_target || '').trim().toLowerCase() === 'cartao'
            ? 'cartao'
            : (String(preview.detected_import_target || '').trim().toLowerCase() === 'conta' ? 'conta' : null);
        const targetMismatch = Boolean(preview.target_mismatch === true);

        return {
            importTarget: String(preview.import_target || payload.import_target || 'conta'),
            cardId: parsePositiveInt(preview.cartao_id ?? payload.cartao_id ?? null),
            detectedImportTarget,
            targetMismatch,
            rows,
            warnings,
            errors,
            canConfirm,
            summary: {
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
            },
        };
    };

    const mergeSuggestedRowsIntoPreview = (currentRows, suggestedRows) => {
        if (!Array.isArray(currentRows) || currentRows.length === 0) {
            return Array.isArray(suggestedRows) ? suggestedRows : [];
        }

        const currentRowsByKey = new Map(
            currentRows
                .filter((row) => row?.rowKey)
                .map((row) => [String(row.rowKey), row])
        );

        return (Array.isArray(suggestedRows) ? suggestedRows : []).map((suggestedRow) => {
            const currentRow = currentRowsByKey.get(String(suggestedRow?.rowKey || ''));
            if (!currentRow || currentRow.categoriaEditada !== true) {
                return suggestedRow;
            }

            return {
                ...suggestedRow,
                categoriaId: currentRow.categoriaId,
                subcategoriaId: currentRow.subcategoriaId,
                categoriaNome: currentRow.categoriaNome,
                subcategoriaNome: currentRow.subcategoriaNome,
            };
        });
    };

    const normalizeConfirmResponse = (response) => {
        const body = typeof response?.data === 'object' && response?.data !== null ? response.data : {};
        const summary = typeof body.summary === 'object' && body.summary !== null ? body.summary : {};
        const batch = typeof body.batch === 'object' && body.batch !== null ? body.batch : {};
        const job = typeof body.job === 'object' && body.job !== null ? body.job : null;

        if (job && Number.parseInt(String(job.id ?? 0), 10) > 0) {
            return {
                mode: 'async',
                jobId: Number.parseInt(String(job.id ?? 0), 10) || 0,
                jobStatus: String(job.status || 'queued').trim().toLowerCase(),
                importTarget: String(job.import_target || context.state.selectedImportTarget || 'conta'),
            };
        }

        return {
            mode: 'sync',
            batchId: Number.parseInt(String(batch.id ?? 0), 10) || 0,
            importTarget: String(batch.import_target || context.state.selectedImportTarget || 'conta'),
            totalRows: Number.parseInt(String(summary.total_rows ?? batch.total_rows ?? 0), 10) || 0,
            importedRows: Number.parseInt(String(summary.imported_rows ?? batch.imported_rows ?? 0), 10) || 0,
            duplicateRows: Number.parseInt(String(summary.duplicate_rows ?? batch.duplicate_rows ?? 0), 10) || 0,
            errorRows: Number.parseInt(String(summary.error_rows ?? batch.error_rows ?? 0), 10) || 0,
        };
    };

    const normalizeJobStatusResponse = (response) => {
        const body = typeof response?.data === 'object' && response?.data !== null ? response.data : {};
        const job = typeof body.job === 'object' && body.job !== null ? body.job : {};
        const summary = typeof body.summary === 'object' && body.summary !== null ? body.summary : {};
        const batch = typeof body.batch === 'object' && body.batch !== null ? body.batch : {};

        const status = String(body.status || job.status || '').trim().toLowerCase();

        return {
            status,
            jobId: Number.parseInt(String(job.id ?? 0), 10) || 0,
            batchId: Number.parseInt(String(batch.id ?? job.result_batch_id ?? 0), 10) || 0,
            importTarget: String(batch.import_target || job.import_target || context.state.selectedImportTarget || 'conta'),
            totalRows: Number.parseInt(String(summary.total_rows ?? job.total_rows ?? batch.total_rows ?? 0), 10) || 0,
            processedRows: Number.parseInt(String(summary.processed_rows ?? job.processed_rows ?? 0), 10) || 0,
            importedRows: Number.parseInt(String(summary.imported_rows ?? job.imported_rows ?? batch.imported_rows ?? 0), 10) || 0,
            duplicateRows: Number.parseInt(String(summary.duplicate_rows ?? job.duplicate_rows ?? batch.duplicate_rows ?? 0), 10) || 0,
            errorRows: Number.parseInt(String(summary.error_rows ?? job.error_rows ?? batch.error_rows ?? 0), 10) || 0,
            message: String(body.message || job.error_summary || '').trim(),
        };
    };

    const markCurrentBucketAsUsed = () => {
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
        const remaining = Math.max(0, limit - nextUsed);
        quota.used = nextUsed;
        quota.remaining = remaining;
        quota.allowed = remaining > 0;
    };

    const buildFormDataForAction = (payload, file) => {
        const formData = form ? new FormData(form) : new FormData();
        formData.set('source_type', String(payload.source_type || 'ofx'));
        formData.set('import_target', String(payload.import_target || 'conta'));
        formData.set('async', String(payload.async || '0'));
        formData.set('categorize_preview', String(payload.categorize_preview || '0'));

        if (payload.row_overrides && typeof payload.row_overrides === 'object') {
            formData.set('row_overrides', JSON.stringify(payload.row_overrides));
        } else {
            formData.delete('row_overrides');
        }

        if (payload.import_target === 'cartao') {
            formData.set('cartao_id', String(payload.cartao_id || ''));
            formData.delete('conta_id');
        } else {
            formData.set('conta_id', String(payload.conta_id || ''));
            formData.delete('cartao_id');
        }

        if (file instanceof File) {
            formData.set('file', file, file.name);
        }

        appendCsrfToken(formData, form || context.root);

        return formData;
    };

    const requestPreview = async (payload, file) => {
        if (!previewEndpoint) {
            throw new Error('Endpoint de preview não configurado para esta página.');
        }

        const formData = buildFormDataForAction(payload, file);
        return fetchApiJson(previewEndpoint, {
            method: 'POST',
            body: formData,
        });
    };

    const requestConfirm = async (payload, file) => {
        if (!confirmEndpoint) {
            throw new Error('Endpoint de confirmação não configurado para esta página.');
        }

        const formData = buildFormDataForAction(payload, file);
        return fetchApiJson(confirmEndpoint, {
            method: 'POST',
            body: formData,
        });
    };

    const requestJobStatus = async (jobId) => {
        if (!jobStatusEndpointBase) {
            throw new Error('Endpoint de status do job não configurado para esta página.');
        }

        const normalizedJobId = Number.parseInt(String(jobId || 0), 10) || 0;
        if (normalizedJobId <= 0) {
            throw new Error('Job inválido para consulta de status.');
        }

        return fetchApiJson(`${jobStatusEndpointBase}/${normalizedJobId}`, {
            method: 'GET',
        });
    };

    const applyAsyncSuccess = (normalized) => {
        const warnings = [];
        if (normalized.duplicateRows > 0) {
            warnings.push(`${normalized.duplicateRows} linha(s) ignorada(s) por duplicidade.`);
        }

        const errors = [];
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
            previewSummary: {
                fileName: context.state.previewSummary.fileName || context.state.selectedFile?.name || '',
                totalRows: normalized.totalRows,
                importedRows: normalized.importedRows,
                duplicateRows: normalized.duplicateRows,
                errorRows: normalized.errorRows,
                categorizedRows: context.state.previewSummary.categorizedRows || 0,
                uncategorizedRows: context.state.previewSummary.uncategorizedRows || 0,
                userRuleSuggestedRows: context.state.previewSummary.userRuleSuggestedRows || 0,
                globalRuleSuggestedRows: context.state.previewSummary.globalRuleSuggestedRows || 0,
                categorizationApplied: context.state.previewSummary.categorizationApplied === true,
            },
        });

        if (previewNextStep) {
            previewNextStep.textContent = getCompletedMessage();
        }
    };

    const applyAsyncFailure = (message, payload) => {
        const error = new Error(message || 'Falha ao processar job da importação.');
        applyPreviewError(error, payload);
    };

    const scheduleJobPolling = (payload) => {
        stopJobPolling();

        const poll = async () => {
            if (!activeJobId) {
                return;
            }

            try {
                const response = await requestJobStatus(activeJobId);
                const normalized = normalizeJobStatusResponse(response);
                if (normalized.status === 'queued') {
                    updateJobProgressMessage(getProcessingMessage());
                    pollingTimer = setTimeout(poll, 2000);
                    return;
                }

                if (normalized.status === 'processing') {
                    updateJobProgressMessage(getProcessingMessage());
                    pollingTimer = setTimeout(poll, 2000);
                    return;
                }

                stopJobPolling();

                if (normalized.status === 'completed') {
                    applyAsyncSuccess(normalized);
                    activeJobId = null;
                    return;
                }

                const failureMessage = normalized.message || `Lote #${activeJobId} falhou durante o processamento.`;
                applyAsyncFailure(failureMessage, payload);
                activeJobId = null;
            } catch (error) {
                stopJobPolling();
                applyPreviewError(error, payload);
                activeJobId = null;
            }
        };

        poll();
    };

    const applyPreviewResult = async (response, payload) => {
        const normalized = normalizePreviewResponse(response, payload);
        const categorizePreview = isTruthyFlag(payload?.categorize_preview);

        if (
            normalized.targetMismatch
            && normalized.detectedImportTarget
            && normalized.detectedImportTarget !== payload.import_target
        ) {
            applyDetectedImportTargetSuggestion(normalized);
            return;
        }

        if (normalized.importTarget === 'conta' && normalizeSourceType(payload.source_type || 'ofx', 'ofx') === 'ofx') {
            try {
                await ensureCategoryOptionsLoaded();
                await prefetchSubcategoryOptions(normalized.rows);
                if (categorizePreview) {
                    normalized.rows = mergeSuggestedRowsIntoPreview(context.state.previewRows, normalized.rows);
                }
                normalized.rows = normalized.rows.map((row) => syncPreviewRowState(row));
                normalized.summary = {
                    ...normalized.summary,
                    ...summarizeRows(normalized.rows),
                    categorizationApplied: categorizePreview,
                };
                categoryCatalogError = '';
            } catch (error) {
                categoryCatalogError = String(error?.message || 'Não foi possível carregar categorias para edição.').trim();
                normalized.warnings = normalizeMessages([
                    ...normalized.warnings,
                    categoryCatalogError,
                ]);
            }
        }

        context.setState({
            selectedImportTarget: normalized.importTarget,
            selectedCardId: normalized.cardId ?? context.state.selectedCardId,
            showOnlyPendingCategories: false,
            previewStatus: 'preview_ready',
            previewRows: normalized.rows,
            previewWarnings: normalized.warnings,
            previewErrors: normalized.errors,
            previewCanConfirm: normalized.canConfirm,
            previewSummary: normalized.summary,
        });
    };

    const applyPreviewError = (error, payload) => {
        stopJobPolling();
        activeJobId = null;

        const payloadErrors = error?.payload?.errors && typeof error.payload.errors === 'object'
            ? error.payload.errors
            : null;

        if (payloadErrors?.limit_reached === true) {
            const quotaBucket = String(payloadErrors.bucket || resolveQuotaBucket(payload.import_target, payload.source_type || 'ofx'));
            if (quotaBucket && importLimitsByBucket[quotaBucket] && typeof importLimitsByBucket[quotaBucket] === 'object') {
                importLimitsByBucket[quotaBucket] = {
                    ...importLimitsByBucket[quotaBucket],
                    allowed: false,
                    remaining: 0,
                    message: String(error?.message || importLimitsByBucket[quotaBucket]?.message || ''),
                    upgrade_url: String(payloadErrors.upgrade_url || upgradeUrl),
                    used: payloadErrors?.limit_info?.used ?? importLimitsByBucket[quotaBucket]?.used ?? 0,
                    limit: payloadErrors?.limit_info?.limit ?? importLimitsByBucket[quotaBucket]?.limit ?? null,
                };
            }
        }

        const messages = Array.isArray(error?.messages) && error.messages.length > 0
            ? error.messages
            : [String(error?.message || 'Erro ao montar preview.')];

        context.setState({
            previewStatus: 'preview_error',
            showOnlyPendingCategories: false,
            previewRows: [],
            previewWarnings: [],
            previewErrors: messages,
            previewCanConfirm: false,
            jobProgressMessage: '',
            previewSummary: {
                fileName: String(payload.filename || context.state.selectedFile?.name || ''),
                totalRows: 0,
                importedRows: 0,
                duplicateRows: 0,
                errorRows: 0,
                categorizedRows: 0,
                uncategorizedRows: 0,
                userRuleSuggestedRows: 0,
                globalRuleSuggestedRows: 0,
                categorizationApplied: false,
            },
        });
    };

    const resetPreviewForSelectionChange = () => {
        stopJobPolling();
        activeJobId = null;

        context.setState({
            previewStatus: context.state.selectedFile ? 'file_selected' : 'idle',
            showOnlyPendingCategories: false,
            previewRows: [],
            previewWarnings: [],
            previewErrors: [],
            previewCanConfirm: false,
            jobProgressMessage: '',
            previewSummary: {
                fileName: context.state.selectedFile?.name || '',
                totalRows: 0,
                importedRows: 0,
                duplicateRows: 0,
                errorRows: 0,
                categorizedRows: 0,
                uncategorizedRows: 0,
                userRuleSuggestedRows: 0,
                globalRuleSuggestedRows: 0,
                categorizationApplied: false,
            },
        });
    };

    if (pendingOnlyToggle) {
        pendingOnlyToggle.addEventListener('change', () => {
            context.setState({
                showOnlyPendingCategories: pendingOnlyToggle.checked === true,
            });
        });
    }

    targetInputs.forEach((input) => {
        input.addEventListener('change', () => {
            const nextTarget = currentImportTarget();
            syncSourceTypeAvailability();
            context.setState({
                selectedImportTarget: nextTarget,
                selectedSourceType: currentSourceType(),
                selectedAccountId: resolveActiveConfigAccountId(nextTarget),
                selectedCardId: cardSelect ? parsePositiveInt(cardSelect.value) : null,
                targetAutoAdjustedToDetectedFile: false,
            });
            resetPreviewForSelectionChange();
            ensureProfileConfigLoaded(resolveActiveConfigAccountId(nextTarget));
        });
    });

    sourceInputs.forEach((input) => {
        input.addEventListener('change', () => {
            context.setState({
                selectedSourceType: currentSourceType(),
                sourceAutoAdjustedToDetectedFile: false,
            });
            resetPreviewForSelectionChange();
        });
    });

    if (accountSelect) {
        context.setState({
            selectedAccountId: resolveActiveConfigAccountId('conta'),
        });

        accountSelect.addEventListener('change', () => {
            context.setState({
                selectedAccountId: resolveActiveConfigAccountId('conta'),
            });
            resetPreviewForSelectionChange();
            ensureProfileConfigLoaded(resolveActiveConfigAccountId('conta'));
        });
    }

    if (cardSelect) {
        context.setState({
            selectedCardId: parsePositiveInt(cardSelect.value),
            selectedAccountId: resolveActiveConfigAccountId('cartao'),
        });

        cardSelect.addEventListener('change', () => {
            context.setState({
                selectedCardId: parsePositiveInt(cardSelect.value),
                selectedAccountId: resolveActiveConfigAccountId('cartao'),
            });
            resetPreviewForSelectionChange();
            ensureProfileConfigLoaded(resolveActiveConfigAccountId('cartao'));
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', async () => {
            const file = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0] : null;
            await applySelectedFile(file);
        });
    }

    if (fileDrop) {
        const preventFileDropDefault = (event) => {
            event.preventDefault();
            event.stopPropagation();
        };

        fileDrop.addEventListener('dragenter', (event) => {
            preventFileDropDefault(event);
            fileDropDragDepth += 1;
            if (!context.state.fileDropActive) {
                context.setState({ fileDropActive: true });
            }
        });

        fileDrop.addEventListener('dragover', (event) => {
            preventFileDropDefault(event);
            if (!context.state.fileDropActive) {
                context.setState({ fileDropActive: true });
            }
        });

        fileDrop.addEventListener('dragleave', (event) => {
            preventFileDropDefault(event);
            fileDropDragDepth = Math.max(0, fileDropDragDepth - 1);
            if (fileDropDragDepth === 0 && context.state.fileDropActive) {
                context.setState({ fileDropActive: false });
            }
        });

        fileDrop.addEventListener('drop', async (event) => {
            preventFileDropDefault(event);
            fileDropDragDepth = 0;

            const files = event.dataTransfer?.files;
            const file = files && files.length > 0 ? files[0] : null;

            syncFileInputFiles(files);
            await applySelectedFile(file);
        });
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = preparePreviewPayload({ categorizePreview: false });
            const quota = currentQuota();

            if (!quota.allowed) {
                applyPreviewError(new Error(quota.message || 'Limite de importação atingido para o plano atual.'), payload);
                return;
            }


            if (payload.import_target === 'cartao' && !payload.cartao_id) {
                applyPreviewError(new Error('Selecione um cartão para preparar o preview.'), payload);
                return;
            }

            if (payload.import_target === 'conta' && !payload.conta_id) {
                applyPreviewError(new Error('Selecione uma conta para preparar o preview.'), payload);
                return;
            }

            if (!context.state.selectedFile) {
                applyPreviewError(new Error('Selecione um arquivo para continuar.'), payload);
                return;
            }

            context.setState({
                previewStatus: 'loading_preview',
                showOnlyPendingCategories: false,
                previewRows: [],
                previewWarnings: [],
                previewErrors: [],
                previewCanConfirm: false,
                previewSummary: {
                    fileName: context.state.selectedFile.name,
                    totalRows: 0,
                    importedRows: 0,
                    duplicateRows: 0,
                    errorRows: 0,
                    categorizedRows: 0,
                    uncategorizedRows: 0,
                    userRuleSuggestedRows: 0,
                    globalRuleSuggestedRows: 0,
                    categorizationApplied: false,
                },
            });

            try {
                const response = await requestPreview(payload, context.state.selectedFile);
                await applyPreviewResult(response, payload);
            } catch (error) {
                applyPreviewError(error, payload);
            }
        });
    }

    if (categorizePreviewButton) {
        categorizePreviewButton.addEventListener('click', async () => {
            if (context.state.previewStatus !== 'preview_ready' || !context.state.selectedFile || !isContaOfxPreviewActive()) {
                return;
            }

            const payload = preparePreviewPayload({ categorizePreview: true });
            const previousRows = context.state.previewRows;
            const previousWarnings = context.state.previewWarnings;
            const previousErrors = context.state.previewErrors;
            const previousCanConfirm = context.state.previewCanConfirm;
            const previousSummary = context.state.previewSummary;

            context.setState({
                previewStatus: 'loading_preview',
            });

            try {
                const response = await requestPreview(payload, context.state.selectedFile);
                await applyPreviewResult(response, payload);
            } catch (error) {
                const messages = Array.isArray(error?.messages) && error.messages.length > 0
                    ? error.messages
                    : [String(error?.message || 'Erro ao aplicar categorização.')];

                context.setState({
                    previewStatus: 'preview_ready',
                    previewRows: previousRows,
                    previewWarnings: previousWarnings,
                    previewErrors: normalizeMessages([...previousErrors, ...messages]),
                    previewCanConfirm: previousCanConfirm,
                    previewSummary: previousSummary,
                });
            }
        });
    }

    if (confirmButton) {
        confirmButton.addEventListener('click', async () => {
            if (context.state.previewStatus !== 'preview_ready' || !context.state.previewCanConfirm) {
                return;
            }

            const payload = {
                ...preparePreviewPayload(),
                async: confirmAsyncDefault ? '1' : '0',
                row_overrides: buildRowOverrides(),
            };
            const quota = currentQuota();

            if (!quota.allowed) {
                applyPreviewError(new Error(quota.message || 'Limite de importação atingido para o plano atual.'), payload);
                return;
            }

            if (!context.state.selectedFile) {
                applyPreviewError(new Error('Arquivo não encontrado para confirmação.'), payload);
                return;
            }

            context.setState({
                previewStatus: 'confirming',
                jobProgressMessage: getProcessingMessage(),
            });

            try {
                const response = await requestConfirm(payload, context.state.selectedFile);
                const normalized = normalizeConfirmResponse(response);

                if (normalized.mode === 'async') {
                    activeJobId = normalized.jobId;

                    updateJobProgressMessage(getProcessingMessage());
                    scheduleJobPolling(payload);
                    return;
                }

                applyAsyncSuccess(normalized);
            } catch (error) {
                applyPreviewError(error, payload);
            }
        });
    }

    window.addEventListener('beforeunload', stopJobPolling);

    context.onStateChange(renderState);
    syncSourceTypeAvailability();
    context.setState({
        selectedImportTarget: currentImportTarget(),
        selectedSourceType: currentSourceType(),
        selectedAccountId: resolveActiveConfigAccountId(currentImportTarget()),
        selectedCardId: cardSelect ? parsePositiveInt(cardSelect.value) : context.state.selectedCardId,
        previewStatus: context.state.selectedFile ? 'file_selected' : 'idle',
    });
    ensureProfileConfigLoaded(resolveActiveConfigAccountId(currentImportTarget()));
}
