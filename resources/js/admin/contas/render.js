/**
 * ============================================================================
 * LUKRATO - Contas / Render
 * ============================================================================
 * Rendering helpers: account cards, institution selects, stats.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules, escapeHtml } from './state.js';
import { refreshIcons } from '../shared/ui.js';

export const ContasRender = {
    getFilteredContas() {
        const query = String(STATE.searchQuery || '').trim().toLowerCase();
        const typeFilter = STATE.typeFilter || 'all';

        return STATE.contas.filter((conta) => {
            const tipoConta = conta.tipo_conta || conta.tipo || 'conta_corrente';
            if (typeFilter !== 'all' && tipoConta !== typeFilter) {
                return false;
            }

            if (!query) return true;

            const instituicao = conta.instituicao_financeira || Utils.getInstituicao(conta.instituicao_financeira_id);
            const haystack = [
                conta.nome,
                instituicao?.nome,
                Utils.formatTipoConta(tipoConta),
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return haystack.includes(query);
        });
    },

    updatePageContext(contasVisiveis = ContasRender.getFilteredContas()) {
        const query = String(STATE.searchQuery || '').trim();
        const typeFilter = STATE.typeFilter || 'all';
        const titleEl = document.getElementById('contasContextTitle');
        const descriptionEl = document.getElementById('contasContextDescription');
        const chipsEl = document.getElementById('contasContextChips');
        const negativeEl = document.getElementById('contasNegativasCount');
        const positiveEl = document.getElementById('contasPositivasCount');
        const allCount = STATE.contas.length;

        const negativeCount = contasVisiveis.filter((conta) => (conta.saldoAtual ?? 0) < -0.009).length;
        const positiveCount = contasVisiveis.filter((conta) => (conta.saldoAtual ?? 0) >= -0.009).length;

        if (titleEl) {
            titleEl.textContent = query || typeFilter !== 'all'
                ? `${contasVisiveis.length} conta(s) na visualiza\u00e7\u00e3o atual`
                : 'Suas contas ativas em tempo real';
        }

        if (descriptionEl) {
            if (STATE.lastLoadError && !STATE.contas.length) {
                descriptionEl.textContent = STATE.lastLoadError;
            } else if (query || typeFilter !== 'all') {
                descriptionEl.textContent = 'A lista abaixo est\u00e1 filtrada, mas os cards do topo continuam mostrando o saldo total atual da carteira.';
            } else {
                descriptionEl.textContent = 'Esta p\u00e1gina mostra a posi\u00e7\u00e3o atual das contas ativas, incluindo investimentos. Para an\u00e1lises por per\u00edodo, use os relat\u00f3rios.';
            }
        }

        if (negativeEl) negativeEl.textContent = negativeCount;
        if (positiveEl) positiveEl.textContent = positiveCount;

        if (chipsEl) {
            const chips = [
                '<span class="contas-context-chip info">Exclus\u00e3o permanente fica em Arquivadas</span>',
                `<span class="contas-context-chip neutral">Mostrando ${contasVisiveis.length} de ${allCount} conta(s)</span>`,
            ];

            if (STATE.lastLoadedAt) {
                chips.push(`<span class="contas-context-chip success">Atualizado em ${escapeHtml(Utils.formatDateTime(STATE.lastLoadedAt))}</span>`);
            }

            if (query) {
                chips.push(`<span class="contas-context-chip accent">Busca: ${escapeHtml(query)}</span>`);
            }

            if (typeFilter !== 'all') {
                chips.push(`<span class="contas-context-chip warning">Tipo: ${escapeHtml(Utils.formatTipoConta(typeFilter))}</span>`);
            }

            chipsEl.innerHTML = chips.join('');
        }
    },

    updateFilterSummary(contasVisiveis = ContasRender.getFilteredContas()) {
        const summaryEl = document.getElementById('contasFilterSummary');
        if (!summaryEl) return;

        const query = String(STATE.searchQuery || '').trim();
        const typeFilter = STATE.typeFilter || 'all';

        if (STATE.lastLoadError && !STATE.contas.length) {
            summaryEl.innerHTML = `
                <div class="contas-filter-summary-text error">
                    <i data-lucide="triangle-alert"></i>
                    <span>${escapeHtml(STATE.lastLoadError)}</span>
                </div>
            `;
            return;
        }

        if (!query && typeFilter === 'all') {
            summaryEl.innerHTML = `
                <div class="contas-filter-summary-text">
                    <i data-lucide="info"></i>
                    <span>Os saldos acima representam a posi\u00e7\u00e3o atual consolidada das contas ativas, incluindo investimentos.</span>
                </div>
            `;
            return;
        }

        summaryEl.innerHTML = `
            <div class="contas-filter-summary-text">
                <i data-lucide="filter"></i>
                <span>Filtros ativos: ${contasVisiveis.length} conta(s) encontradas. ${query ? `Busca por "${escapeHtml(query)}". ` : ''}${typeFilter !== 'all' ? `Tipo ${escapeHtml(Utils.formatTipoConta(typeFilter))}.` : ''}</span>
                <button type="button" class="contas-inline-action" data-action="clear-contas-filters">Limpar filtros</button>
            </div>
        `;
    },

    renderErrorState(message) {
        return `
            <div class="error-state">
                <i data-lucide="triangle-alert"></i>
                <p class="error-message">${escapeHtml(message)}</p>
                <button class="btn btn-primary btn-retry" data-action="retry-load-contas">
                    <i data-lucide="refresh-cw"></i> Tentar novamente
                </button>
            </div>
        `;
    },

    renderEmptyState() {
        return `
            <div class="empty-state contas-empty-state">
                <div class="empty-icon">
                    <i data-lucide="wallet"></i>
                </div>
                <h3>Nenhuma conta cadastrada</h3>
                <p>Comece criando sua primeira conta para acompanhar seu saldo atual em um s\u00f3 lugar.</p>
                <button class="btn btn-primary btn-lg" data-action="create-first-account">
                    <i data-lucide="plus"></i> Criar primeira conta
                </button>
            </div>
        `;
    },

    renderFilteredEmptyState() {
        return `
            <div class="empty-state contas-empty-state">
                <div class="empty-icon">
                    <i data-lucide="search-x"></i>
                </div>
                <h3>Nenhuma conta encontrada</h3>
                <p>Ajuste a busca ou o tipo selecionado para voltar a ver suas contas ativas.</p>
                <button class="btn btn-light" data-action="clear-contas-filters">
                    <i data-lucide="x"></i> Limpar filtros
                </button>
            </div>
        `;
    },

    renderContas() {
        const container = document.getElementById('accountsGrid');
        if (!container) {
            console.error('accountsGrid nao encontrado.');
            return;
        }

        const contasVisiveis = ContasRender.getFilteredContas();
        container.setAttribute('aria-busy', STATE.isLoadingContas ? 'true' : 'false');

        if (STATE.lastLoadError && STATE.contas.length === 0) {
            container.innerHTML = ContasRender.renderErrorState(STATE.lastLoadError);
            ContasRender.updatePageContext([]);
            ContasRender.updateFilterSummary([]);
            refreshIcons();
            return;
        }

        if (STATE.contas.length === 0) {
            container.innerHTML = ContasRender.renderEmptyState();
            ContasRender.updatePageContext([]);
            ContasRender.updateFilterSummary([]);
            refreshIcons();
            Modules.Events?.attachContaCardListeners?.();
            return;
        }

        if (contasVisiveis.length === 0) {
            container.innerHTML = ContasRender.renderFilteredEmptyState();
            ContasRender.updatePageContext([]);
            ContasRender.updateFilterSummary([]);
            refreshIcons();
            Modules.Events?.attachContaCardListeners?.();
            return;
        }

        container.innerHTML = contasVisiveis.map((conta) => ContasRender.createContaCard(conta)).join('');
        ContasRender.updatePageContext(contasVisiveis);
        ContasRender.updateFilterSummary(contasVisiveis);
        refreshIcons();
        Modules.Events?.attachContaCardListeners?.();
    },

    createContaCard(conta) {
        const instituicao = conta.instituicao_financeira || Utils.getInstituicao(conta.instituicao_financeira_id);
        const logoUrl = instituicao?.logo_url || `${CONFIG.BASE_URL}assets/img/banks/default.svg`;
        const corPrimaria = instituicao?.cor_primaria || '#667eea';

        let saldo = conta.saldoAtual ?? 0;
        if (Math.abs(saldo) < 0.01) saldo = 0;

        const saldoClass = saldo >= 0 ? 'positive' : 'negative';
        const tipoConta = conta.tipo_conta || conta.tipo || 'conta_corrente';
        const tipoLabel = Utils.formatTipoConta(tipoConta);
        const tipoClass = Utils.getTipoContaClass(tipoConta);

        return `
            <div class="account-card" data-account-id="${conta.id}">
                <div class="account-header" style="background: ${corPrimaria};">
                    <div class="account-logo">
                        <img src="${logoUrl}" alt="${escapeHtml(conta.nome)}" />
                    </div>
                    <div class="account-actions">
                        <button class="btn-icon" onclick="contasManager.editConta(${conta.id})" title="Editar">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button class="btn-icon" onclick="contasManager.moreConta(${conta.id}, event)" title="Mais op\u00e7\u00f5es">
                            <i data-lucide="more-vertical"></i>
                        </button>
                    </div>
                </div>
                <div class="account-body">
                    <h3 class="account-name">${escapeHtml(conta.nome)}</h3>
                    <div class="account-institution">${escapeHtml(instituicao ? instituicao.nome : 'Institui\u00e7\u00e3o n\u00e3o definida')}</div>
                    <span class="account-type-badge ${tipoClass}">${escapeHtml(tipoLabel)}</span>
                    <div class="account-balance ${saldoClass}">
                        ${Utils.formatCurrency(saldo)}
                    </div>
                    <div class="account-info">
                        <button class="btn-new-transaction" data-conta-id="${conta.id}" title="Novo lan\u00e7amento">
                            <i data-lucide="circle-plus"></i> Novo Lan\u00e7amento
                        </button>
                    </div>
                    ${ContasRender.renderCartoesBadge(conta)}
                </div>
                <div class="account-list-actions">
                    <button class="btn-icon" onclick="contasManager.editConta(${conta.id})" title="Editar">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="btn-icon" onclick="contasManager.moreConta(${conta.id}, event)" title="Mais op\u00e7\u00f5es">
                        <i data-lucide="more-vertical"></i>
                    </button>
                </div>
            </div>
        `;
    },

    renderCartoesBadge() {
        return '';
    },

    renderInstituicoesSelect() {
        const select = document.getElementById('instituicaoFinanceiraSelect');
        if (!select) return;

        const grupos = Utils.groupByTipo(STATE.instituicoes);

        select.innerHTML = '<option value="">Selecione uma institui\u00e7\u00e3o</option>';

        Object.keys(grupos).forEach((tipo) => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = Utils.formatTipo(tipo);

            grupos[tipo].forEach((inst) => {
                const option = document.createElement('option');
                option.value = inst.id;
                option.textContent = inst.nome;
                option.dataset.codigo = inst.codigo;
                option.dataset.cor = inst.cor_primaria;
                optgroup.appendChild(option);
            });

            select.appendChild(optgroup);
        });
    },

    updateStats() {
        const totalContas = STATE.contas.length;
        const tiposInvestimento = ['conta_investimento'];
        const contasNormais = STATE.contas.filter((conta) => !tiposInvestimento.includes(conta.tipo_conta));
        const contasInvest = STATE.contas.filter((conta) => tiposInvestimento.includes(conta.tipo_conta));

        const saldoContas = contasNormais.reduce((sum, conta) => sum + (conta.saldoAtual ?? 0), 0);
        const saldoInvest = contasInvest.reduce((sum, conta) => sum + (conta.saldoAtual ?? 0), 0);
        const saldoTotal = saldoContas + saldoInvest;

        const totalContasEl = document.getElementById('totalContas');
        const saldoTotalEl = document.getElementById('saldoTotal');
        const saldoInvestEl = document.getElementById('saldoInvestimentos');

        if (totalContasEl) totalContasEl.textContent = totalContas;
        if (saldoTotalEl) saldoTotalEl.textContent = Utils.formatCurrency(saldoTotal);
        if (saldoInvestEl) saldoInvestEl.textContent = Utils.formatCurrency(saldoInvest);
    },

    showLoading(show) {
        const grid = document.getElementById('accountsGrid');
        if (!grid) return;

        if (show) {
            grid.setAttribute('aria-busy', 'true');
            grid.innerHTML = `
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
            `;
            return;
        }

        grid.setAttribute('aria-busy', 'false');
    }
};

Modules.Render = ContasRender;
