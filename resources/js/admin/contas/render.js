/**
 * ============================================================================
 * LUKRATO - Contas / Render
 * ============================================================================
 * Rendering helpers: portfolio summary, distribution, account cards and states.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules, escapeHtml } from './state.js';
import { refreshIcons } from '../shared/ui.js';

const RESERVE_TYPES = new Set(['conta_poupanca', 'conta_investimento']);

const TYPE_META = {
    conta_corrente: {
        label: 'Conta corrente',
        icon: 'landmark',
        color: '#3b82f6',
    },
    conta_poupanca: {
        label: 'Poupanca',
        icon: 'piggy-bank',
        color: '#10b981',
    },
    conta_investimento: {
        label: 'Reserva',
        icon: 'shield-check',
        color: '#0ea5a4',
    },
    carteira_digital: {
        label: 'Carteira digital',
        icon: 'smartphone',
        color: '#8b5cf6',
    },
    dinheiro: {
        label: 'Dinheiro',
        icon: 'wallet',
        color: '#f59e0b',
    },
};

function normalizeBalance(conta) {
    const value = Number(conta?.saldoAtual ?? conta?.saldoInicial ?? conta?.saldo_inicial ?? 0);
    return Math.abs(value) < 0.01 ? 0 : value;
}

function getContaType(conta) {
    return conta?.tipo_conta || conta?.tipo || 'conta_corrente';
}

function getInstitution(conta) {
    return conta?.instituicao_financeira || Utils.getInstituicao(conta?.instituicao_financeira_id);
}

function getTypeMeta(tipo) {
    return TYPE_META[tipo] || {
        label: Utils.formatTipoConta(tipo),
        icon: 'wallet',
        color: '#64748b',
    };
}

function formatPercent(value) {
    const safeValue = Number.isFinite(value) ? Math.max(value, 0) : 0;
    const decimals = safeValue >= 10 ? 0 : 1;
    return `${safeValue.toLocaleString('pt-BR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    })}%`;
}

function pluralizeContas(value) {
    return `${value} ${value === 1 ? 'conta' : 'contas'}`;
}

function buildTooltipAttrs(title, text) {
    return `data-lk-tooltip-title="${escapeHtml(title)}" data-lk-tooltip="${escapeHtml(text)}"`;
}

function getTypeTooltip(tipo) {
    const tooltips = {
        conta_corrente: 'Conta para entradas, pagamentos e movimentações do dia a dia.',
        conta_poupanca: 'Conta voltada para guardar dinheiro com liquidez simples.',
        conta_investimento: 'Conta separada para reserva, objetivos ou investimentos.',
        carteira_digital: 'Saldo mantido em carteira digital para movimentações rapidas.',
        dinheiro: 'Valor em especie acompanhado manualmente no painel.',
    };

    return tooltips[tipo] || 'Tipo usado para organizar como essa conta aparece no seu painel.';
}

function calculatePortfolio(contas = STATE.contas) {
    const normalized = (Array.isArray(contas) ? contas : []).map((conta) => {
        const saldo = normalizeBalance(conta);
        const tipo = getContaType(conta);
        const instituicao = getInstitution(conta);
        const meta = getTypeMeta(tipo);

        return {
            ...conta,
            saldoAtualNormalizado: saldo,
            tipoContaNormalizado: tipo,
            instituicaoNormalizada: instituicao,
            typeMeta: meta,
            positiveBalance: Math.max(saldo, 0),
            isReserve: RESERVE_TYPES.has(tipo),
        };
    });

    const totalBalance = normalized.reduce((sum, conta) => sum + conta.saldoAtualNormalizado, 0);
    const positiveAllocationTotal = normalized.reduce((sum, conta) => sum + conta.positiveBalance, 0);
    const reserveBalance = normalized
        .filter((conta) => conta.isReserve)
        .reduce((sum, conta) => sum + conta.positiveBalance, 0);

    const primaryAccount = normalized
        .slice()
        .sort((a, b) => b.saldoAtualNormalizado - a.saldoAtualNormalizado)[0] || null;

    const distribution = Object.values(normalized.reduce((acc, conta) => {
        if (conta.positiveBalance <= 0) return acc;

        const key = conta.tipoContaNormalizado;
        if (!acc[key]) {
            acc[key] = {
                tipo: key,
                label: conta.typeMeta.label,
                icon: conta.typeMeta.icon,
                color: conta.typeMeta.color,
                total: 0,
                count: 0,
            };
        }

        acc[key].total += conta.positiveBalance;
        acc[key].count += 1;
        return acc;
    }, {})).map((item) => ({
        ...item,
        percent: positiveAllocationTotal > 0 ? (item.total / positiveAllocationTotal) * 100 : 0,
    })).sort((a, b) => b.total - a.total);

    const primaryShare = primaryAccount && positiveAllocationTotal > 0 && primaryAccount.positiveBalance > 0
        ? (primaryAccount.positiveBalance / positiveAllocationTotal) * 100
        : 0;

    const reserveShare = positiveAllocationTotal > 0
        ? (reserveBalance / positiveAllocationTotal) * 100
        : 0;

    const topType = distribution[0] || null;

    return {
        contas: normalized,
        totalAccounts: normalized.length,
        totalBalance,
        positiveAllocationTotal,
        reserveBalance,
        reserveShare,
        primaryAccount,
        primaryShare,
        distribution,
        topType,
    };
}

function buildInsight(portfolio) {
    const {
        totalAccounts,
        totalBalance,
        reserveBalance,
        reserveShare,
        primaryAccount,
        primaryShare,
    } = portfolio;

    if (totalAccounts === 0) {
        return {
            title: 'Seu dinheiro aparece aqui assim que a primeira conta for criada.',
            description: 'Cadastre uma conta para ver onde o saldo esta concentrado e quanto você ja separou em reserva.',
        };
    }

    if (totalBalance <= 0) {
        return {
            title: 'Seu saldo total merece atencao.',
            description: 'Hoje o valor consolidado nao esta positivo. Vale revisar as contas com saldo menor e reforcar sua reserva.',
        };
    }

    if (!primaryAccount) {
        return {
            title: 'Seu dinheiro total ja esta consolidado.',
            description: 'Assim que houver saldo positivo, mostramos qual conta concentra a maior parte dele.',
        };
    }

    if (primaryShare >= 70 && totalAccounts > 1) {
        return {
            title: `Sua maior parte do dinheiro esta em ${primaryAccount.nome}.`,
            description: reserveBalance > 0
                ? 'Existe concentracao em uma unica conta. Considere distribuir melhor para reduzir dependencia.'
                : 'Existe concentracao em uma unica conta e ainda nao ha reserva separada. Vale distribuir melhor.',
        };
    }

    if (reserveBalance <= 0) {
        return {
            title: `Sua maior parte do dinheiro esta em ${primaryAccount.nome}.`,
            description: 'Quase todo o saldo esta em contas de uso diario. Considere separar parte do valor em reserva.',
        };
    }

    if (reserveShare >= 35) {
        return {
            title: `${Utils.formatCurrency(reserveBalance)} ja estao guardados.`,
            description: `A reserva representa ${formatPercent(reserveShare)} do seu dinheiro total e melhora sua seguranca financeira.`,
        };
    }

    return {
        title: `Sua maior parte do dinheiro esta em ${primaryAccount.nome}.`,
        description: `${formatPercent(primaryShare)} do valor positivo esta nessa conta. Sua distribuicao esta equilibrada, mas ainda pode ficar melhor.`,
    };
}

export const ContasRender = {
    getFilteredContas() {
        const query = String(STATE.searchQuery || '').trim().toLowerCase();
        const typeFilter = STATE.typeFilter || 'all';

        return STATE.contas.filter((conta) => {
            const tipoConta = getContaType(conta);
            if (typeFilter !== 'all' && tipoConta !== typeFilter) {
                return false;
            }

            if (!query) return true;

            const instituicao = getInstitution(conta);
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

    sortContasForDisplay(contas, portfolio) {
        const primaryId = portfolio.primaryAccount?.id ?? null;

        return contas.slice().sort((a, b) => {
            if (a.id === primaryId && b.id !== primaryId) return -1;
            if (b.id === primaryId && a.id !== primaryId) return 1;

            const balanceDiff = normalizeBalance(b) - normalizeBalance(a);
            if (Math.abs(balanceDiff) > 0.009) return balanceDiff;

            return String(a.nome || '').localeCompare(String(b.nome || ''), 'pt-BR');
        });
    },

    updatePageContext(contasVisiveis = ContasRender.getFilteredContas()) {
        const titleEl = document.getElementById('contasListTitle');
        const descriptionEl = document.getElementById('contasListDescription');
        const query = String(STATE.searchQuery || '').trim();
        const typeFilter = STATE.typeFilter || 'all';

        if (titleEl) {
            titleEl.textContent = query || typeFilter !== 'all'
                ? `${contasVisiveis.length} conta(s) na visualização atual`
                : 'Suas contas ativas';
        }

        if (descriptionEl) {
            if (STATE.lastLoadError && STATE.contas.length === 0) {
                descriptionEl.textContent = STATE.lastLoadError;
            } else if (query || typeFilter !== 'all') {
                descriptionEl.textContent = 'Busca e filtros afetam apenas a lista abaixo. O resumo no topo continua considerando todas as contas ativas.';
            } else {
                descriptionEl.textContent = 'A conta com maior saldo aparece primeiro, seguida pelas demais em ordem de saldo.';
            }
        }
    },

    updateFilterSummary(contasVisiveis = ContasRender.getFilteredContas()) {
        const summaryEl = document.getElementById('contasFilterSummary');
        if (!summaryEl) return;

        const query = String(STATE.searchQuery || '').trim();
        const typeFilter = STATE.typeFilter || 'all';

        if (STATE.lastLoadError && STATE.contas.length === 0) {
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
                    <span>Use busca e filtro para localizar contas rapidamente sem perder a visao consolidada do topo.</span>
                </div>
            `;
            return;
        }

        summaryEl.innerHTML = `
            <div class="contas-filter-summary-text">
                <i data-lucide="filter"></i>
                <span>${contasVisiveis.length} conta(s) encontradas.${query ? ` Busca por "${escapeHtml(query)}".` : ''}${typeFilter !== 'all' ? ` Tipo ${escapeHtml(Utils.formatTipoConta(typeFilter))}.` : ''}</span>
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
                <p>Cadastre sua primeira conta para enxergar onde o dinheiro esta e quanto você ja separou em reserva.</p>
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
                <p>Ajuste a busca ou o tipo selecionado para voltar a ver as contas da sua carteira.</p>
                <button class="btn btn-light" data-action="clear-contas-filters">
                    <i data-lucide="x"></i> Limpar filtros
                </button>
            </div>
        `;
    },

    renderDistribution(portfolio) {
        const listEl = document.getElementById('contasDistributionList');
        const summaryEl = document.getElementById('contasDistributionSummary');

        if (!listEl || !summaryEl) return;

        if (!portfolio.distribution.length) {
            summaryEl.textContent = 'Quando houver saldo positivo nas contas, mostramos a distribuicao por tipo aqui.';
            listEl.innerHTML = `
                <div class="contas-distribution-empty">
                    <i data-lucide="wallet"></i>
                    <span>Sem saldo positivo para distribuir no momento.</span>
                </div>
            `;
            return;
        }

        const topType = portfolio.distribution[0];
        summaryEl.textContent = `${topType.label} concentra ${formatPercent(topType.percent)} do seu dinheiro positivo hoje.`;

        listEl.innerHTML = portfolio.distribution.map((item) => `
            <article class="contas-distribution-item" style="--distribution-color:${item.color};">
                <div class="contas-distribution-item-head">
                    <div class="contas-distribution-item-label">
                        <span class="contas-distribution-item-icon">
                            <i data-lucide="${item.icon}"></i>
                        </span>
                        <div>
                            <strong>${escapeHtml(item.label)}</strong>
                            <span>${pluralizeContas(item.count)}</span>
                        </div>
                    </div>
                    <div class="contas-distribution-item-values">
                        <strong>${Utils.formatCurrency(item.total)}</strong>
                        <span>${formatPercent(item.percent)}</span>
                    </div>
                </div>
                <div class="contas-distribution-bar">
                    <span style="width:${Math.max(item.percent, 4)}%;"></span>
                </div>
            </article>
        `).join('');
    },

    renderContas() {
        const container = document.getElementById('accountsGrid');
        if (!container) {
            console.error('accountsGrid nao encontrado.');
            return;
        }

        const portfolio = calculatePortfolio(STATE.contas);
        const contasVisiveis = ContasRender.sortContasForDisplay(
            ContasRender.getFilteredContas(),
            portfolio
        );

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

        container.innerHTML = contasVisiveis
            .map((conta) => ContasRender.createContaCard(conta, portfolio))
            .join('');

        ContasRender.updatePageContext(contasVisiveis);
        ContasRender.updateFilterSummary(contasVisiveis);
        refreshIcons();
        Modules.Events?.attachContaCardListeners?.();
    },

    createContaCard(conta, portfolio = calculatePortfolio(STATE.contas)) {
        const instituicao = getInstitution(conta);
        const logoUrl = instituicao?.logo_url || `${CONFIG.BASE_URL}assets/img/banks/default.svg`;
        const balance = normalizeBalance(conta);
        const type = getContaType(conta);
        const typeMeta = conta?.typeMeta || getTypeMeta(type);
        const typeLabel = Utils.formatTipoConta(type);
        const typeClass = Utils.getTipoContaClass(type);
        const accentColor = instituicao?.cor_primaria || typeMeta.color || '#667eea';
        const positiveAllocationTotal = portfolio.positiveAllocationTotal;
        const share = balance > 0 && positiveAllocationTotal > 0
            ? (balance / positiveAllocationTotal) * 100
            : 0;
        const shareLabel = balance > 0 ? formatPercent(share) : '0%';
        const shareText = balance > 0
            ? (portfolio.primaryAccount?.id === conta.id
                ? `Conta principal com ${shareLabel} do saldo positivo`
                : `${shareLabel} do saldo positivo`)
            : balance < 0
                ? 'Saldo abaixo de zero no momento'
                : 'Sem participacao no saldo positivo';
        const progressWidth = balance > 0 ? Math.max(Math.min(share, 100), 6) : 0;
        const isFeatured = portfolio.primaryAccount?.id === conta.id && balance >= 0;
        const isReserve = RESERVE_TYPES.has(type);
        const balanceClass = balance >= 0 ? 'positive' : 'negative';
        const progressContext = isFeatured
            ? 'Conta principal'
            : isReserve
                ? 'Saldo guardado'
                : typeLabel;
        const featuredBadge = isFeatured
            ? `<span class="account-chip account-chip--featured" ${buildTooltipAttrs('Conta principal', 'Hoje esta e a conta com maior saldo entre as contas ativas.')}>
                    <i data-lucide="sparkles"></i>
                    Conta principal
               </span>`
            : '';
        const reserveBadge = isReserve
            ? `<span class="account-chip account-chip--reserve" ${buildTooltipAttrs('Saldo guardado', 'Essa conta esta marcada como reserva para dinheiro separado do uso do dia a dia.')}>
                    <i data-lucide="piggy-bank"></i>
                    Saldo guardado
               </span>`
            : '';
        const demoBadge = conta?.is_demo
            ? `<span class="account-chip account-chip--featured" ${buildTooltipAttrs('Conta de exemplo', 'Esta conta existe apenas para demonstrar como a tela funciona.')}>
                    <i data-lucide="flask-conical"></i>
                    Exemplo
               </span>`
            : '';
        const menuMarkup = conta?.is_demo
            ? `<span class="account-chip account-chip--reserve" ${buildTooltipAttrs('Somente visualizacao', 'Itens de exemplo nao podem ser editados nem arquivados.')}>
                    <i data-lucide="eye"></i>
                    Somente visualizacao
               </span>`
            : `
                <button
                    type="button"
                    class="btn-icon btn-icon--soft"
                    onclick="contasManager.moreConta(${conta.id}, event)"
                    aria-label="Abrir acoes da conta"
                    ${buildTooltipAttrs('Acoes da conta', 'Abra o menu para editar ou arquivar esta conta.')}>
                    <i data-lucide="more-horizontal"></i>
                </button>
            `;

        return `
            <article class="account-card surface-card ${isFeatured ? 'is-featured' : ''}" data-account-id="${conta.id}" style="--account-accent:${accentColor};">
                <div class="account-media">
                    <div class="account-logo">
                        <img src="${logoUrl}" alt="${escapeHtml(conta.nome)}" />
                    </div>
                </div>

                <div class="account-header">
                    <div class="account-card-badges">
                        <span class="account-type-badge ${typeClass}" ${buildTooltipAttrs(typeLabel, getTypeTooltip(type))}>
                            <i data-lucide="${typeMeta.icon}"></i>
                            ${escapeHtml(typeLabel)}
                        </span>
                        ${featuredBadge}
                        ${reserveBadge}
                        ${demoBadge}
                    </div>
                </div>

                <div class="account-content">
                    <h3 class="account-name">${escapeHtml(conta.nome)}</h3>
                    <p class="account-institution">${escapeHtml(instituicao?.nome || 'Instituicao nao definida')}</p>
                </div>

                <div class="account-balance-panel">
                    <span class="account-balance-caption">${balance >= 0 ? 'Saldo disponivel' : 'Saldo atual'}</span>
                    <strong class="account-balance ${balanceClass}">${Utils.formatCurrency(balance)}</strong>
                </div>

                <div class="account-menu">
                    ${menuMarkup}
                </div>

                <div class="account-progress">
                    <div class="account-progress-head">
                        <span>${shareText}</span>
                        <span>${escapeHtml(progressContext)}</span>
                    </div>
                    <div class="account-progress-bar">
                        <span style="width:${progressWidth}%;"></span>
                    </div>
                </div>
            </article>
        `;
    },

    renderCartoesBadge() {
        return '';
    },

    renderInstituicoesSelect() {
        const select = document.getElementById('instituicaoFinanceiraSelect');
        if (!select) return;

        const grupos = Utils.groupByTipo(STATE.instituicoes);
        select.innerHTML = '<option value="">Selecione uma instituicao</option>';

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
        const portfolio = calculatePortfolio(STATE.contas);
        const insight = buildInsight(portfolio);
        const totalContasEl = document.getElementById('totalContas');
        const saldoTotalEl = document.getElementById('saldoTotal');
        const saldoReservasEl = document.getElementById('saldoReservas');
        const contextTitleEl = document.getElementById('contasContextTitle');
        const contextDescriptionEl = document.getElementById('contasContextDescription');
        const mainAccountNameEl = document.getElementById('contasMainAccountName');
        const mainAccountValueEl = document.getElementById('contasMainAccountValue');
        const mainAccountShareEl = document.getElementById('contasMainAccountShare');
        const reserveLabelEl = document.getElementById('contasReserveLabel');
        const reserveShareEl = document.getElementById('contasReserveShare');

        if (totalContasEl) totalContasEl.textContent = pluralizeContas(portfolio.totalAccounts);
        if (saldoTotalEl) saldoTotalEl.textContent = Utils.formatCurrency(portfolio.totalBalance);
        if (saldoReservasEl) saldoReservasEl.textContent = Utils.formatCurrency(portfolio.reserveBalance);
        if (contextTitleEl) contextTitleEl.textContent = insight.title;
        if (contextDescriptionEl) contextDescriptionEl.textContent = insight.description;

        if (mainAccountNameEl) {
            mainAccountNameEl.textContent = portfolio.primaryAccount?.nome || 'Nenhuma conta';
        }

        if (mainAccountValueEl) {
            mainAccountValueEl.textContent = portfolio.primaryAccount
                ? Utils.formatCurrency(portfolio.primaryAccount.saldoAtualNormalizado)
                : 'R$ 0,00';
        }

        if (mainAccountShareEl) {
            mainAccountShareEl.textContent = portfolio.primaryAccount && portfolio.primaryShare > 0
                ? `${formatPercent(portfolio.primaryShare)} do saldo positivo esta concentrado aqui`
                : 'Sem concentracao relevante no momento';
        }

        if (reserveLabelEl) {
            reserveLabelEl.textContent = `${Utils.formatCurrency(portfolio.reserveBalance)} guardados`;
        }

        if (reserveShareEl) {
            reserveShareEl.textContent = portfolio.reserveShare > 0
                ? `${formatPercent(portfolio.reserveShare)} do saldo positivo esta protegido em reserva`
                : 'Nenhum valor guardado em reserva';
        }

        ContasRender.renderDistribution(portfolio);
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
    },
};

Modules.Render = ContasRender;
