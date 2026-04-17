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

export function createExportHandler({
    getReportType,
    showRestrictionAlert,
    handleRestrictedAccess,
}) {
    return async function handleExport() {
        if (!window.IS_PRO) {
            return showRestrictionAlert('Exportação de relatórios é exclusiva do plano PRO.');
        }

        const currentType = getReportType() || 'despesas_por_categoria';

        const { value: formValues } = await Swal.fire({
            title: 'Exportar Relatório',
            html: `
                <div style="text-align:left;display:flex;flex-direction:column;gap:12px;padding-top:8px;">
                    <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Tipo de Relatório</label>
                    <select id="swalExportType" class="swal2-select" style="width:100%;font-size:0.9rem;">
                        <option value="despesas_por_categoria" ${currentType === 'despesas_por_categoria' ? 'selected' : ''}>Despesas por Categoria</option>
                        <option value="receitas_por_categoria" ${currentType === 'receitas_por_categoria' ? 'selected' : ''}>Receitas por Categoria</option>
                        <option value="saldo_mensal" ${currentType === 'saldo_mensal' ? 'selected' : ''}>Saldo Diário</option>
                        <option value="receitas_despesas_diario" ${currentType === 'receitas_despesas_diario' ? 'selected' : ''}>Receitas x Despesas Diário</option>
                        <option value="evolucao_12m" ${currentType === 'evolucao_12m' ? 'selected' : ''}>Evolução 12 Meses</option>
                        <option value="receitas_despesas_por_conta" ${currentType === 'receitas_despesas_por_conta' ? 'selected' : ''}>Receitas x Despesas por Conta</option>
                        <option value="cartoes_credito" ${currentType === 'cartoes_credito' ? 'selected' : ''}>Relatório de Cartões</option>
                        <option value="resumo_anual" ${currentType === 'resumo_anual' ? 'selected' : ''}>Resumo Anual</option>
                        <option value="despesas_anuais_por_categoria" ${currentType === 'despesas_anuais_por_categoria' ? 'selected' : ''}>Despesas Anuais por Categoria</option>
                        <option value="receitas_anuais_por_categoria" ${currentType === 'receitas_anuais_por_categoria' ? 'selected' : ''}>Receitas Anuais por Categoria</option>
                    </select>
                    <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Formato</label>
                    <select id="swalExportFormat" class="swal2-select" style="width:100%;font-size:0.9rem;">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel (.xlsx)</option>
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Exportar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e67e22',
            preConfirm: () => ({
                type: document.getElementById('swalExportType').value,
                format: document.getElementById('swalExportFormat').value,
            }),
        });

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

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Relatório exportado!',
                    text: filename,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            }
        } catch (error) {
            if (await handleRestrictedAccess(error)) {
                return;
            }
            console.error('Export error:', error);
            const message = getErrorMessage(error, 'Erro ao exportar relatório. Tente novamente.');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Erro ao exportar',
                    text: message,
                    showConfirmButton: false,
                    timer: 3000,
                });
            } else {
                alert(message);
            }
        } finally {
            if (exportBtn) {
                exportBtn.disabled = false;
                exportBtn.innerHTML = originalHTML;
            }
        }
    };
}
