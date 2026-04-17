import { resolveLancamentosEndpoint } from '../api/endpoints/lancamentos.js';
import { apiGet, getErrorMessage } from './api.js';
import { refreshIcons } from './ui.js';
import { escapeHtml, formatMoney } from './utils.js';

function formatHistoryDate(dateValue) {
    const normalized = String(dateValue || '').trim();
    const datePart = normalized.includes('T') ? normalized.split('T')[0] : normalized.split(' ')[0];

    if (!/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
        return '';
    }

    return new Date(`${datePart}T00:00:00`).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short'
    });
}

function buildHistoryState(icon, message) {
    return `
        <div class="lk-historico-empty">
            <i data-lucide="${icon}"></i>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

function resolveMovementMeta(lancamento, contaId) {
    const isTransfer = Boolean(lancamento?.eh_transferencia) || String(lancamento?.tipo || '') === 'transferencia';

    if (isTransfer) {
        const isIncomingTransfer = Number(lancamento?.conta_id_destino || 0) === Number(contaId);

        return {
            tipoClass: 'transferencia',
            tipoIcon: 'arrow-left-right',
            sinal: isIncomingTransfer ? '+' : '-'
        };
    }

    const isReceita = String(lancamento?.tipo || '') === 'receita';

    return {
        tipoClass: isReceita ? 'receita' : 'despesa',
        tipoIcon: isReceita ? 'arrow-down' : 'arrow-up',
        sinal: isReceita ? '+' : '-'
    };
}

export function renderLancamentoHistoryPlaceholder(containerEl, message = 'Selecione uma conta para ver as ultimas movimentações.') {
    if (!containerEl) return;

    containerEl.innerHTML = buildHistoryState('history', message);
    refreshIcons();
}

export function renderLancamentoHistory(containerEl, lancamentos, contaId, emptyMessage = 'Nenhuma movimentacao recente') {
    if (!containerEl) return;

    if (!Array.isArray(lancamentos) || lancamentos.length === 0) {
        containerEl.innerHTML = buildHistoryState('inbox', emptyMessage);
        refreshIcons();
        return;
    }

    containerEl.innerHTML = lancamentos.map((lancamento) => {
        const { tipoClass, tipoIcon, sinal } = resolveMovementMeta(lancamento, contaId);
        const valorFormatado = formatMoney(Math.abs(Number(lancamento?.valor || 0)));
        const dataFormatada = formatHistoryDate(lancamento?.data);

        return `
            <div class="lk-historico-item surface-card lk-historico-${tipoClass}">
                <div class="lk-historico-icon">
                    <i data-lucide="${tipoIcon}"></i>
                </div>
                <div class="lk-historico-info">
                    <div class="lk-historico-desc">${escapeHtml(lancamento?.descricao || 'Sem descricao')}</div>
                    <div class="lk-historico-cat">${escapeHtml(lancamento?.categoria || 'Sem categoria')}</div>
                </div>
                <div class="lk-historico-right">
                    <div class="lk-historico-valor">${sinal} ${valorFormatado}</div>
                    <div class="lk-historico-data">${escapeHtml(dataFormatada)}</div>
                </div>
            </div>
        `;
    }).join('');

    refreshIcons();
}

export async function loadLancamentoRecentHistory({
    contaId,
    containerEl,
    limit = 5,
    lookbackDays = 120,
    emptyMessage = 'Nenhuma movimentacao recente',
    errorMessage = 'Erro ao carregar historico'
}) {
    if (!containerEl) return [];

    if (!contaId) {
        renderLancamentoHistoryPlaceholder(containerEl);
        return [];
    }

    containerEl.innerHTML = buildHistoryState('loader-2', 'Carregando ultimas movimentações...');
    refreshIcons();

    const endDate = new Date();
    const startDate = new Date(endDate);
    startDate.setDate(startDate.getDate() - lookbackDays);

    try {
        const result = await apiGet(resolveLancamentosEndpoint(), {
            account_id: String(contaId),
            limit: String(limit),
            start_date: startDate.toISOString().slice(0, 10),
            end_date: endDate.toISOString().slice(0, 10)
        });
        const lancamentos = Array.isArray(result) ? result : (result.data || result.lancamentos || []);

        renderLancamentoHistory(containerEl, lancamentos, contaId, emptyMessage);
        return lancamentos;
    } catch (error) {
        console.error('Erro ao carregar historico recente:', error);
        containerEl.innerHTML = buildHistoryState('circle-alert', getErrorMessage(error, errorMessage));
        refreshIcons();
        return [];
    }
}
