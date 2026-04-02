import { escapeHtml } from './utils.js';

export function canLinkMetaInLancamento({
    tipo,
    formaPagamento = '',
    allowTransfer = false,
}) {
    const normalizedTipo = String(tipo || '').toLowerCase().trim();
    const normalizedPayment = String(formaPagamento || '').toLowerCase().trim();

    if (normalizedTipo === 'receita') {
        return normalizedPayment !== 'estorno_cartao';
    }

    if (normalizedTipo === 'despesa') {
        return normalizedPayment !== 'cartao_credito';
    }

    if (normalizedTipo === 'transferencia') {
        return allowTransfer;
    }

    return false;
}

export function parseSelectedMetaId(valueOrSelect) {
    const raw = typeof valueOrSelect === 'object' && valueOrSelect !== null
        ? String(valueOrSelect.value || '').trim()
        : String(valueOrSelect || '').trim();

    const parsed = Number(raw);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

export function resolveMetaOperationForLancamento({
    tipo,
    hasMetaLink,
    isRealizacao = false,
}) {
    if (!hasMetaLink) return null;

    const normalizedTipo = String(tipo || '').toLowerCase().trim();
    if (normalizedTipo === 'despesa') {
        return isRealizacao ? 'realizacao' : 'resgate';
    }

    return 'aporte';
}

export function summarizeMetaTitles(metas = []) {
    const titles = metas
        .map((meta) => String(meta?.titulo || '').trim())
        .filter(Boolean);

    if (titles.length === 0) return 'suas metas';
    if (titles.length === 1) return titles[0];
    if (titles.length === 2) return `${titles[0]} e ${titles[1]}`;
    return `${titles[0]}, ${titles[1]} e mais ${titles.length - 2}`;
}

export function buildPlanningAlertCard({
    tone = 'info',
    icon = 'target',
    eyebrow,
    title,
    message,
}) {
    return `
        <div class="lk-planning-alert is-${tone}">
            <div class="lk-planning-alert__icon">
                <i data-lucide="${icon}"></i>
            </div>
            <div class="lk-planning-alert__body">
                <span class="lk-planning-alert__eyebrow">${escapeHtml(eyebrow || 'Planejamento')}</span>
                <strong class="lk-planning-alert__title">${escapeHtml(title || '')}</strong>
                <p class="lk-planning-alert__message">${escapeHtml(message || '')}</p>
            </div>
        </div>
    `;
}

export function formatMetaOptionLabel(meta, formatPercent = null) {
    const title = String(meta?.titulo || 'Meta').trim();
    const status = String(meta?.status || '').trim().toLowerCase();

    if (status === 'concluida') return `${title} • Concluida`;
    if (status === 'pausada') return `${title} • Pausada`;
    if (status === 'cancelada') return `${title} • Cancelada`;

    const progress = Number(meta?.progresso ?? 0);
    if (Number.isFinite(progress) && progress > 0) {
        if (typeof formatPercent === 'function') {
            return `${title} • ${formatPercent(progress)}`;
        }

        return `${title} • ${progress.toFixed(1)}%`;
    }

    return title;
}
