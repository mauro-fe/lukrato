/**
 * ============================================================================
 * LUKRATO - Relatorios / Export
 * ============================================================================
 * Report export flow extracted from app.js.
 * ============================================================================
 */

import { STATE, Utils } from './state.js';
import { apiFetch, getErrorMessage } from '../shared/api.js';
import { resolveReportsExportEndpoint } from '../api/endpoints/reports.js';

function getFocusableElements(container) {
    return Array.from(container.querySelectorAll(
        'button:not([disabled]), select:not([disabled]), input:not([disabled]), [href], [tabindex]:not([tabindex="-1"])'
    )).filter((element) => element.offsetParent !== null);
}

function showExportToast(type, message, detail = '') {
    const text = detail ? `${message}: ${detail}` : message;

    if (typeof window.showToast === 'function') {
        window.showToast(text, type, type === 'error' ? 4500 : 3000);
        return;
    }

    let container = document.getElementById('relExportToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'relExportToastContainer';
        container.className = 'rel-export-toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `rel-export-toast rel-export-toast--${type}`;
    toast.textContent = text;
    container.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('is-visible'));
    setTimeout(() => {
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 220);
    }, type === 'error' ? 4500 : 3000);
}

function openExportDialog(currentType) {
    const overlay = document.getElementById('relExportModalOverlay');
    const modal = overlay?.querySelector('.rel-export-modal');
    const form = document.getElementById('relExportForm');
    const typeSelect = document.getElementById('relExportType');

    if (!overlay || !modal || !form || !typeSelect) {
        return Promise.resolve(null);
    }

    const hasCurrentType = Array.from(typeSelect.options).some((option) => option.value === currentType);
    typeSelect.value = hasCurrentType ? currentType : 'despesas_por_categoria';

    const pdfInput = form.querySelector('input[name="format"][value="pdf"]');
    if (pdfInput) {
        pdfInput.checked = true;
    }

    const previousFocus = document.activeElement;

    return new Promise((resolve) => {
        let resolved = false;

        const cleanup = () => {
            form.removeEventListener('submit', onSubmit);
            overlay.removeEventListener('click', onOverlayClick);
            document.removeEventListener('keydown', onKeyDown);
        };

        const close = (value = null) => {
            if (resolved) return;
            resolved = true;
            cleanup();
            overlay.classList.remove('is-open');
            document.body.classList.remove('rel-export-modal-open');
            setTimeout(() => {
                overlay.hidden = true;
                if (previousFocus && typeof previousFocus.focus === 'function') {
                    previousFocus.focus();
                }
            }, 140);
            resolve(value);
        };

        function onSubmit(event) {
            event.preventDefault();
            close({
                type: typeSelect.value,
                format: form.elements.format?.value || 'pdf',
            });
        }

        function onOverlayClick(event) {
            if (event.target === overlay || event.target.closest('[data-rel-export-close]')) {
                event.preventDefault();
                close(null);
            }
        }

        function onKeyDown(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                close(null);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusable = getFocusableElements(modal);
            if (focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        form.addEventListener('submit', onSubmit);
        overlay.addEventListener('click', onOverlayClick);
        document.addEventListener('keydown', onKeyDown);

        overlay.hidden = false;
        document.body.classList.add('rel-export-modal-open');
        requestAnimationFrame(() => {
            overlay.classList.add('is-open');
            window.lucide?.createIcons?.();
            typeSelect.focus();
        });
    });
}

export function createExportHandler({
    getReportType,
    showRestrictionAlert,
    handleRestrictedAccess,
}) {
    return async function handleExport() {
        if (!window.IS_PRO) {
            return showRestrictionAlert('Exportacao de relatorios e exclusiva do plano PRO.');
        }

        const currentType = getReportType() || 'despesas_por_categoria';
        const formValues = await openExportDialog(currentType);

        if (!formValues) return;

        const exportBtn = document.getElementById('exportBtn');
        const originalHTML = exportBtn ? exportBtn.innerHTML : '';
        if (exportBtn) {
            exportBtn.disabled = true;
            exportBtn.innerHTML = `
                <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
                <span>Exportando...</span>
            `;
        }

        try {
            const type = formValues.type;
            const format = formValues.format;

            const params = new URLSearchParams({
                type,
                format,
                year: STATE.currentMonth.split('-')[0],
                month: STATE.currentMonth.split('-')[1],
            });

            if (STATE.currentAccount) {
                params.set('account_id', STATE.currentAccount);
            }

            const response = await apiFetch(`${resolveReportsExportEndpoint()}?${params.toString()}`, {
                method: 'GET',
            }, {
                responseType: 'response',
            });

            const blob = await response.blob();
            const disposition = response.headers.get('Content-Disposition');
            const filename = Utils.extractFilename(disposition)
                || (format === 'excel' ? 'relatorio.xlsx' : 'relatorio.pdf');

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);

            showExportToast('success', 'Relatorio exportado', filename);
        } catch (error) {
            if (await handleRestrictedAccess(error)) {
                return;
            }
            console.error('Export error:', error);
            const message = getErrorMessage(error, 'Erro ao exportar relatorio. Tente novamente.');
            showExportToast('error', 'Erro ao exportar', message);
        } finally {
            if (exportBtn) {
                exportBtn.disabled = false;
                exportBtn.innerHTML = originalHTML;
            }
        }
    };
}
