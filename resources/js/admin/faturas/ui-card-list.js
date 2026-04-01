/**
 * ============================================================================
 * LUKRATO - Faturas / UI Card List Methods
 * ============================================================================
 * Card rendering and status helpers extracted from ui.js to keep
 * responsibilities isolated and the main UI file smaller.
 * ============================================================================
 */

import { DOM, Utils } from './state.js';
import { refreshIcons } from '../shared/ui.js';

const clamp = (value, min = 0, max = 100) => Math.min(max, Math.max(min, Number(value) || 0));
const safeText = (value, fallback = '') => Utils.escapeHtml(String(value ?? fallback));
const formatPercent = (value, digits = 0) => `${(Number(value) || 0).toLocaleString('pt-BR', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
})}%`;
const buildTooltipAttrs = (title, text) => `data-lk-tooltip-title="${safeText(title)}" data-lk-tooltip="${safeText(text)}"`;
const COLOR_TOKEN_REGEX = /(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))/;

export const CardListMethods = {
    renderParcelamentos(parcelamentos) {
        if (!Array.isArray(parcelamentos) || parcelamentos.length === 0) {
            this.showEmpty();
            return;
        }

        DOM.emptyStateEl.style.display = 'none';
        DOM.containerEl.style.display = 'grid';

        const fragment = document.createDocumentFragment();
        parcelamentos.forEach((parc) => {
            const card = this.createParcelamentoCard(parc);
            fragment.appendChild(card);
        });

        DOM.containerEl.innerHTML = '';
        DOM.containerEl.appendChild(fragment);
        refreshIcons();
    },

    createParcelamentoCard(parc) {
        const progresso = parc.progresso || 0;
        const itensPendentes = parc.parcelas_pendentes || 0;
        const itensPagos = parc.parcelas_pagas || 0;
        const totalItens = itensPagos + itensPendentes;
        const dueMeta = this.getDueMeta(parc);
        const statusMeta = this.getStatusMeta(parc.status, progresso, dueMeta);

        const div = document.createElement('div');
        div.className = `parcelamento-card surface-card surface-card--interactive surface-card--clip status-${parc.status}`;
        div.dataset.id = parc.id;
        div.style.setProperty('--fatura-accent', this.getAccentColorSolid(parc.cartao));

        const statusBadge = this.getStatusBadge(parc.status, progresso, dueMeta);
        const mes = parc.mes_referencia || '';
        const ano = parc.ano_referencia || '';

        div.innerHTML = this.createCardHTML({
            parc,
            statusBadge,
            mes,
            ano,
            itensPendentes,
            itensPagos,
            totalItens,
            progresso,
            dueMeta,
            statusMeta,
        });

        this.attachCardEventListeners(div, parc.id);
        return div;
    },

    attachCardEventListeners(card, id) {
        const btnView = card.querySelector('[data-action="view"]');
        if (btnView) {
            btnView.addEventListener('click', () => this.showDetalhes(id));
        }
    },

    getAccentColorSolid(cartao) {
        const colors = {
            visa: '#1A1F71',
            mastercard: '#EB001B',
            elo: '#FFCB05',
            amex: '#006FCF',
            diners: '#0079BE',
            discover: '#FF6000',
            hipercard: '#B11116',
        };
        const fallback = colors[cartao?.bandeira?.toLowerCase()] || '#3b82f6';
        const rawValue = String(
            cartao?.cor_cartao ||
            cartao?.conta?.instituicao_financeira?.cor_primaria ||
            fallback,
        ).trim();

        if (!rawValue) {
            return fallback;
        }

        if (/gradient/i.test(rawValue)) {
            const match = rawValue.match(COLOR_TOKEN_REGEX);
            return match?.[1] || fallback;
        }

        if (/^var\(/i.test(rawValue) || COLOR_TOKEN_REGEX.test(rawValue)) {
            return rawValue;
        }

        return fallback;
    },

    getBandeiraIcon(bandeira) {
        const svgIcons = {
            visa: '<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71"/><text x="24" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">VISA</text></svg>',
            mastercard: '<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71" opacity="0"/><circle cx="19" cy="16" r="10" fill="#EB001B" opacity=".85"/><circle cx="29" cy="16" r="10" fill="#F79E1B" opacity=".85"/></svg>',
            elo: '<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#000"/><text x="24" y="20" text-anchor="middle" font-size="13" font-weight="bold" fill="#FFCB05" font-family="sans-serif">elo</text></svg>',
            amex: '<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#006FCF"/><text x="24" y="20" text-anchor="middle" font-size="9" font-weight="bold" fill="#fff" font-family="sans-serif">AMEX</text></svg>',
            hipercard: '<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#B11116"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">HIPER</text></svg>',
            diners: '<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#0079BE"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">DINERS</text></svg>',
        };
        return svgIcons[bandeira] || '<i data-lucide="credit-card"></i>';
    },

    getDueMeta(parc) {
        let dataVencStr = parc.data_vencimento;

        if (!dataVencStr && parc.cartao?.dia_vencimento && parc.descricao) {
            const descMatch = parc.descricao.match(/(\d{1,2})\/(\d{4})/);
            if (descMatch) {
                const mesFatura = descMatch[1].padStart(2, '0');
                const anoFatura = descMatch[2];
                const dia = String(parc.cartao.dia_vencimento).padStart(2, '0');
                dataVencStr = `${anoFatura}-${mesFatura}-${dia}`;
            }
        }

        if (!dataVencStr) {
            return {
                hasDate: false,
                label: 'A definir',
                helper: 'Sem data de vencimento informada',
                detailClass: '',
                isVencida: false,
                isProxima: false,
            };
        }

        const dataFormatada = Utils.formatDate(dataVencStr);
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);

        const dataVenc = new Date(`${dataVencStr}T00:00:00`);
        const isPendente = parc.status !== 'paga' && parc.status !== 'concluido' && parc.status !== 'cancelado';
        const isVencida = isPendente && dataVenc < hoje;
        const isProxima = isPendente && !isVencida && (dataVenc - hoje) <= 3 * 24 * 60 * 60 * 1000;

        return {
            hasDate: true,
            raw: dataVencStr,
            label: dataFormatada,
            helper: isVencida ? 'Vencimento expirado' : isProxima ? 'Vence em breve' : 'Dentro do prazo',
            detailClass: isVencida ? 'is-danger' : isProxima ? 'is-warning' : '',
            isVencida,
            isProxima,
        };
    },

    getStatusMeta(status, progresso = null, dueMeta = null) {
        const progressoNormalizado = clamp(progresso);

        if (status === 'cancelado') {
            return {
                badgeClass: 'badge-cancelado',
                progressClass: 'is-muted',
                icon: 'ban',
                label: 'Cancelada',
                shortLabel: 'Cancelada',
                hint: 'Sem cobranca ativa',
                tooltip: 'Esta fatura foi cancelada e nao entra mais no acompanhamento ativo.',
            };
        }

        if (progressoNormalizado >= 100 || status === 'paga' || status === 'concluido') {
            return {
                badgeClass: 'badge-paga',
                progressClass: 'is-safe',
                icon: 'circle-check',
                label: 'Paga',
                shortLabel: 'Liquidada',
                hint: 'Pagamento concluido',
                tooltip: 'O valor desta fatura ja foi quitado integralmente.',
            };
        }

        if (dueMeta?.isVencida) {
            return {
                badgeClass: 'badge-alerta',
                progressClass: 'is-danger',
                icon: 'triangle-alert',
                label: 'Vencida',
                shortLabel: 'Em atraso',
                hint: 'Regularize esta fatura',
                tooltip: 'A fatura passou do vencimento e merece prioridade para evitar juros.',
            };
        }

        if (dueMeta?.isProxima) {
            return {
                badgeClass: 'badge-alerta',
                progressClass: 'is-warning',
                icon: 'clock-3',
                label: 'Vence em breve',
                shortLabel: 'Vence logo',
                hint: 'Priorize o pagamento',
                tooltip: 'O vencimento esta proximo. Vale organizar o pagamento desta fatura.',
            };
        }

        if (progressoNormalizado > 0) {
            return {
                badgeClass: 'badge-parcial',
                progressClass: 'is-warning',
                icon: 'loader-2',
                label: 'Pagamento parcial',
                shortLabel: 'Parcial',
                hint: 'Parte do valor ja foi paga',
                tooltip: 'A fatura segue aberta, mas ja possui pagamentos registrados.',
            };
        }

        return {
            badgeClass: 'badge-pendente',
            progressClass: 'is-safe',
            icon: 'clock-3',
            label: 'Pendente',
            shortLabel: 'No prazo',
            hint: 'Aguardando pagamento',
            tooltip: 'A fatura segue aberta e ainda esta dentro do prazo normal de pagamento.',
        };
    },

    getResumoPrincipal(parc, dueMeta, statusMeta, itensPendentes, itensPagos, totalItens) {
        const temEstornos = parc.total_estornos && parc.total_estornos > 0;
        const pagamentoLabel = totalItens > 0
            ? `${itensPagos} de ${totalItens} itens pagos`
            : 'Sem itens consolidados';
        const dueTag = dueMeta.hasDate && dueMeta.helper !== 'Dentro do prazo'
            ? `<span class="fatura-card-due-tag ${dueMeta.detailClass}">${safeText(dueMeta.helper)}</span>`
            : '';

        return `
            <div class="fatura-card-main">
                <span class="resumo-label">Valor total da fatura</span>
                <strong class="resumo-valor">${Utils.formatMoney(parc.valor_total)}</strong>
                <div class="fatura-card-due-line ${dueMeta.detailClass}">
                    <span class="fatura-card-due-copy">Vencimento ${safeText(dueMeta.label)}</span>
                    ${dueTag}
                </div>
                ${temEstornos ? `
                    <p class="fatura-card-note">
                        Inclui ${Utils.formatMoney(parc.total_estornos)} em estornos no fechamento.
                    </p>
                ` : ''}
            </div>

            <div class="fatura-card-details">
                <div class="fatura-card-detail ${dueMeta.detailClass}" ${buildTooltipAttrs('Vencimento', dueMeta.hasDate
                    ? `Data prevista para pagamento desta fatura: ${dueMeta.label}.`
                    : 'A fatura ainda nao possui data de vencimento consolidada.')}>
                    <span class="fatura-card-detail-label">Vencimento</span>
                    <strong class="fatura-card-detail-value">${safeText(dueMeta.label)}</strong>
                    <span class="fatura-card-detail-meta">${safeText(dueMeta.helper)}</span>
                </div>

                <div class="fatura-card-detail ${statusMeta.progressClass}" ${buildTooltipAttrs('Progresso de pagamento', totalItens > 0
                    ? `${itensPagos} de ${totalItens} itens ja foram pagos nesta fatura.`
                    : 'Ainda nao existem itens suficientes para calcular o progresso de pagamento.')}>
                    <span class="fatura-card-detail-label">Pagamento</span>
                    <strong class="fatura-card-detail-value">${totalItens > 0 ? `${itensPagos}/${totalItens}` : '--'}</strong>
                    <span class="fatura-card-detail-meta">${safeText(pagamentoLabel)}</span>
                </div>
            </div>
        `;
    },

    getProgressoSection(totalItens, itensPendentes, itensPagos, progresso, statusMeta) {
        const progressoNormalizado = clamp(progresso);
        const progressWidth = progressoNormalizado > 0 ? Math.max(progressoNormalizado, 8) : 0;

        if (totalItens === 0) {
            return `
                <div class="parc-progress-section is-empty">
                    <div class="parc-progress-header">
                        <span class="parc-progress-text">Sem itens suficientes para medir o pagamento</span>
                        <span class="parc-progress-percent">--</span>
                    </div>
                    <div class="parc-progress-bar">
                        <div class="parc-progress-fill ${statusMeta.progressClass}" style="width: 0%"></div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="parc-progress-section ${statusMeta.progressClass}">
                <div class="parc-progress-header">
                    <span class="parc-progress-text">Pagamento ${formatPercent(progressoNormalizado)}</span>
                    <span class="parc-progress-percent">${safeText(statusMeta.shortLabel)}</span>
                </div>
                <div class="parc-progress-bar">
                    <div class="parc-progress-fill ${statusMeta.progressClass}" style="width: ${progressWidth}%"></div>
                </div>
                <div class="parc-progress-foot">
                    <span>${itensPagos} de ${totalItens} itens pagos</span>
                    <span>${itensPendentes} em aberto</span>
                </div>
            </div>
        `;
    },

    getStatusBadge(status, progresso = null, dueMeta = null) {
        const meta = this.getStatusMeta(status, progresso, dueMeta);
        return `
            <span
                class="parc-card-badge ${meta.badgeClass}"
                ${buildTooltipAttrs(meta.label, meta.tooltip)}>
                <i data-lucide="${meta.icon}" style="width:12px;height:12px"></i>
                ${safeText(meta.label)}
            </span>
        `;
    },

    createCardHTML({ parc, statusBadge, mes, ano, itensPendentes, itensPagos, totalItens, progresso, dueMeta, statusMeta }) {
        const resumoPrincipal = this.getResumoPrincipal(parc, dueMeta, statusMeta, itensPendentes, itensPagos, totalItens);
        const progressoSection = this.getProgressoSection(totalItens, itensPendentes, itensPagos, progresso, statusMeta);
        const cartaoNome = parc.cartao ? (parc.cartao.nome || parc.cartao.bandeira || 'Cartao') : 'Cartao';
        const instituicaoNome = parc.cartao?.conta?.instituicao_financeira?.nome || 'Sem instituicao';
        const cartaoNumero = parc.cartao?.ultimos_digitos ? `Final ${parc.cartao.ultimos_digitos}` : '';
        const accentColor = this.getAccentColorSolid(parc.cartao);
        const bandeira = parc.cartao?.bandeira?.toLowerCase() || 'outros';
        const bandeiraIcon = this.getBandeiraIcon(bandeira);
        const periodoLabel = mes && ano ? `${mes}/${ano}` : 'Fatura atual';
        const listSubline = [safeText(instituicaoNome), cartaoNumero ? safeText(cartaoNumero) : '']
            .filter(Boolean)
            .join(' - ');

        return `
            <div class="fatura-card-shell" style="--fatura-accent:${accentColor};">
                <div class="fatura-card-top">
                    <div class="fatura-card-media">
                        <div class="fatura-card-brand" aria-hidden="true">
                            ${bandeiraIcon}
                        </div>
                    </div>

                    <div class="fatura-card-head">
                        <div class="fatura-card-title-wrap">
                            <span class="fatura-card-title">${safeText(cartaoNome)}</span>
                            <span class="fatura-card-subtitle">${safeText(instituicaoNome)}</span>
                        </div>
                        <div class="fatura-card-meta">
                            <span class="fatura-card-period" ${buildTooltipAttrs('Periodo da fatura', 'Competencia consolidada desta fatura para acompanhar fechamento e vencimento.')}>
                                <i data-lucide="calendar-days"></i>
                                <span>${safeText(periodoLabel)}</span>
                            </span>
                            ${statusBadge}
                        </div>
                    </div>
                </div>

                <div class="fatura-list-info">
                    <span class="list-cartao-nome">${safeText(cartaoNome)}</span>
                    <span class="list-periodo">${safeText(periodoLabel)}</span>
                    <span class="list-cartao-numero">${listSubline}</span>
                </div>

                <div class="fatura-resumo-principal">${resumoPrincipal}</div>
                ${progressoSection}
                <div class="fatura-status-col">${statusBadge}</div>
                <div class="parc-card-actions">
                    <button class="parc-btn parc-btn-view" data-action="view" data-id="${parc.id}">
                        <i data-lucide="eye"></i>
                        <span>Ver detalhes</span>
                    </button>
                </div>
            </div>
        `;
    },
};
