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
    const previewWarnings = context.root.querySelector('[data-imp-preview-warnings]');
    const previewErrors = context.root.querySelector('[data-imp-preview-errors]');
    const previewEmpty = context.root.querySelector('[data-imp-preview-empty]');
    const previewTableWrap = context.root.querySelector('[data-imp-preview-table-wrap]');
    const previewRowsBody = context.root.querySelector('[data-imp-preview-rows]');
    const previewNextStep = context.root.querySelector('[data-imp-preview-next-step]');
    const confirmButton = context.root.querySelector('[data-imp-confirm]');
    const form = context.root.querySelector('#imp-upload-form');
    const previewEndpoint = String(context.root.dataset.impPreviewEndpoint || '').trim();
    const confirmEndpoint = String(context.root.dataset.impConfirmEndpoint || '').trim();
    const jobStatusEndpointBase = String(context.root.dataset.impJobStatusEndpointBase || '').trim();
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

    const STATUS_MESSAGES = {
        idle: 'Selecione alvo, formato e arquivo para montar o preview.',
        file_selected: 'Arquivo selecionado. Clique em "Preparar preview".',
        loading_preview: 'Preparando preview e validando conteúdo do arquivo.',
        preview_ready: 'Preview pronto. Revise as linhas antes da confirmação.',
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

    const setStatusMessage = (message) => {
        if (previewState) {
            previewState.textContent = message;
        }
    };

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

    const normalizeRows = (rows) => {
        if (!Array.isArray(rows)) {
            return [];
        }

        return rows.map((row) => {
            const source = typeof row === 'object' && row !== null ? row : {};
            const amountValue = source.amount ?? source.valor ?? source.value ?? null;

            return {
                date: String(source.date ?? source.occurred_on ?? source.posted_on ?? '-'),
                description: String(source.description ?? source.memo ?? source.historico ?? '-'),
                amount: amountValue === null ? '-' : formatAmount(amountValue),
                type: String(source.type ?? source.entry_type ?? source.kind ?? '-').toUpperCase(),
                status: String(source.status ?? 'Pronto'),
            };
        });
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

    const renderPreviewRows = (rows) => {
        if (!previewRowsBody) {
            return;
        }

        clearPreviewRows();

        rows.forEach((row) => {
            const tr = document.createElement('tr');
            const values = [row.date, row.description, row.amount, row.type, row.status];

            values.forEach((value) => {
                const td = document.createElement('td');
                td.textContent = String(value ?? '-');
                tr.appendChild(td);
            });

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
        const hasRows = Array.isArray(state.previewRows) && state.previewRows.length > 0;
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

        if (previewTableWrap) {
            previewTableWrap.hidden = !hasRows;
        }

        if (previewEmpty) {
            previewEmpty.hidden = hasRows;
        }

        if (hasRows) {
            renderPreviewRows(state.previewRows);
        } else {
            clearPreviewRows();
        }

        const statusMessage = !quota.allowed
            ? (quota.message || 'Limite de importação atingido para o plano atual.')
            : (STATUS_MESSAGES[state.previewStatus] || STATUS_MESSAGES.idle);
        setStatusMessage(statusMessage);

        if (previewNextStep) {
            if (state.previewStatus === 'preview_ready') {
                previewNextStep.textContent = state.previewCanConfirm
                    ? 'Preview válido. Clique em "Confirmar importação" para persistir os dados.'
                    : 'Preview retornou bloqueios. Ajuste os dados e envie novamente.';
            } else if (state.previewStatus === 'confirming') {
                previewNextStep.textContent = String(state.jobProgressMessage || getProcessingMessage());
            } else if (state.previewStatus === 'preview_error') {
                previewNextStep.textContent = quota.allowed
                    ? 'Não foi possível concluir a etapa. Revise os dados e tente novamente.'
                    : 'Limite do plano atingido para este fluxo. Faça upgrade para continuar.';
            } else if (state.previewStatus === 'confirmed') {
                previewNextStep.textContent = getCompletedMessage();
            } else {
                previewNextStep.textContent = 'Revise o preview para liberar a confirmação.';
            }
        }
    };

    const preparePreviewPayload = () => {
        const payload = {
            source_type: isCardTarget() ? 'ofx' : context.state.selectedSourceType,
            import_target: context.state.selectedImportTarget,
            filename: context.state.selectedFile?.name || '',
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
        const totalRows = Number.parseInt(String(preview.total_rows ?? rows.length), 10);
        const warnings = normalizeMessages(preview.warnings ?? body.warnings ?? []);
        const errors = normalizeMessages(preview.errors ?? body.errors ?? []);
        const canConfirm = Boolean(preview.can_confirm === true);

        return {
            importTarget: String(preview.import_target || payload.import_target || 'conta'),
            cardId: parsePositiveInt(preview.cartao_id ?? payload.cartao_id ?? null),
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
            },
        };
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

    const applyPreviewResult = (response, payload) => {
        const normalized = normalizePreviewResponse(response, payload);
        context.setState({
            selectedImportTarget: normalized.importTarget,
            selectedCardId: normalized.cardId ?? context.state.selectedCardId,
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
            },
        });
    };

    const resetPreviewForSelectionChange = () => {
        stopJobPolling();
        activeJobId = null;

        context.setState({
            previewStatus: context.state.selectedFile ? 'file_selected' : 'idle',
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
            },
        });
    };

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
                },
            });
        });
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = preparePreviewPayload();
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
                },
            });

            try {
                const response = await requestPreview(payload, context.state.selectedFile);
                applyPreviewResult(response, payload);
            } catch (error) {
                applyPreviewError(error, payload);
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
