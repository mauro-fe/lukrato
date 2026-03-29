/**
 * Metas – Main application logic
 * Data loading, rendering, CRUD, aportes, templates
 */

import { CONFIG, STATE, Utils, getCategoryIconColor } from './state.js';
import {
    apiDelete as sharedApiDelete,
    apiGet as sharedApiGet,
    apiPost as sharedApiPost,
    apiPut as sharedApiPut,
    getErrorMessage,
} from '../shared/api.js';

// ── API Helpers ────────────────────────────────────────────────

async function apiGet(endpoint) { return sharedApiGet(endpoint); }
async function apiPost(endpoint, data) { return sharedApiPost(endpoint, data); }
async function apiPut(endpoint, data) { return sharedApiPut(endpoint, data); }
async function apiDelete(endpoint) { return sharedApiDelete(endpoint); }
function requestErrorMessage(error, fallback) { return getErrorMessage(error, fallback); }

// ── Plan limit error handler ───────────────────────────────────

function handleLimitError(res) {
    const isError = res.success === false;
    if (!isError) return false;

    const msg = res.message || '';
    const isLimitError = /limite|plano gratuito|upgrade|faça upgrade/i.test(msg);

    if (isLimitError) {
        Swal.fire({
            icon: 'warning',
            title: '🚀 Limite Atingido',
            html: `
                <p>${msg}</p>
                <p style="margin-top: 12px; color: #6c757d; font-size: 0.9em;">
                    Desbloqueie metas ilimitadas com o plano Pro!
                </p>
            `,
            showCancelButton: true,
            confirmButtonText: '✨ Ver Plano Pro',
            cancelButtonText: 'Depois',
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) goToBilling();
        });
        return true;
    }
    return false;
}

function goToBilling() {
    if (typeof openBillingModal === 'function') openBillingModal();
    else window.location.href = `${CONFIG.BASE_URL}billing`;
}

function applyPreviewMeta(meta) {
    STATE.previewMeta = meta?.is_demo ? meta : null;

    if (STATE.previewMeta) {
        window.LKDemoPreviewBanner?.show(STATE.previewMeta);
        return;
    }

    window.LKDemoPreviewBanner?.hide();
}

function getCollectionPayload(data, key) {
    if (Array.isArray(data)) {
        return data;
    }

    return Array.isArray(data?.[key]) ? data[key] : [];
}

function isDemoItem(item) {
    return item?.is_demo === true;
}

function preventDemoAction(item) {
    if (!isDemoItem(item)) return false;

    Utils.showToast('Esse item e apenas um exemplo. Crie um registro real para editar ou excluir.', 'info');
    return true;
}

// ── Gamification helper ────────────────────────────────────────

function processGamification(gamification) {
    if (!gamification) return;
    const achievements = gamification.achievements || [];
    if (achievements.length > 0 && typeof window.notifyMultipleAchievements === 'function') {
        window.notifyMultipleAchievements(achievements);
    }
}

// ── Initialization ─────────────────────────────────────────────

export const MetasApp = {
    async init() {
        MetasApp.attachEventListeners();
        MetasApp.setupMoneyInputs();
        await MetasApp.loadContas();
        await MetasApp.loadAll();
    },

    // ==================== EVENT LISTENERS ====================

    attachEventListeners() {
        // Metas buttons
        document.getElementById('btnNovaMeta')?.addEventListener('click', () => MetasApp.openMetaModal());
        document.getElementById('btnNovaMetaHeader')?.addEventListener('click', () => MetasApp.openMetaModal());
        document.getElementById('btnTemplates')?.addEventListener('click', () => MetasApp.openTemplates());
        document.getElementById('btnTemplatesEmpty')?.addEventListener('click', () => MetasApp.openTemplates());
        document.getElementById('btnNovaMetaEmpty')?.addEventListener('click', () => MetasApp.openMetaModal());
        document.getElementById('formMeta')?.addEventListener('submit', (e) => MetasApp.handleMetaSubmit(e));
        document.getElementById('formAporte')?.addEventListener('submit', (e) => MetasApp.handleAporteSubmit(e));
        document.getElementById('metSearchInput')?.addEventListener('input', (e) => {
            STATE.ui.query = e.target.value || '';
            MetasApp.renderMetas();
        });
        document.getElementById('metSortSelect')?.addEventListener('change', (e) => {
            STATE.ui.sort = e.target.value || 'deadline';
            MetasApp.renderMetas();
        });
        document.querySelectorAll('#metFilterChips [data-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                STATE.ui.filter = button.dataset.filter || 'all';
                document.querySelectorAll('#metFilterChips [data-filter]').forEach((chip) => {
                    chip.classList.toggle('is-active', chip === button);
                });
                MetasApp.renderMetas();
            });
        });

        // Color picker
        document.querySelectorAll('#metaCorPicker .color-dot').forEach(dot => {
            dot.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => d.classList.remove('active'));
                dot.classList.add('active');
                document.getElementById('metaCor').value = dot.dataset.color;
            });
        });

        // Modal overlays
        document.querySelectorAll('.fin-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) MetasApp.closeModal(overlay.id);
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => MetasApp.closeModal(btn.dataset.closeModal));
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.fin-modal-overlay.active').forEach(m => MetasApp.closeModal(m.id));
            }
        });

        // Meta deadline change → show suggested monthly contribution
        document.getElementById('metaPrazo')?.addEventListener('change', () => MetasApp.updateAporteSugerido());
        document.getElementById('metaValorAlvo')?.addEventListener('input', () => MetasApp.updateAporteSugerido());
        document.getElementById('metaValorAtual')?.addEventListener('input', () => MetasApp.updateAporteSugerido());

        // Conta vinculada → toggle valor_atual field
        document.getElementById('metaContaId')?.addEventListener('change', () => MetasApp.onMetaContaChange());
    },

    setupMoneyInputs() {
        const moneyFields = ['metaValorAlvo', 'metaValorAtual', 'aporteValor'];
        moneyFields.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', () => Utils.formatarDinheiro(input));
                input.addEventListener('focus', () => { if (!input.value) input.value = '0,00'; });
            }
        });
    },

    // ==================== DATA LOADING ====================

    async loadAll() {
        try {
            await Promise.all([
                MetasApp.loadResumo(),
                MetasApp.loadMetas(),
            ]);
            MetasApp.renderFocusPanel();
            MetasApp.renderInsights();
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    },

    async loadContas() {
        try {
            const res = await apiGet('api/contas?only_active=1&with_balances=1');
            if (Array.isArray(res)) {
                STATE.contas = res;
            } else if (res.success !== false && Array.isArray(res.data)) {
                STATE.contas = res.data;
            }
        } catch (e) {
            console.error('Erro ao carregar contas:', e);
        }
    },

    onMetaContaChange() {
        const contaId = document.getElementById('metaContaId')?.value;
        const valorAtualGroup = document.getElementById('metaValorAtual')?.closest('.fin-form-group');
        const hint = document.getElementById('metaContaHint');
        if (contaId) {
            if (valorAtualGroup) valorAtualGroup.style.display = 'none';
            const conta = STATE.contas.find(c => String(c.id) === String(contaId));
            const saldo = conta?.saldoAtual ?? 0;
            if (hint) {
                hint.innerHTML = `<i data-lucide="info"></i> Saldo atual da conta: <strong>${Utils.formatCurrency(saldo)}</strong> — será usado como valor inicial. O progresso atualiza automaticamente.`;
                hint.style.display = '';
                if (window.lucide) lucide.createIcons();
            }
        } else {
            if (valorAtualGroup) valorAtualGroup.style.display = '';
            if (hint) {
                hint.style.display = 'none';
                hint.innerHTML = '';
            }
        }
    },

    async loadResumo() {
        try {
            const res = await apiGet('api/financas/resumo');
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                STATE.resumo = res.data;
                MetasApp.renderResumo(res.data);
            }
        } catch (e) {
            console.error('Erro ao carregar resumo:', e);
        }
    },

    async loadMetas() {
        try {
            const res = await apiGet('api/financas/metas');
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                STATE.metas = getCollectionPayload(res.data, 'metas');
                MetasApp.renderFocusPanel();
                MetasApp.renderMetas();
                MetasApp.renderInsights();
            }
        } catch (e) {
            console.error('Erro ao carregar metas:', e);
        }
    },

    // ==================== RENDER: RESUMO ====================

    renderResumo(data) {
        if (!data) return;

        const met = data.metas || {};
        const totalMetas = met.total_metas ?? met.ativas ?? 0;
        const totalAtual = met.total_atual ?? 0;
        const totalAlvo = met.total_alvo ?? 0;
        const progressoGeral = met.progresso_geral ?? 0;
        const atrasadas = met.atrasadas ?? 0;

        Utils.setText('metasAtivas', `${totalMetas} ativa${totalMetas !== 1 ? 's' : ''}`);
        Utils.setText('metasTotalAtual', Utils.formatCurrency(totalAtual));
        Utils.setText('metasTotalAlvo', Utils.formatCurrency(totalAlvo));

        const ringFill = document.getElementById('metasProgressRingFill');
        const scoreEl = document.getElementById('metasProgressScore');
        const labelEl = document.getElementById('metasProgressLabel');

        if (ringFill) {
            ringFill.style.strokeDasharray = `${progressoGeral}, 100`;
            ringFill.classList.remove('met-ring-fill--good', 'met-ring-fill--warn', 'met-ring-fill--bad');
            if (atrasadas > 0 || progressoGeral < 30) ringFill.classList.add('met-ring-fill--bad');
            else if (progressoGeral < 70) ringFill.classList.add('met-ring-fill--warn');
            else ringFill.classList.add('met-ring-fill--good');
        }
        if (scoreEl) scoreEl.textContent = `${Math.round(progressoGeral)}%`;
        if (labelEl) {
            labelEl.className = 'met-summary-card__status';
            if (atrasadas > 0) {
                labelEl.textContent = `${atrasadas} atrasada${atrasadas > 1 ? 's' : ''}`;
                labelEl.classList.add('met-status--bad');
            } else if (totalMetas === 0) {
                labelEl.textContent = 'Nenhuma meta';
            } else if (progressoGeral >= 80) {
                labelEl.textContent = 'Quase la';
                labelEl.classList.add('met-status--good');
            } else {
                labelEl.textContent = 'Em progresso';
                labelEl.classList.add('met-status--good');
            }
        }
    },

    getFilteredMetas() {
        const query = STATE.ui.query.trim().toLowerCase();

        return STATE.metas.filter((meta) => {
            if (query && !(meta.titulo || '').toLowerCase().includes(query)) {
                return false;
            }

            switch (STATE.ui.filter) {
                case 'ativa':
                    return meta.status === 'ativa';
                case 'atrasada':
                    return meta.is_atrasada === true || ((meta.dias_restantes ?? 1) < 0 && meta.status === 'ativa');
                case 'concluida':
                    return meta.status === 'concluida';
                default:
                    return true;
            }
        }).sort((a, b) => MetasApp.compareMetas(a, b, STATE.ui.sort));
    },

    compareMetas(a, b, sort) {
        const priorityWeight = { alta: 0, media: 1, baixa: 2 };

        switch (sort) {
            case 'progress':
                return (b.progresso || 0) - (a.progresso || 0);
            case 'remaining':
                return (b.valor_restante || 0) - (a.valor_restante || 0);
            case 'priority':
                return (priorityWeight[a.prioridade] ?? 99) - (priorityWeight[b.prioridade] ?? 99);
            case 'title':
                return (a.titulo || '').localeCompare(b.titulo || '', 'pt-BR');
            case 'deadline':
            default: {
                const aDeadline = a.dias_restantes ?? Number.POSITIVE_INFINITY;
                const bDeadline = b.dias_restantes ?? Number.POSITIVE_INFINITY;
                if (aDeadline !== bDeadline) return aDeadline - bDeadline;
                return (b.progresso || 0) - (a.progresso || 0);
            }
        }
    },

    renderFocusPanel() {
        const focus = document.getElementById('metFocusContent');
        const stats = document.getElementById('metFocusStats');
        if (!focus || !stats) return;

        const activeMetas = STATE.metas.filter((meta) => meta.status === 'ativa');
        const overdueMetas = activeMetas.filter((meta) => meta.is_atrasada === true || (meta.dias_restantes ?? 1) < 0);
        const completedMetas = STATE.metas.filter((meta) => meta.status === 'concluida');
        const recommendedMonthly = activeMetas.reduce((sum, meta) => sum + (meta.aporte_mensal_sugerido || 0), 0);
        const nextMeta = [...activeMetas].sort((a, b) => {
            const progressDiff = (b.progresso || 0) - (a.progresso || 0);
            if (progressDiff !== 0) return progressDiff;
            return (a.valor_restante || 0) - (b.valor_restante || 0);
        })[0];

        stats.innerHTML = `
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Em risco</span>
                <strong class="met-focus-stat__value">${overdueMetas.length}</strong>
            </div>
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Aporte sugerido</span>
                <strong class="met-focus-stat__value">${recommendedMonthly > 0 ? Utils.formatCurrency(recommendedMonthly) : 'Sem prazo'}</strong>
            </div>
            <div class="met-focus-stat">
                <span class="met-focus-stat__label">Concluidas</span>
                <strong class="met-focus-stat__value">${completedMetas.length}</strong>
            </div>
        `;

        if (!nextMeta) {
            focus.innerHTML = `
                <div class="met-focus-callout">
                    <div>
                        <h2 class="met-focus-callout__title">Voce ainda nao tem uma meta ativa.</h2>
                        <p class="met-focus-callout__text">Use um template para sair do zero mais rapido ou crie uma meta com valor e prazo.</p>
                    </div>
                    <div class="met-focus-callout__actions">
                        <button type="button" class="met-action-btn met-action-btn--success" onclick="metasManager.openMetaModal()">
                            <i data-lucide="plus"></i>
                            <span>Criar Meta</span>
                        </button>
                        <button type="button" class="met-action-btn" onclick="metasManager.openTemplates()">
                            <i data-lucide="wand-sparkles"></i>
                            <span>Usar Template</span>
                        </button>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        const monthlyHint = nextMeta.aporte_mensal_sugerido > 0
            ? `${Utils.formatCurrency(nextMeta.aporte_mensal_sugerido)}/mes sugeridos`
            : 'Sem prazo definido para calculo de aporte';
        const deadlineHint = nextMeta.dias_restantes == null
            ? 'Sem prazo definido.'
            : (nextMeta.dias_restantes < 0
                ? 'Prazo vencido.'
                : `${nextMeta.dias_restantes} dia${nextMeta.dias_restantes === 1 ? '' : 's'} restantes.`);
        const primaryAction = nextMeta.status === 'ativa' && !nextMeta.conta_id && !isDemoItem(nextMeta)
            ? `<button type="button" class="met-action-btn met-action-btn--success" onclick="metasManager.openAporteModal(${nextMeta.id})">
                    <i data-lucide="circle-plus"></i>
                    <span>Registrar aporte</span>
               </button>`
            : `<button type="button" class="met-action-btn met-action-btn--success" onclick="metasManager.openMetaModal(${nextMeta.id})">
                    <i data-lucide="pencil"></i>
                    <span>Revisar meta</span>
               </button>`;

        focus.innerHTML = `
            <div class="met-focus-callout">
                <div>
                    <h2 class="met-focus-callout__title">${Utils.escHtml(nextMeta.titulo)}</h2>
                    <p class="met-focus-callout__text">
                        Faltam <strong>${Utils.formatCurrency(nextMeta.valor_restante || Math.max(0, (nextMeta.valor_alvo || 0) - (nextMeta.valor_atual || 0)))}</strong>
                        para concluir. ${deadlineHint}
                    </p>
                    <div class="met-focus-callout__meta">
                        <span class="met-focus-callout__pill">${(nextMeta.progresso || 0).toFixed(1)}% concluido</span>
                        <span class="met-focus-callout__pill">${monthlyHint}</span>
                    </div>
                </div>
                <div class="met-focus-callout__actions">
                    ${primaryAction}
                    <button type="button" class="met-action-btn" onclick="metasManager.openMetaModal(${nextMeta.id})">
                        <i data-lucide="sliders-horizontal"></i>
                        <span>Ajustar meta</span>
                    </button>
                </div>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    },

    buildInsights() {
        const activeMetas = STATE.metas.filter((meta) => meta.status === 'ativa');
        const completedMetas = STATE.metas.filter((meta) => meta.status === 'concluida');
        const overdueMetas = activeMetas.filter((meta) => meta.is_atrasada === true || (meta.dias_restantes ?? 1) < 0);
        const highestPriority = [...activeMetas]
            .filter((meta) => meta.prioridade === 'alta')
            .sort((a, b) => (a.progresso || 0) - (b.progresso || 0))[0];
        const closestMeta = [...activeMetas]
            .sort((a, b) => (a.valor_restante || 0) - (b.valor_restante || 0))[0];
        const monthlyRequired = activeMetas.reduce((sum, meta) => sum + (meta.aporte_mensal_sugerido || 0), 0);
        const insights = [];

        if (monthlyRequired > 0) {
            insights.push({
                tipo: 'info',
                titulo: 'Ritmo mensal das metas',
                mensagem: `Para cumprir os prazos atuais, reserve cerca de ${Utils.formatCurrency(monthlyRequired)} por mes.`,
                icon: 'calendar-range',
            });
        }

        if (overdueMetas.length > 0) {
            const overdue = overdueMetas[0];
            insights.push({
                tipo: 'danger',
                titulo: `${overdue.titulo} esta atrasada`,
                mensagem: overdue.aporte_mensal_sugerido > 0
                    ? `Para recuperar o prazo, tente reforcar em ${Utils.formatCurrency(overdue.aporte_mensal_sugerido)} por mes.`
                    : `Faltam ${Utils.formatCurrency(overdue.valor_restante || 0)} para concluir esta meta.`,
                icon: 'triangle-alert',
                action: overdue.conta_id ? 'review' : 'deposit',
                metaId: overdue.id,
            });
        }

        if (closestMeta) {
            insights.push({
                tipo: 'success',
                titulo: `${closestMeta.titulo} esta mais perto de sair do papel`,
                mensagem: `Faltam ${Utils.formatCurrency(closestMeta.valor_restante || 0)} para concluir.`,
                icon: 'target',
                action: closestMeta.conta_id ? 'review' : 'deposit',
                metaId: closestMeta.id,
            });
        }

        if (highestPriority) {
            insights.push({
                tipo: 'warning',
                titulo: `Sua meta de alta prioridade pede atencao`,
                mensagem: `${highestPriority.titulo} ainda esta em ${(highestPriority.progresso || 0).toFixed(1)}% de progresso.`,
                icon: 'flag',
                action: 'review',
                metaId: highestPriority.id,
            });
        }

        if (completedMetas.length > 0) {
            insights.push({
                tipo: 'success',
                titulo: `Voce ja concluiu ${completedMetas.length} meta${completedMetas.length > 1 ? 's' : ''}`,
                mensagem: 'Vale usar esse embalo para abrir o proximo objetivo e manter a consistencia.',
                icon: 'party-popper',
            });
        }

        return insights.slice(0, 5);
    },

    renderInsights() {
        const section = document.getElementById('metInsightsSection');
        const grid = document.getElementById('metInsightsGrid');
        if (!section || !grid) return;

        const insights = MetasApp.buildInsights();
        if (!insights.length) {
            section.style.display = 'none';
            return;
        }

        section.style.display = '';
        grid.innerHTML = insights.map((insight) => {
            const action = insight.metaId
                ? (insight.action === 'deposit'
                    ? `<button type="button" class="met-insight-action" onclick="metasManager.openAporteModal(${insight.metaId})">
                            <i data-lucide="circle-plus"></i>
                            <span>Aportar</span>
                       </button>`
                    : `<button type="button" class="met-insight-action" onclick="metasManager.openMetaModal(${insight.metaId})">
                            <i data-lucide="pencil"></i>
                            <span>Revisar</span>
                       </button>`)
                : '';
            return `
                <div class="met-insight-card surface-card surface-card--interactive ${insight.tipo}">
                    <div class="met-insight-icon ${insight.tipo}">
                        <i data-lucide="${insight.icon}"></i>
                    </div>
                    <div class="met-insight-content">
                        <span class="met-insight-title">${Utils.escHtml(insight.titulo)}</span>
                        <p class="met-insight-text">${Utils.escHtml(insight.mensagem)}</p>
                    </div>
                    ${action}
                </div>
            `;
        }).join('');
        if (window.lucide) lucide.createIcons();
    },

    // ==================== RENDER: METAS ====================

    renderMetas() {
        const grid = document.getElementById('metasGrid');
        const empty = document.getElementById('metasEmpty');
        if (!grid || !empty) return;

        if (!STATE.metas.length) {
            grid.style.display = 'none';
            empty.style.display = 'flex';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';
        const filteredMetas = MetasApp.getFilteredMetas();

        if (!filteredMetas.length) {
            grid.innerHTML = `
                <div class="met-soft-empty surface-card">
                    <i data-lucide="search-x"></i>
                    <p>Nenhuma meta encontrada para os filtros atuais.</p>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        grid.innerHTML = filteredMetas.map(meta => {
            const progresso = meta.progresso || 0;
            const isDemo = isDemoItem(meta);
            const cor = meta.cor || '#8b5cf6';
            const tipoEmoji = Utils.getTipoEmoji(meta.tipo);
            const prioridadeTag = Utils.getPrioridadeTag(meta.prioridade);
            const isCompleted = meta.status === 'concluida';
            const statusTag = meta.status !== 'ativa'
                ? `<span class="met-card__badge met-card__badge--${meta.status === 'concluida' ? 'completed' : 'active'}">${Utils.capitalize(meta.status)}</span>`
                : `<span class="met-card__badge met-card__badge--active">Ativa</span>`;
            const demoTag = isDemo ? '<span class="met-card__badge met-card__badge--active">Exemplo</span>' : '';
            const diasRestantes = meta.dias_restantes;
            const prazoInfo = diasRestantes !== null && diasRestantes !== undefined
                ? (diasRestantes > 0
                    ? `<span class="met-card__deadline">${diasRestantes} dias restantes</span>`
                    : `<span class="met-card__deadline" style="color:#ef4444">Prazo vencido!</span>`)
                : '';
            const aporteInfo = meta.aporte_mensal_sugerido > 0
                ? `<span class="met-card__hint"><i data-lucide="calendar-range" style="width:12px;height:12px"></i> ${Utils.formatCurrency(meta.aporte_mensal_sugerido)}/mes sugeridos</span>`
                : '';
            const contaBadge = meta.conta_id
                ? `<span class="met-card__hint"><i data-lucide="landmark" style="width:12px;height:12px"></i> ${Utils.escHtml(meta.conta_nome || 'Conta vinculada')} • saldo sincronizado</span>`
                : '';

            return `
            <div class="met-card surface-card surface-card--interactive ${isCompleted ? 'met-card--completed' : ''}" style="--met-card-color: ${cor}" data-aos="fade-up">
                <div class="met-card__header">
                    <div class="met-card__title-group">
                        <span class="met-card__icon">${tipoEmoji}</span>
                        <div class="met-card__info">
                            <span class="met-card__name">${Utils.escHtml(meta.titulo)}</span>
                            ${prazoInfo}
                            ${aporteInfo}
                            ${contaBadge}
                            ${demoTag}
                        </div>
                    </div>
                    <div class="met-card__actions">
                        ${!isDemo && meta.status === 'ativa' && !meta.conta_id ? `
                        <button class="met-card__action-btn met-card__action-btn--deposit" onclick="metasManager.openAporteModal(${meta.id})" title="Adicionar aporte">
                            <i data-lucide="circle-plus"></i>
                        </button>` : ''}
                        ${!isDemo ? `<button class="met-card__action-btn" onclick="metasManager.openMetaModal(${meta.id})" title="Editar">
                            <i data-lucide="pencil"></i>
                        </button>
                        <button class="met-card__action-btn met-card__action-btn--danger" onclick="metasManager.deleteMeta(${meta.id})" title="Excluir">
                            <i data-lucide="trash-2"></i>
                        </button>` : ''}
                    </div>
                </div>
                <div class="met-card__progress">
                    <div class="met-card__progress-bar">
                        <div class="met-card__progress-fill" style="width: ${Math.min(progresso, 100)}%"></div>
                    </div>
                    <div class="met-card__progress-info">
                        <span class="met-card__current">${Utils.formatCurrency(meta.valor_atual || 0)}</span>
                        <span class="met-card__target">de ${Utils.formatCurrency(meta.valor_alvo)}</span>
                    </div>
                </div>
                <div class="met-card__footer">
                    <span class="met-card__pct">${progresso.toFixed(1)}%</span>
                    ${prioridadeTag}
                    <span class="met-card__remaining-value">Faltam ${Utils.formatCurrency(Math.max(0, (meta.valor_alvo || 0) - (meta.valor_atual || 0)))}</span>
                    ${statusTag}
                </div>
                ${isCompleted ? '<div class="met-celebrate"><i data-lucide="party-popper" style="width:14px;height:14px"></i> Meta atingida!</div>' : ''}
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    },

    // ==================== MODAIS ====================

    openModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // ==================== METAS: CRUD ====================

    openMetaModal(metaId = null) {
        STATE.editingMetaId = metaId;
        const title = document.getElementById('modalMetaTitle');
        const form = document.getElementById('formMeta');

        // Populate conta select
        const contaSelect = document.getElementById('metaContaId');
        if (contaSelect) {
            contaSelect.innerHTML = '<option value="">— Sem vínculo (aporte manual) —</option>';
            STATE.contas.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                const saldo = c.saldoAtual != null ? ` • ${Utils.formatCurrency(c.saldoAtual)}` : '';
                opt.textContent = c.nome + (c.instituicao ? ` (${c.instituicao})` : '') + saldo;
                contaSelect.appendChild(opt);
            });
        }

        if (metaId) {
            const meta = STATE.metas.find(m => m.id === metaId);
            if (!meta) return;
            if (preventDemoAction(meta)) return;
            if (title) title.textContent = 'Editar Meta';
            document.getElementById('metaTitulo').value = meta.titulo || '';
            document.getElementById('metaValorAlvo').value = Utils.formatNumber(meta.valor_alvo);
            document.getElementById('metaValorAtual').value = Utils.formatNumber(meta.valor_atual || 0);
            document.getElementById('metaTipo').value = Utils.normalizeMetaTipo(meta.tipo);
            document.getElementById('metaPrioridade').value = meta.prioridade || 'media';
            document.getElementById('metaPrazo').value = meta.data_prazo || '';
            document.getElementById('metaCor').value = meta.cor || '#8b5cf6';
            document.getElementById('metaId').value = meta.id;
            if (contaSelect) contaSelect.value = meta.conta_id || '';

            document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                d.classList.toggle('active', d.dataset.color === (meta.cor || '#8b5cf6'));
            });
        } else {
            if (title) title.textContent = 'Nova Meta';
            form?.reset();
            document.getElementById('metaValorAtual').value = '0,00';
            document.getElementById('metaCor').value = '#8b5cf6';
            document.getElementById('metaId').value = '';
            if (contaSelect) contaSelect.value = '';
            document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                d.classList.toggle('active', d.dataset.color === '#8b5cf6');
            });
        }

        MetasApp.onMetaContaChange();
        MetasApp.updateAporteSugerido();
        MetasApp.openModal('modalMeta');
    },

    async handleMetaSubmit(e) {
        e.preventDefault();

        const metaId = document.getElementById('metaId').value;
        const contaIdRaw = document.getElementById('metaContaId')?.value;
        const data = {
            titulo: document.getElementById('metaTitulo').value.trim(),
            valor_alvo: Utils.parseMoney(document.getElementById('metaValorAlvo').value),
            valor_atual: contaIdRaw ? 0 : Utils.parseMoney(document.getElementById('metaValorAtual').value),
            tipo: document.getElementById('metaTipo').value,
            prioridade: document.getElementById('metaPrioridade').value,
            data_prazo: document.getElementById('metaPrazo').value || null,
            cor: document.getElementById('metaCor').value,
            conta_id: contaIdRaw ? parseInt(contaIdRaw) : null,
        };

        if (!data.titulo) return Utils.showToast('Informe o título da meta', 'error');
        if (data.valor_alvo <= 0) return Utils.showToast('Informe o valor da meta', 'error');

        try {
            let res;
            if (metaId) {
                res = await apiPut(`api/financas/metas/${metaId}`, data);
            } else {
                res = await apiPost('api/financas/metas', data);
            }

            if (handleLimitError(res)) return;

            if (res.success) {
                const responseData = res.data;
                const gamification = responseData?.gamification || res.gamification;
                processGamification(gamification);

                MetasApp.closeModal('modalMeta');
                Utils.showToast(metaId ? 'Meta atualizada!' : 'Meta criada!', 'success');
                await MetasApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao salvar', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao salvar meta'), 'error');
        }
    },

    async deleteMeta(id) {
        const meta = STATE.metas.find(m => m.id === id);
        if (preventDemoAction(meta)) return;
        const result = await Swal.fire({
            title: 'Excluir meta',
            html: `Deseja excluir a meta <strong>${Utils.escHtml(meta?.titulo || '')}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await apiDelete(`api/financas/metas/${id}`);
            if (res.success !== false) {
                Utils.showToast('Meta excluída', 'success');
                await MetasApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao excluir', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao excluir'), 'error');
        }
    },

    // ==================== APORTE ====================

    openAporteModal(metaId) {
        const meta = STATE.metas.find(m => m.id === metaId);
        if (!meta) return;
        if (preventDemoAction(meta)) return;

        document.getElementById('aporteMetaId').value = metaId;
        document.getElementById('aporteValor').value = '';
        const info = document.getElementById('aporteMetaInfo');
        if (info) {
            const restante = (meta.valor_alvo || 0) - (meta.valor_atual || 0);
            info.innerHTML = `<strong>${Utils.escHtml(meta.titulo)}</strong><br>
                Faltam ${Utils.formatCurrency(Math.max(0, restante))} para a meta de ${Utils.formatCurrency(meta.valor_alvo)}`;
        }
        MetasApp.openModal('modalAporte');
        setTimeout(() => document.getElementById('aporteValor')?.focus(), 300);
    },

    async handleAporteSubmit(e) {
        e.preventDefault();

        const metaId = document.getElementById('aporteMetaId').value;
        const valor = Utils.parseMoney(document.getElementById('aporteValor').value);

        if (valor <= 0) return Utils.showToast('Informe um valor válido', 'error');

        try {
            const res = await apiPost(`api/financas/metas/${metaId}/aporte`, { valor });

            if (res.success !== false) {
                MetasApp.closeModal('modalAporte');

                const responseData = res.data;
                const meta = responseData?.meta || responseData;
                const gamification = responseData?.gamification || res.gamification;

                if (meta?.status === 'concluida') {
                    await Swal.fire({
                        icon: 'success',
                        title: '🎉 Meta concluída!',
                        html: `Parabéns! Você atingiu <strong>${Utils.formatCurrency(meta.valor_alvo)}</strong> na meta <strong>${Utils.escHtml(meta.titulo)}</strong>!`,
                        confirmButtonText: 'Celebrar! 🎊'
                    });
                } else {
                    Utils.showToast('Aporte registrado!', 'success');
                }

                processGamification(gamification);
                await MetasApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao registrar aporte', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao registrar aporte'), 'error');
        }
    },

    // ==================== TEMPLATES ====================

    async openTemplates() {
        MetasApp.openModal('modalTemplates');
        const grid = document.getElementById('templatesGrid');
        grid.innerHTML = '<div class="lk-loading-state"><i data-lucide="loader-2"></i><p>Carregando templates...</p></div>';
        if (window.lucide) lucide.createIcons();

        try {
            const res = await apiGet('api/financas/metas/templates');
            if (res.success !== false && res.data?.length) {
                grid.innerHTML = res.data.map(tmpl => {
                    const iconeHtml = tmpl.icone
                        ? `<i data-lucide="${tmpl.icone}" style="color:${getCategoryIconColor(tmpl.icone)}"></i>`
                        : '<i data-lucide="target" style="color:#ef4444"></i>';
                    return `
                    <div class="template-card" onclick="metasManager.useTemplate(${JSON.stringify(tmpl).replace(/"/g, '&quot;')})">
                        <span class="template-icon">${iconeHtml}</span>
                        <div class="template-info">
                            <strong>${Utils.escHtml(tmpl.titulo)}</strong>
                            <p>${Utils.escHtml(tmpl.descricao || '')}</p>
                            ${tmpl.valor_sugerido ? `<span class="template-valor">Sugestão: ${Utils.formatCurrency(tmpl.valor_sugerido)}</span>` : ''}
                        </div>
                        <i data-lucide="chevron-right" class="template-arrow"></i>
                    </div>`;
                }).join('');
                if (window.lucide) lucide.createIcons();
            } else {
                grid.innerHTML = '<div class="fin-empty-state"><p>Nenhum template disponível.</p></div>';
            }
        } catch (e) {
            grid.innerHTML = '<div class="fin-empty-state"><p>Erro ao carregar templates.</p></div>';
        }
    },

    useTemplate(tmpl) {
        MetasApp.closeModal('modalTemplates');
        MetasApp.openMetaModal();

        setTimeout(() => {
            if (tmpl.titulo) document.getElementById('metaTitulo').value = tmpl.titulo;
            if (tmpl.tipo) document.getElementById('metaTipo').value = Utils.normalizeMetaTipo(tmpl.tipo);
            if (tmpl.valor_sugerido) document.getElementById('metaValorAlvo').value = Utils.formatNumber(tmpl.valor_sugerido);
            if (tmpl.prioridade) document.getElementById('metaPrioridade').value = tmpl.prioridade;
            if (tmpl.cor) {
                document.getElementById('metaCor').value = tmpl.cor;
                document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                    d.classList.toggle('active', d.dataset.color === tmpl.cor);
                });
            }
            MetasApp.updateAporteSugerido();
        }, 100);
    },

    updateAporteSugerido() {
        const hint = document.getElementById('metaAporteSugerido');
        if (!hint) return;

        const valorAlvo = Utils.parseMoney(document.getElementById('metaValorAlvo')?.value || '0');
        const prazo = document.getElementById('metaPrazo')?.value;

        let valorAtual = 0;
        const contaId = document.getElementById('metaContaId')?.value;
        if (contaId) {
            const conta = STATE.contas.find(c => String(c.id) === String(contaId));
            valorAtual = conta?.saldoAtual ?? 0;
        } else {
            valorAtual = Utils.parseMoney(document.getElementById('metaValorAtual')?.value || '0');
        }

        if (!prazo || valorAlvo <= 0) { hint.textContent = ''; return; }

        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        const dataPrazo = new Date(prazo + 'T00:00:00');

        if (dataPrazo <= hoje) {
            hint.textContent = '⚠️ Esse prazo já passou. Ajuste para uma data futura.';
            return;
        }

        const restante = valorAlvo - valorAtual;
        if (restante <= 0) { hint.textContent = '🎉 Valor já atingido!'; return; }

        const diffDias = Math.ceil((dataPrazo - hoje) / (1000 * 60 * 60 * 24));
        const mesesRestantes = Math.max(1, Math.ceil(diffDias / 30.44));
        const aporteMensal = restante / mesesRestantes;

        const plural = mesesRestantes === 1 ? 'mês' : 'meses';
        hint.textContent = `💡 Para atingir no prazo: ${Utils.formatCurrency(aporteMensal)} por mês (${mesesRestantes} ${plural})`;
    },
};
