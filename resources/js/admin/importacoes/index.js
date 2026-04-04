import '../../../css/admin/importacoes/index.css';
import {
    appendCsrfToken,
    bootImportacoesPage,
    fetchApiJson,
    normalizeSourceType,
} from './app.js';

const context = bootImportacoesPage('index');

if (context) {
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
    const form = context.root.querySelector('#imp-upload-form');
    const previewEndpoint = String(context.root.dataset.impPreviewEndpoint || '').trim();
    const confirmEndpoint = String(context.root.dataset.impConfirmEndpoint || '').trim();
    const jobStatusEndpointBase = String(context.root.dataset.impJobStatusEndpointBase || '').trim();
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
    let categoryOptions = [];
    let categoryCatalogError = '';
    const subcategoryCache = new Map();

    const STATUS_MESSAGES = {
        idle: 'Selecione alvo, formato e arquivo para montar o preview.',
        file_selected: 'Arquivo selecionado. Clique em "Preparar preview".',
        loading_preview: 'Preparando preview e validando conteúdo do arquivo.',
        preview_ready: 'Preview pronto. Revise as linhas, categorize se quiser e confirme.',
        preview_error: 'Não foi possível preparar o preview. Revise os dados e tente novamente.',
        confirming: 'Seu OFX está sendo importado...',
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

    const isTruthyFlag = (value) => ['1', 'true', 'yes', 'on'].includes(String(value || '0').trim().toLowerCase());

    const currentSourceType = () => {
        const checked = sourceInputs.find((input) => input.checked);
        return normalizeSourceType(checked?.value || 'ofx');
    };

    const currentImportTarget = () => {
        const checked = targetInputs.find((input) => input.checked);
        return String(checked?.value || 'conta').trim().toLowerCase() === 'cartao' ? 'cartao' : 'conta';
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

    const getProcessingMessage = () => 'Seu OFX está sendo importado...';
    const getCompletedMessage = () => 'Concluído.';

    const syncSourceTypeAvailability = () => {
        const lockToOfx = isCardTarget();
        let hasSelectedEnabledSource = false;
        let ofxInput = null;

        sourceInputs.forEach((input) => {
            const sourceType = normalizeSourceType(input.value, 'ofx');
            const shouldDisable = lockToOfx && sourceType !== 'ofx';
            const wrapper = input.closest('.imp-format-switch__item');

            input.disabled = shouldDisable;
            if (wrapper) {
                wrapper.classList.toggle('imp-format-switch__item--disabled', shouldDisable);
            }

            if (sourceType === 'ofx') {
                ofxInput = input;
            }

            if (!shouldDisable && input.checked) {
                hasSelectedEnabledSource = true;
            }
        });

        if (!hasSelectedEnabledSource && ofxInput) {
            ofxInput.checked = true;
        }

        if (targetSourceHint) {
            targetSourceHint.hidden = !lockToOfx;
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

        if (!nextErrors.includes(recoveryMessage)) {
            nextErrors.push(recoveryMessage);
        }

        syncTargetInputSelection(nextTarget);
        syncSourceInputSelection('ofx');
        syncSourceTypeAvailability();

        context.setState({
            selectedImportTarget: nextTarget,
            selectedSourceType: 'ofx',
            selectedAccountId: accountSelect ? parsePositiveInt(accountSelect.value) : context.state.selectedAccountId,
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

        if (accountField) {
            accountField.hidden = isCardTarget();
        }
        if (cardField) {
            cardField.hidden = !isCardTarget();
        }
        syncSourceTypeAvailability();

        if (previewBadge) {
            previewBadge.dataset.status = state.previewStatus;
            previewBadge.textContent = STATUS_BADGES[state.previewStatus] || STATUS_BADGES.idle;
        }

        if (fileDrop) {
            fileDrop.dataset.hasFile = state.selectedFile ? 'true' : 'false';
            fileDrop.dataset.previewStatus = state.previewStatus;
        }

        if (selectedFileLabel) {
            selectedFileLabel.textContent = state.selectedFile
                ? `Arquivo selecionado: ${state.selectedFile.name}`
                : 'Nenhum arquivo selecionado.';
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
            source_type: isCardTarget() ? 'ofx' : context.state.selectedSourceType,
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
            syncSourceTypeAvailability();
            context.setState({
                selectedImportTarget: currentImportTarget(),
                selectedSourceType: currentSourceType(),
                selectedAccountId: accountSelect ? parsePositiveInt(accountSelect.value) : null,
                selectedCardId: cardSelect ? parsePositiveInt(cardSelect.value) : null,
            });
            resetPreviewForSelectionChange();
        });
    });

    sourceInputs.forEach((input) => {
        input.addEventListener('change', () => {
            context.setState({
                selectedSourceType: currentSourceType(),
            });
            resetPreviewForSelectionChange();
        });
    });

    if (accountSelect) {
        context.setState({
            selectedAccountId: parsePositiveInt(accountSelect.value),
        });

        accountSelect.addEventListener('change', () => {
            context.setState({
                selectedAccountId: parsePositiveInt(accountSelect.value),
            });
            resetPreviewForSelectionChange();
        });
    }

    if (cardSelect) {
        context.setState({
            selectedCardId: parsePositiveInt(cardSelect.value),
        });

        cardSelect.addEventListener('change', () => {
            context.setState({
                selectedCardId: parsePositiveInt(cardSelect.value),
            });
            resetPreviewForSelectionChange();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0] : null;
            context.setState({
                selectedFile: file,
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
        selectedAccountId: accountSelect ? parsePositiveInt(accountSelect.value) : context.state.selectedAccountId,
        selectedCardId: cardSelect ? parsePositiveInt(cardSelect.value) : context.state.selectedCardId,
        previewStatus: context.state.selectedFile ? 'file_selected' : 'idle',
    });
}
