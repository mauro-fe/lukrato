import '../../../css/admin/importacoes/historico.css';
import { bootImportacoesPage, fetchApiJson, findCsrfToken } from './app.js';

const STATUS_LABELS = {
    processing: 'Processando',
    processed: 'Processado',
    processed_with_duplicates: 'Processado com duplicados',
    processed_duplicates_only: 'Somente duplicados',
    processed_with_errors: 'Processado com erros',
    failed: 'Falhou',
};

const context = bootImportacoesPage('historico');

if (context) {
    const table = context.root.querySelector('[data-imp-history-table]');
    const rows = table ? Array.from(table.querySelectorAll('tbody tr')) : [];
    const count = rows.length;

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

    const deleteUrl = String(button.dataset.deleteUrl || '').trim();
    if (!deleteUrl) {
        notifyError('Nao foi possivel localizar o endpoint de exclusao.');
        return;
    }

    setButtonLoading(button, true);

    try {
        const payload = await fetchApiJson(deleteUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: findCsrfToken(context.root),
            }),
        });

        const data = payload?.data || {};
        if (data.batch_removed) {
            row.remove();
            refreshHistoryMetrics(context);

            const remainingRows = context.root.querySelectorAll('[data-imp-history-row]').length;
            if (remainingRows === 0) {
                window.location.reload();
                return;
            }
        } else if (data.batch) {
            patchRow(row, data.batch);
            refreshHistoryMetrics(context);
        }

        notifySuccess(payload?.message || 'Importacao excluida com sucesso.');
    } catch (error) {
        console.error('Erro ao excluir importacao:', error);
        notifyError(resolveErrorMessage(error, 'Erro ao excluir importacao.'));
    } finally {
        setButtonLoading(button, false);
    }
}

async function confirmDelete() {
    const message = 'Isso removera o lote, o rastreio da importacao e os registros que ainda estiverem intactos. Registros alterados manualmente serao preservados.';

    if (window.LKFeedback?.confirm) {
        const result = await window.LKFeedback.confirm(message, {
            title: 'Excluir importacao?',
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
            ? 'Lote parcialmente preservado. Voce pode tentar excluir novamente depois.'
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
        previewSummary: {
            fileName: '',
            totalRows: totals.totalRows,
            importedRows: totals.importedRows,
            duplicateRows: totals.duplicateRows,
            errorRows: totals.errorRows,
        },
    });
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
