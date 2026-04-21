import '../../../css/admin/importacoes/historico.css';
import { bootImportacoesPage } from './app.js';
import {
    deleteImportacaoHistorico,
    loadImportacoesHistoricoPageInit,
} from './api/historico.js';

const STATUS_LABELS = {
    processing: 'Processando',
    processed: 'Processado',
    processed_with_duplicates: 'Processado com duplicados',
    processed_duplicates_only: 'Somente duplicados',
    processed_with_errors: 'Processado com erros',
    failed: 'Falhou',
};

const TARGET_LABELS = {
    conta: 'Conta',
    cartao: 'Cartão',
};

const context = bootImportacoesPage('historico');

if (context) {
    const table = context.root.querySelector('[data-imp-history-table]');
    const rows = table ? Array.from(table.querySelectorAll('[data-imp-history-row]')) : [];
    const count = rows.length;

    const filterForm = context.root.querySelector('[data-imp-history-filters]');
    const filterTarget = context.root.querySelector('[data-imp-history-filter-target]');
    const filterAccount = context.root.querySelector('[data-imp-history-filter-account]');
    const filterSource = context.root.querySelector('[data-imp-history-filter-source]');
    const filterStatus = context.root.querySelector('[data-imp-history-filter-status]');
    const tableWrap = context.root.querySelector('[data-imp-history-table-wrap]');
    const emptyState = context.root.querySelector('[data-imp-history-empty]');
    const rowsBody = context.root.querySelector('[data-imp-history-rows]');
    const filterControls = [filterTarget, filterAccount, filterSource, filterStatus].filter(Boolean);
    let pageInitRequestToken = 0;

    context.setState({
        previewStatus: count > 0 ? 'preview_ready' : 'idle',
        previewRows: rows,
        previewSummary: {
            fileName: '',
            totalRows: count,
            importedRows: 0,
            duplicateRows: 0,
            errorRows: 0,
        },
    });

    bindDeletionActions(context);

    if (filterForm) {
        filterForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            await hydrateHistoryPageInit();
        });
    }

    void hydrateHistoryPageInit();

    function buildFilterParams() {
        return {
            import_target: String(filterTarget?.value || '').trim(),
            conta_id: Number.parseInt(String(filterAccount?.value || '0'), 10) || 0,
            source_type: String(filterSource?.value || '').trim(),
            status: String(filterStatus?.value || '').trim(),
        };
    }

    function setFiltersDisabled(disabled) {
        filterControls.forEach((control) => {
            if ('disabled' in control) {
                control.disabled = disabled;
            }
        });
    }

    function replaceAccountOptions(accounts = [], selectedAccountId = 0) {
        if (!(filterAccount instanceof HTMLSelectElement)) {
            return;
        }

        filterAccount.innerHTML = '';

        const emptyOption = document.createElement('option');
        emptyOption.value = '0';
        emptyOption.textContent = 'Todas as contas';
        filterAccount.appendChild(emptyOption);

        accounts.forEach((account) => {
            const option = document.createElement('option');
            const accountId = Number.parseInt(String(account?.id || '0'), 10);
            option.value = Number.isFinite(accountId) && accountId > 0 ? String(accountId) : '0';
            option.textContent = String(account?.nome || 'Conta sem nome');
            filterAccount.appendChild(option);
        });

        filterAccount.value = Number.isFinite(Number(selectedAccountId)) && Number(selectedAccountId) > 0
            ? String(selectedAccountId)
            : '0';
    }

    function replaceStatusOptions(options = [], selectedStatus = '') {
        if (!(filterStatus instanceof HTMLSelectElement)) {
            return;
        }

        filterStatus.innerHTML = '';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Todos';
        filterStatus.appendChild(emptyOption);

        options.forEach((statusValue) => {
            const normalizedStatus = String(statusValue || '').trim().toLowerCase();
            if (!normalizedStatus) {
                return;
            }

            const option = document.createElement('option');
            option.value = normalizedStatus;
            option.textContent = STATUS_LABELS[normalizedStatus] || normalizedStatus;
            filterStatus.appendChild(option);
        });

        filterStatus.value = String(selectedStatus || '').trim().toLowerCase();
    }

    function updateFilterValues(payload = {}) {
        if (filterTarget instanceof HTMLSelectElement) {
            filterTarget.value = String(payload.selectedImportTarget || '').trim().toLowerCase();
        }

        if (filterSource instanceof HTMLSelectElement) {
            filterSource.value = String(payload.selectedSourceType || '').trim().toLowerCase();
        }

        replaceAccountOptions(payload.accounts || [], Number(payload.selectedAccountId || 0));
        replaceStatusOptions(payload.statusOptions || [], payload.selectedStatus || '');
    }

    function refreshHistoryViewState() {
        const hasRows = context.root.querySelectorAll('[data-imp-history-row]').length > 0;

        if (tableWrap) {
            tableWrap.hidden = !hasRows;
        }

        if (emptyState) {
            emptyState.hidden = hasRows;
        }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[char] || char));
    }

    function buildContextLabel(item) {
        const target = String(item?.import_target || '').trim().toLowerCase();
        if (target === 'cartao') {
            const cardName = String(item?.cartao_nome || '').trim();
            const cardId = Number.parseInt(String(item?.cartao_id || '0'), 10);
            return cardName || (Number.isFinite(cardId) && cardId > 0 ? `Cartão #${cardId}` : '-');
        }

        const accountName = String(item?.conta_nome || '').trim();
        return accountName || '-';
    }

    function createHistoryRowMarkup(item) {
        const batchId = Number.parseInt(String(item?.batch_id || '0'), 10) || 0;
        const totalRows = Number.parseInt(String(item?.total_rows || '0'), 10) || 0;
        const importedRows = Number.parseInt(String(item?.imported_rows || '0'), 10) || 0;
        const duplicateRows = Number.parseInt(String(item?.duplicate_rows || '0'), 10) || 0;
        const errorRows = Number.parseInt(String(item?.error_rows || '0'), 10) || 0;
        const status = String(item?.status || 'processed').trim().toLowerCase();
        const target = String(item?.import_target || 'conta').trim().toLowerCase() === 'cartao' ? 'cartao' : 'conta';
        const canDelete = item?.can_delete !== false;
        const partialDeleteSummary = String(item?.partial_delete_summary || '').trim();
        const filename = String(item?.filename || '').trim();
        const sourceType = String(item?.source_type || '').trim().toUpperCase();
        const createdAt = String(item?.created_at || '').trim();
        const contextLabel = buildContextLabel(item);
        const actionHint = canDelete
            ? 'Remove o lote e os registros ainda intactos.'
            : 'Aguarde o processamento terminar para excluir.';

        return `<tr data-imp-history-row data-batch-id="${batchId}" data-total-rows="${totalRows}" data-imported-rows="${importedRows}" data-duplicate-rows="${duplicateRows}" data-error-rows="${errorRows}">
            <td class="imp-history-table__batch">#${escapeHtml(batchId > 0 ? batchId : '-')}</td>
            <td>${escapeHtml(TARGET_LABELS[target] || 'Conta')}</td>
            <td>${escapeHtml(contextLabel)}</td>
            <td class="imp-history-table__file">
                <div class="imp-history-table__file-name">${escapeHtml(filename)}</div>
                <p class="imp-history-table__summary" data-imp-history-retention-summary ${partialDeleteSummary === '' ? 'hidden' : ''}>${escapeHtml(partialDeleteSummary)}</p>
            </td>
            <td class="imp-history-table__source">${escapeHtml(sourceType)}</td>
            <td data-imp-history-col="total_rows">${totalRows}</td>
            <td data-imp-history-col="imported_rows">${importedRows}</td>
            <td data-imp-history-col="duplicate_rows">${duplicateRows}</td>
            <td data-imp-history-col="error_rows">${errorRows}</td>
            <td>
                <span class="imp-status-badge" data-status="${escapeHtml(status)}" data-imp-history-status-badge>${escapeHtml(STATUS_LABELS[status] || status)}</span>
            </td>
            <td class="imp-history-table__date">${escapeHtml(createdAt)}</td>
            <td class="imp-history-table__actions">
                <button type="button" class="btn btn-ghost imp-history-table__delete" data-imp-history-delete ${canDelete ? '' : 'disabled'}>Excluir importação</button>
                <p class="imp-history-table__action-hint" data-imp-history-action-hint>${escapeHtml(actionHint)}</p>
            </td>
        </tr>`;
    }

    function renderHistoryRows(items = []) {
        if (!(rowsBody instanceof HTMLTableSectionElement)) {
            return;
        }

        rowsBody.innerHTML = Array.isArray(items)
            ? items.map((item) => createHistoryRowMarkup(item)).join('')
            : '';

        bindDeletionActions(context);
        refreshHistoryViewState();
    }

    function applyPageInitPayload(payload = {}) {
        const totals = typeof payload?.totals === 'object' && payload.totals !== null
            ? payload.totals
            : {};
        const items = Array.isArray(payload?.historyItems) ? payload.historyItems : [];

        updateFilterValues(payload);
        renderHistoryRows(items);

        setMetric(context.root, '[data-imp-history-total-batches]', Number(totals.batches || items.length));
        setMetric(context.root, '[data-imp-history-total-rows]', Number(totals.totalRows || 0));
        setMetric(context.root, '[data-imp-history-total-imported]', Number(totals.importedRows || 0));
        setMetric(context.root, '[data-imp-history-total-duplicates]', Number(totals.duplicateRows || 0));
        setMetric(context.root, '[data-imp-history-total-errors]', Number(totals.errorRows || 0));

        context.setState({
            previewStatus: items.length > 0 ? 'preview_ready' : 'idle',
            previewRows: items,
            previewSummary: {
                fileName: '',
                totalRows: Number(totals.totalRows || 0),
                importedRows: Number(totals.importedRows || 0),
                duplicateRows: Number(totals.duplicateRows || 0),
                errorRows: Number(totals.errorRows || 0),
            },
        });

        const url = new URL(window.location.href);
        const nextParams = buildFilterParams();
        Object.entries(nextParams).forEach(([key, value]) => {
            const normalizedValue = String(value || '').trim();
            if (!normalizedValue || normalizedValue === '0') {
                url.searchParams.delete(key);
                return;
            }

            url.searchParams.set(key, normalizedValue);
        });
        window.history.replaceState({}, '', url.toString());
    }

    async function hydrateHistoryPageInit() {
        const requestToken = ++pageInitRequestToken;
        setFiltersDisabled(true);

        try {
            const response = await loadImportacoesHistoricoPageInit(buildFilterParams());
            if (requestToken !== pageInitRequestToken) {
                return;
            }

            applyPageInitPayload(response?.data || {});
        } catch (error) {
            console.error('Erro ao carregar histórico de importações:', error);
            notifyError(resolveErrorMessage(error, 'Erro ao carregar histórico.'));
        } finally {
            if (requestToken === pageInitRequestToken) {
                setFiltersDisabled(false);
            }
        }
    }
}

function bindDeletionActions(context) {
    const buttons = Array.from(context.root.querySelectorAll('[data-imp-history-delete]'));

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            await handleDeleteClick(context, button);
        });
    });
}

async function handleDeleteClick(context, button) {
    if (!(button instanceof HTMLButtonElement) || button.disabled) {
        return;
    }

    const row = button.closest('[data-imp-history-row]');
    if (!(row instanceof HTMLTableRowElement)) {
        return;
    }

    const confirmed = await confirmDelete();
    if (!confirmed) {
        return;
    }

    const batchId = Number.parseInt(String(row.dataset.batchId || '0'), 10);
    if (!Number.isFinite(batchId) || batchId <= 0) {
        notifyError('Não foi possível localizar o endpoint de exclusão.');
        return;
    }

    setButtonLoading(button, true);

    try {
        const payload = await deleteImportacaoHistorico(batchId, context.root);

        const data = payload?.data || {};
        if (data.batch_removed) {
            row.remove();
            refreshHistoryMetrics(context);
        } else if (data.batch) {
            patchRow(row, data.batch);
            refreshHistoryMetrics(context);
        }

        notifySuccess(payload?.message || 'Importação excluída com sucesso.');
    } catch (error) {
        console.error('Erro ao excluir importação:', error);
        notifyError(resolveErrorMessage(error, 'Erro ao excluir importação.'));
    } finally {
        setButtonLoading(button, false);
    }
}

async function confirmDelete() {
    const message = 'Isso removerá o lote, o rastreio da importação e os registros que ainda estiverem intactos. Registros alterados manualmente serão preservados.';

    if (window.LKFeedback?.confirm) {
        const result = await window.LKFeedback.confirm(message, {
            title: 'Excluir importação?',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        });

        return Boolean(result?.isConfirmed);
    }

    return window.confirm(message);
}

function patchRow(row, batch) {
    const totalRows = Number(batch.total_rows || 0);
    const importedRows = Number(batch.imported_rows || 0);
    const duplicateRows = Number(batch.duplicate_rows || 0);
    const errorRows = Number(batch.error_rows || 0);
    const status = String(batch.status || 'processed').trim().toLowerCase();
    const summary = String(batch.partial_delete_summary || '').trim();
    const canDelete = batch.can_delete !== false;

    row.dataset.totalRows = String(totalRows);
    row.dataset.importedRows = String(importedRows);
    row.dataset.duplicateRows = String(duplicateRows);
    row.dataset.errorRows = String(errorRows);

    setCellValue(row, 'total_rows', totalRows);
    setCellValue(row, 'imported_rows', importedRows);
    setCellValue(row, 'duplicate_rows', duplicateRows);
    setCellValue(row, 'error_rows', errorRows);

    const statusBadge = row.querySelector('[data-imp-history-status-badge]');
    if (statusBadge) {
        statusBadge.dataset.status = status;
        statusBadge.textContent = STATUS_LABELS[status] || status;
    }

    const summaryEl = row.querySelector('[data-imp-history-retention-summary]');
    if (summaryEl) {
        summaryEl.textContent = summary;
        summaryEl.hidden = summary === '';
    }

    const button = row.querySelector('[data-imp-history-delete]');
    if (button instanceof HTMLButtonElement) {
        button.disabled = !canDelete;
    }

    const actionHint = row.querySelector('[data-imp-history-action-hint]');
    if (actionHint) {
        actionHint.textContent = canDelete
            ? 'Lote parcialmente preservado. Você pode tentar excluir novamente depois.'
            : 'Aguarde o processamento terminar para excluir.';
    }
}

function setCellValue(row, key, value) {
    const cell = row.querySelector(`[data-imp-history-col="${key}"]`);
    if (cell) {
        cell.textContent = String(value);
    }
}

function refreshHistoryMetrics(context) {
    const rows = Array.from(context.root.querySelectorAll('[data-imp-history-row]'));

    const totals = rows.reduce((carry, row) => ({
        batches: carry.batches + 1,
        totalRows: carry.totalRows + Number(row.dataset.totalRows || 0),
        importedRows: carry.importedRows + Number(row.dataset.importedRows || 0),
        duplicateRows: carry.duplicateRows + Number(row.dataset.duplicateRows || 0),
        errorRows: carry.errorRows + Number(row.dataset.errorRows || 0),
    }), {
        batches: 0,
        totalRows: 0,
        importedRows: 0,
        duplicateRows: 0,
        errorRows: 0,
    });

    setMetric(context.root, '[data-imp-history-total-batches]', totals.batches);
    setMetric(context.root, '[data-imp-history-total-rows]', totals.totalRows);
    setMetric(context.root, '[data-imp-history-total-imported]', totals.importedRows);
    setMetric(context.root, '[data-imp-history-total-duplicates]', totals.duplicateRows);
    setMetric(context.root, '[data-imp-history-total-errors]', totals.errorRows);

    context.setState({
        previewRows: rows,
        previewStatus: rows.length > 0 ? 'preview_ready' : 'idle',
        previewSummary: {
            fileName: '',
            totalRows: totals.totalRows,
            importedRows: totals.importedRows,
            duplicateRows: totals.duplicateRows,
            errorRows: totals.errorRows,
        },
    });

    const tableWrap = context.root.querySelector('[data-imp-history-table-wrap]');
    const emptyState = context.root.querySelector('[data-imp-history-empty]');
    if (tableWrap) {
        tableWrap.hidden = rows.length === 0;
    }

    if (emptyState) {
        emptyState.hidden = rows.length > 0;
    }
}

function setMetric(root, selector, value) {
    root.querySelectorAll(selector).forEach((node) => {
        node.textContent = String(value);
    });
}

function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.dataset.originalText = button.textContent || 'Excluir importação';
        button.disabled = true;
        button.textContent = 'Excluindo...';
        return;
    }

    button.disabled = false;
    button.textContent = button.dataset.originalText || 'Excluir importação';
}

function notifySuccess(message) {
    if (window.LKFeedback?.success) {
        window.LKFeedback.success(message, { toast: true });
        return;
    }

    window.alert(message);
}

function notifyError(message) {
    if (window.LKFeedback?.error) {
        window.LKFeedback.error(message, { toast: true });
        return;
    }

    window.alert(message);
}

function resolveErrorMessage(error, fallback) {
    if (error instanceof Error && String(error.message || '').trim() !== '') {
        return error.message;
    }

    return fallback;
}
