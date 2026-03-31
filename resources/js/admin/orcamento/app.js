/**
 * Orçamento – Main application logic
 * Data loading, rendering, CRUD, sugestões, insights
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
        if (window.PlanLimits?.promptUpgrade) {
            window.PlanLimits.promptUpgrade({
                context: 'orcamento',
                message: msg || 'Este recurso está disponível no plano Pro.',
            }).catch(() => { /* ignore */ });
            return true;
        }

        if (window.LKFeedback?.upgradePrompt) {
            window.LKFeedback.upgradePrompt({
                context: 'orcamento',
                message: msg || 'Este recurso está disponível no plano Pro.',
            }).catch(() => { /* ignore */ });
            return true;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Recurso Pro',
                text: msg || 'Este recurso está disponível no plano Pro.',
                showCancelButton: true,
                confirmButtonText: 'Ver planos',
                cancelButtonText: 'Agora não',
            }).then((result) => {
                if (result.isConfirmed) goToBilling();
            });
        } else {
            goToBilling();
        }

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

// ── Initialization ─────────────────────────────────────────────

export const OrcamentoApp = {
    async init() {
        OrcamentoApp.syncFromHeader();
        OrcamentoApp.attachEventListeners();
        OrcamentoApp.setupMoneyInputs();
        await OrcamentoApp.loadCategorias();
        await OrcamentoApp.loadAll();
    },

    // ==================== SYNC HEADER ====================

    syncFromHeader() {
        const ym = window.LukratoHeader?.getMonth?.() || sessionStorage.getItem('lkMes');
        if (ym && /^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
            const [y, m] = ym.split('-').map(Number);
            STATE.currentYear = y;
            STATE.currentMonth = m;
        }
    },

    // ==================== EVENT LISTENERS ====================

    attachEventListeners() {
        document.addEventListener('lukrato:month-changed', (e) => {
            const ym = e.detail?.month;
            if (ym && /^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
                const [y, m] = ym.split('-').map(Number);
                STATE.currentYear = y;
                STATE.currentMonth = m;
                OrcamentoApp.loadAll();
            }
        });

        document.getElementById('btnAutoSugerir')?.addEventListener('click', () => OrcamentoApp.openSugestoes());
        document.getElementById('btnAutoSugerirEmpty')?.addEventListener('click', () => OrcamentoApp.openSugestoes());
        document.getElementById('btnCopiarMes')?.addEventListener('click', () => OrcamentoApp.copiarMesAnterior());
        document.getElementById('btnNovoOrcamento')?.addEventListener('click', () => OrcamentoApp.openOrcamentoModal());
        document.getElementById('formOrcamento')?.addEventListener('submit', (e) => OrcamentoApp.handleOrcamentoSubmit(e));
        document.getElementById('btnAplicarSugestoes')?.addEventListener('click', () => OrcamentoApp.aplicarSugestoes());
        document.getElementById('orcSearchInput')?.addEventListener('input', (e) => {
            STATE.ui.query = e.target.value || '';
            OrcamentoApp.renderOrcamentos();
        });
        document.getElementById('orcSortSelect')?.addEventListener('change', (e) => {
            STATE.ui.sort = e.target.value || 'usage';
            OrcamentoApp.renderOrcamentos();
        });
        document.querySelectorAll('#orcFilterChips [data-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                STATE.ui.filter = button.dataset.filter || 'all';
                document.querySelectorAll('#orcFilterChips [data-filter]').forEach((chip) => {
                    chip.classList.toggle('is-active', chip === button);
                });
                OrcamentoApp.renderOrcamentos();
            });
        });

        document.getElementById('orcCategoria')?.addEventListener('change', (e) => OrcamentoApp.loadCategorySuggestion(e.target.value));

        // Modal overlays
        document.querySelectorAll('.fin-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) OrcamentoApp.closeModal(overlay.id);
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => OrcamentoApp.closeModal(btn.dataset.closeModal));
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.fin-modal-overlay.active').forEach(m => OrcamentoApp.closeModal(m.id));
            }
        });
    },

    setupMoneyInputs() {
        const moneyFields = ['orcValor'];
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
                OrcamentoApp.loadResumo(),
                OrcamentoApp.loadOrcamentos(),
                OrcamentoApp.loadInsights()
            ]);
            OrcamentoApp.renderFocusPanel();
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    },

    async loadCategorias() {
        try {
            const res = await apiGet('api/categorias');
            if (res.success !== false && res.data) {
                STATE.categorias = (res.data || []).filter(c => c.tipo === 'despesa');
                OrcamentoApp.populateCategoriaSelect();
            }
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    },

    populateCategoriaSelect() {
        const select = document.getElementById('orcCategoria');
        if (!select) return;
        select.innerHTML = '<option value="">Selecione uma categoria</option>';
        STATE.categorias.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.nome;
            select.appendChild(opt);
        });
    },

    async loadResumo() {
        try {
            const res = await apiGet(`api/financas/resumo?mes=${STATE.currentMonth}&ano=${STATE.currentYear}`);
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                STATE.resumo = res.data;
                OrcamentoApp.renderResumo(res.data);
            }
        } catch (e) {
            console.error('Erro ao carregar resumo:', e);
        }
    },

    async loadOrcamentos() {
        try {
            const res = await apiGet(`api/financas/orcamentos?mes=${STATE.currentMonth}&ano=${STATE.currentYear}`);
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                STATE.orcamentos = getCollectionPayload(res.data, 'orcamentos');
                OrcamentoApp.renderFocusPanel();
                OrcamentoApp.renderOrcamentos();
            }
        } catch (e) {
            console.error('Erro ao carregar orçamentos:', e);
        }
    },

    async loadInsights() {
        try {
            const res = await apiGet(`api/financas/insights?mes=${STATE.currentMonth}&ano=${STATE.currentYear}`);
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                STATE.insights = getCollectionPayload(res.data, 'insights');
                OrcamentoApp.renderFocusPanel();
                OrcamentoApp.renderInsights(STATE.insights);
            }
        } catch (e) {
            console.error('Erro ao carregar insights:', e);
        }
    },

    // ==================== RENDER: RESUMO ====================

    renderResumo(data) {
        if (!data) return;

        const orc = data.orcamento || data.orcamentos || {};

        // Saúde financeira
        const saude = orc.saude_financeira || {};
        const score = saude.score ?? orc.saude_score ?? 0;
        const ringFill = document.getElementById('saudeRingFill');
        const scoreEl = document.getElementById('saudeScore');
        const labelEl = document.getElementById('saudeLabel');
        const saudeContent = document.getElementById('saudeContent');
        const saudeCta = document.getElementById('saudeCta');

        const temOrcamentos = (orc.total_limite ?? orc.total_orcado ?? 0) > 0 || STATE.orcamentos.length > 0;

        if (!temOrcamentos && saudeContent && saudeCta) {
            saudeContent.style.display = 'none';
            saudeCta.style.display = '';
        } else if (saudeContent && saudeCta) {
            saudeContent.style.display = '';
            saudeCta.style.display = 'none';
        }

        if (ringFill) {
            ringFill.style.strokeDasharray = `${score}, 100`;
            ringFill.classList.remove('orc-score--good', 'orc-score--warn', 'orc-score--bad');
            if (score >= 70) ringFill.classList.add('orc-score--good');
            else if (score >= 40) ringFill.classList.add('orc-score--warn');
            else ringFill.classList.add('orc-score--bad');
        }
        if (scoreEl) scoreEl.textContent = score;
        if (labelEl) {
            if (score >= 80) labelEl.textContent = 'Excelente!';
            else if (score >= 60) labelEl.textContent = 'Bom';
            else if (score >= 40) labelEl.textContent = 'Atenção';
            else labelEl.textContent = 'Crítico';

            labelEl.className = 'orc-summary-card__status';
            if (score >= 70) labelEl.classList.add('orc-status--good');
            else if (score >= 40) labelEl.classList.add('orc-status--warn');
            else labelEl.classList.add('orc-status--bad');
        }

        const totalOrcado = orc.total_limite ?? orc.total_orcado ?? 0;
        const totalGasto = orc.total_gasto ?? 0;
        const totalDisponivel = orc.total_disponivel ?? Math.max(0, totalOrcado - totalGasto);
        Utils.setText('totalOrcado', Utils.formatCurrency(totalOrcado));
        Utils.setText('totalGasto', Utils.formatCurrency(totalGasto));
        Utils.setText('totalDisponivel', Utils.formatCurrency(totalDisponivel));
    },

    // ==================== RENDER: ORÇAMENTOS ====================

    getPeriodBudgetContext() {
        const totalDays = new Date(STATE.currentYear, STATE.currentMonth, 0).getDate();
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;
        const selectedKey = (STATE.currentYear * 12) + STATE.currentMonth;
        const currentKey = (currentYear * 12) + currentMonth;

        if (selectedKey < currentKey) {
            return {
                phase: 'past',
                totalDays,
                remainingDays: 0,
                elapsedDays: totalDays,
            };
        }

        if (selectedKey > currentKey) {
            return {
                phase: 'future',
                totalDays,
                remainingDays: totalDays,
                elapsedDays: 0,
            };
        }

        const today = Math.min(now.getDate(), totalDays);

        return {
            phase: 'current',
            totalDays,
            remainingDays: Math.max(1, totalDays - today + 1),
            elapsedDays: today,
        };
    },

    formatDayWindow(days, fallback = 'periodo') {
        if (!days || days <= 0) return fallback;
        return `${days} dia${days === 1 ? '' : 's'}`;
    },

    getDailyBudgetInfo(orc) {
        const context = OrcamentoApp.getPeriodBudgetContext();
        const gasto = Number(orc?.gasto_real || 0);
        const limiteBase = Number(orc?.valor_limite || 0);
        const limiteEfetivo = Number(orc?.limite_efetivo || limiteBase);
        const disponivel = Number(orc?.disponivel ?? Math.max(0, limiteEfetivo - gasto));
        const excedido = Number(orc?.excedido ?? Math.max(0, gasto - limiteEfetivo));

        if (context.phase === 'past') {
            const mediaReal = context.totalDays > 0 ? gasto / context.totalDays : 0;
            return {
                tone: 'neutral',
                label: 'Media real por dia',
                value: `${Utils.formatCurrency(mediaReal)}/dia`,
                hint: `Periodo encerrado em ${context.totalDays} dias.`,
            };
        }

        if (context.phase === 'future') {
            const planejado = context.totalDays > 0 ? limiteEfetivo / context.totalDays : 0;
            return {
                tone: 'info',
                label: 'Planejado por dia',
                value: `${Utils.formatCurrency(planejado)}/dia`,
                hint: `Distribuindo o limite pelos ${context.totalDays} dias do periodo.`,
            };
        }

        if (excedido > 0) {
            const corteDiario = context.remainingDays > 0 ? excedido / context.remainingDays : excedido;
            return {
                tone: 'danger',
                label: 'Corte necessario por dia',
                value: `${Utils.formatCurrency(corteDiario)}/dia`,
                hint: `Para compensar ${Utils.formatCurrency(excedido)} nos proximos ${OrcamentoApp.formatDayWindow(context.remainingDays)}.`,
            };
        }

        const diarioDisponivel = context.remainingDays > 0 ? disponivel / context.remainingDays : 0;
        return {
            tone: diarioDisponivel > 0 ? 'success' : 'warning',
            label: 'Pode gastar por dia',
            value: `${Utils.formatCurrency(diarioDisponivel)}/dia`,
            hint: `Pelos proximos ${OrcamentoApp.formatDayWindow(context.remainingDays)} sem estourar.`,
        };
    },

    getFilteredOrcamentos() {
        const query = STATE.ui.query.trim().toLowerCase();

        return STATE.orcamentos.filter((orc) => {
            const catNome = (orc.categoria?.nome || orc.categoria_nome || '').toLowerCase();
            if (query && !catNome.includes(query)) {
                return false;
            }

            const pct = orc.percentual || 0;
            switch (STATE.ui.filter) {
                case 'over':
                    return pct > 100;
                case 'warn':
                    return pct >= 80 && pct <= 100;
                case 'ok':
                    return pct < 80;
                case 'rollover':
                    return !!orc.rollover && (orc.rollover_valor || 0) > 0;
                default:
                    return true;
            }
        }).sort((a, b) => OrcamentoApp.compareOrcamentos(a, b, STATE.ui.sort));
    },

    compareOrcamentos(a, b, sort) {
        const aRemaining = a.disponivel ?? 0;
        const bRemaining = b.disponivel ?? 0;
        const aExceeded = a.excedido ?? 0;
        const bExceeded = b.excedido ?? 0;

        switch (sort) {
            case 'exceeded':
                return bExceeded - aExceeded;
            case 'remaining':
                return bRemaining - aRemaining;
            case 'alpha':
                return (a.categoria?.nome || a.categoria_nome || '').localeCompare((b.categoria?.nome || b.categoria_nome || ''), 'pt-BR');
            case 'usage':
            default:
                return (b.percentual || 0) - (a.percentual || 0);
        }
    },

    renderFocusPanel() {
        const focus = document.getElementById('orcFocusContent');
        const stats = document.getElementById('orcFocusStats');
        if (!focus || !stats) return;

        const periodContext = OrcamentoApp.getPeriodBudgetContext();
        const resumo = STATE.resumo?.orcamento || STATE.resumo?.orcamentos || {};
        const emAlerta = resumo.em_alerta ?? STATE.orcamentos.filter((orc) => (orc.percentual || 0) >= 80 && (orc.percentual || 0) <= 100).length;
        const estourados = resumo.estourados ?? STATE.orcamentos.filter((orc) => (orc.percentual || 0) > 100).length;
        const usoGeral = resumo.percentual_geral ?? 0;
        const totalOrcado = resumo.total_limite ?? resumo.total_orcado ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.limite_efetivo || orc.valor_limite || 0), 0);
        const totalGasto = resumo.total_gasto ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.gasto_real || 0), 0);
        const disponivelTotal = resumo.total_disponivel ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.disponivel ?? 0), 0);
        const topPressure = [...STATE.orcamentos].sort((a, b) => (b.percentual || 0) - (a.percentual || 0))[0];
        const dailyTotal = periodContext.phase === 'past'
            ? (periodContext.totalDays > 0 ? totalGasto / periodContext.totalDays : 0)
            : (periodContext.phase === 'future'
                ? (periodContext.totalDays > 0 ? totalOrcado / periodContext.totalDays : 0)
                : (periodContext.remainingDays > 0 ? disponivelTotal / periodContext.remainingDays : 0));
        const dailyTotalLabel = periodContext.phase === 'past'
            ? 'Media diaria'
            : (periodContext.phase === 'future' ? 'Plano diario' : 'Folga por dia');

        stats.innerHTML = `
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Em alerta</span>
                <strong class="orc-focus-stat__value">${emAlerta}</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Estourados</span>
                <strong class="orc-focus-stat__value">${estourados}</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">Uso geral</span>
                <strong class="orc-focus-stat__value">${Math.round(usoGeral)}%</strong>
            </div>
            <div class="orc-focus-stat">
                <span class="orc-focus-stat__label">${dailyTotalLabel}</span>
                <strong class="orc-focus-stat__value">${Utils.formatCurrency(dailyTotal)}/dia</strong>
            </div>
        `;

        if (!topPressure) {
            focus.innerHTML = `
                <div class="orc-focus-callout">
                    <div>
                        <h2 class="orc-focus-callout__title">Nenhum orcamento criado ainda.</h2>
                        <p class="orc-focus-callout__text">Comece com sugestoes automaticas ou crie manualmente as categorias que mais pesam no seu mes.</p>
                    </div>
                    <div class="orc-focus-callout__actions">
                        <button type="button" class="orc-action-btn orc-action-btn--primary" onclick="orcamentoManager.openSugestoes()">
                            <i data-lucide="wand-2"></i>
                            <span>Sugestao Inteligente</span>
                        </button>
                        <button type="button" class="orc-action-btn orc-action-btn--success" onclick="orcamentoManager.openOrcamentoModal()">
                            <i data-lucide="plus"></i>
                            <span>Novo Orcamento</span>
                        </button>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        const topName = topPressure.categoria?.nome || topPressure.categoria_nome || 'Categoria';
        const topDailyInfo = OrcamentoApp.getDailyBudgetInfo(topPressure);
        const topRemaining = topPressure.percentual > 100
            ? `Excedido em <strong>${Utils.formatCurrency(topPressure.excedido || 0)}</strong>.`
            : `Restam <strong>${Utils.formatCurrency(topPressure.disponivel || 0)}</strong> nesta categoria.`;
        const helper = disponivelTotal > 0
            ? `Voce ainda tem ${Utils.formatCurrency(disponivelTotal)} de folga no total do periodo.`
            : 'Seu limite total do periodo ja foi consumido.';

        focus.innerHTML = `
            <div class="orc-focus-callout">
                <div>
                    <h2 class="orc-focus-callout__title">${Utils.escHtml(topName)}</h2>
                    <p class="orc-focus-callout__text">${topRemaining} ${helper} ${Utils.escHtml(topDailyInfo.hint)}</p>
                    <div class="orc-focus-callout__meta">
                        <span class="orc-focus-callout__pill">${Math.round(topPressure.percentual || 0)}% usado</span>
                        <span class="orc-focus-callout__pill">${Utils.escHtml(topDailyInfo.label)}: ${Utils.escHtml(topDailyInfo.value)}</span>
                        ${topPressure.rollover && (topPressure.rollover_valor || 0) > 0
                ? `<span class="orc-focus-callout__pill">Rollover de ${Utils.formatCurrency(topPressure.rollover_valor || 0)}</span>`
                : ''}
                    </div>
                </div>
                <div class="orc-focus-callout__actions">
                    <button type="button" class="orc-action-btn orc-action-btn--success" onclick="orcamentoManager.openOrcamentoModal(${topPressure.id})">
                        <i data-lucide="pencil"></i>
                        <span>Ajustar limite</span>
                    </button>
                    <button type="button" class="orc-action-btn" onclick="orcamentoManager.openSugestoes()">
                        <i data-lucide="wand-2"></i>
                        <span>Comparar sugestao</span>
                    </button>
                </div>
            </div>
        `;
        if (window.lucide) lucide.createIcons();
    },

    buildDerivedInsights() {
        const periodContext = OrcamentoApp.getPeriodBudgetContext();
        const resumo = STATE.resumo?.orcamento || STATE.resumo?.orcamentos || {};
        const totalGasto = resumo.total_gasto ?? STATE.orcamentos.reduce((sum, orc) => sum + (orc.gasto_real || 0), 0);
        const percentualGeral = resumo.percentual_geral ?? 0;
        const derived = [];

        if (percentualGeral >= 90 && totalGasto > 0) {
            derived.push({
                tipo: 'perigo',
                titulo: 'Mês apertado no consolidado',
                mensagem: `Você já consumiu ${Math.round(percentualGeral)}% do limite total deste período.`,
                icone: 'siren',
            });
        } else if (percentualGeral >= 70 && totalGasto > 0) {
            derived.push({
                tipo: 'alerta',
                titulo: 'Uso geral pede atenção',
                mensagem: `Seu uso geral está em ${Math.round(percentualGeral)}%. Vale revisar as categorias mais pressionadas.`,
                icone: 'gauge',
            });
        }

        if (totalGasto > 0) {
            const topSpender = [...STATE.orcamentos].sort((a, b) => (b.gasto_real || 0) - (a.gasto_real || 0))[0];
            const share = topSpender ? ((topSpender.gasto_real || 0) / totalGasto) * 100 : 0;
            if (topSpender && share >= 40) {
                derived.push({
                    tipo: 'info',
                    titulo: `${topSpender.categoria?.nome || topSpender.categoria_nome} concentra boa parte do gasto`,
                    mensagem: `Essa categoria representa ${Math.round(share)}% do total gasto no periodo.`,
                    icone: 'pie-chart',
                    categoria_id: topSpender.categoria_id,
                });
            }
        }

        const topPressure = [...STATE.orcamentos].sort((a, b) => (b.percentual || 0) - (a.percentual || 0))[0];
        if (topPressure && periodContext.phase !== 'past') {
            const dailyInfo = OrcamentoApp.getDailyBudgetInfo(topPressure);
            derived.push({
                tipo: topPressure.percentual > 100 ? 'perigo' : 'info',
                titulo: `${topPressure.categoria?.nome || topPressure.categoria_nome} pede ritmo diario claro`,
                mensagem: `${dailyInfo.label}: ${dailyInfo.value}. ${dailyInfo.hint}`,
                icone: topPressure.percentual > 100 ? 'triangle-alert' : 'calendar-range',
                categoria_id: topPressure.categoria_id,
            });
        }

        const rolloverWin = STATE.orcamentos.find((orc) => (orc.rollover_valor || 0) > 0 && (orc.percentual || 0) < 80);
        if (rolloverWin) {
            derived.push({
                tipo: 'positivo',
                titulo: `${rolloverWin.categoria?.nome || rolloverWin.categoria_nome} esta respirando melhor`,
                mensagem: `O rollover adicionou ${Utils.formatCurrency(rolloverWin.rollover_valor || 0)} de folga nesta categoria.`,
                icone: 'refresh-cw',
                categoria_id: rolloverWin.categoria_id,
            });
        }

        const slackCandidate = [...STATE.orcamentos]
            .filter((orc) => (orc.percentual || 0) <= 35 && (orc.disponivel || 0) > 0)
            .sort((a, b) => (b.disponivel || 0) - (a.disponivel || 0))[0];
        if (slackCandidate) {
            derived.push({
                tipo: 'positivo',
                titulo: `${slackCandidate.categoria?.nome || slackCandidate.categoria_nome} está com folga relevante`,
                mensagem: `Ainda sobram ${Utils.formatCurrency(slackCandidate.disponivel || 0)}. Talvez o limite possa ser refinado no próximo ciclo.`,
                icone: 'sparkles',
                categoria_id: slackCandidate.categoria_id,
            });
        }

        return derived;
    },

    renderOrcamentos() {
        const grid = document.getElementById('orcamentosGrid');
        const empty = document.getElementById('orcamentosEmpty');
        if (!grid || !empty) return;

        if (!STATE.orcamentos.length) {
            grid.style.display = 'none';
            empty.style.display = 'flex';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';
        const filteredOrcamentos = OrcamentoApp.getFilteredOrcamentos();

        if (!filteredOrcamentos.length) {
            grid.innerHTML = `
                <div class="orc-soft-empty surface-card">
                    <i data-lucide="search-x"></i>
                    <p>Nenhum orcamento encontrado para os filtros atuais.</p>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            return;
        }

        grid.innerHTML = filteredOrcamentos.map(orc => {
            const pct = orc.percentual || 0;
            const isDemo = isDemoItem(orc);
            const statusClass = pct >= 100 ? 'over' : pct >= 80 ? 'warn' : 'ok';
            const catNome = orc.categoria?.nome || orc.categoria_nome || 'Categoria';
            const catIcone = orc.categoria?.icone || 'tag';
            const gasto = orc.gasto_real || 0;
            const limiteBase = orc.valor_limite || 0;
            const limiteEfetivo = orc.limite_efetivo || limiteBase;
            const disponivel = orc.disponivel ?? Math.max(0, limiteEfetivo - gasto);
            const excedido = orc.excedido ?? Math.max(0, gasto - limiteEfetivo);
            const rolloverTag = orc.rollover && orc.rollover_valor > 0
                ? `<span class="orc-card__badge" title="Inclui R$ ${Utils.formatNumber(orc.rollover_valor)} do mês anterior">+${Utils.formatNumber(orc.rollover_valor)} rollover</span>`
                : '';
            const limitHint = limiteEfetivo > limiteBase
                ? `<span class="orc-card__limit-hint">Base ${Utils.formatCurrency(limiteBase)} • Efetivo ${Utils.formatCurrency(limiteEfetivo)}</span>`
                : `<span class="orc-card__limit-hint">Limite do periodo ${Utils.formatCurrency(limiteBase)}</span>`;
            const dailyInfo = OrcamentoApp.getDailyBudgetInfo(orc);
            const dailyIcon = dailyInfo.tone === 'danger'
                ? 'triangle-alert'
                : (dailyInfo.tone === 'info'
                    ? 'calendar-range'
                    : (dailyInfo.tone === 'neutral' ? 'receipt' : 'wallet'));
            const actionsMarkup = isDemo
                ? '<span class="orc-card__badge">Exemplo</span>'
                : `
                    <button class="orc-card__action-btn" onclick="orcamentoManager.openOrcamentoModal(${orc.id})" title="Editar">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="orc-card__action-btn orc-card__action-btn--danger" onclick="orcamentoManager.deleteOrcamento(${orc.id})" title="Excluir">
                        <i data-lucide="trash-2"></i>
                    </button>
                `;

            return `
            <div class="orc-card surface-card surface-card--interactive surface-card--clip ${statusClass}" data-aos="fade-up">
                <div class="orc-card__header">
                    <div class="orc-card__category">
                        <span class="orc-card__icon"><i data-lucide="${catIcone}" style="color:${getCategoryIconColor(catIcone)}"></i></span>
                        <span class="orc-card__name">${Utils.escHtml(catNome)}</span>
                    </div>
                    <div class="orc-card__actions">
                        ${actionsMarkup}
                    </div>
                </div>
                <div class="orc-card__progress">
                    <div class="orc-card__progress-bar">
                        <div class="orc-card__progress-fill ${statusClass}" style="width: ${Math.min(pct, 100)}%"></div>
                    </div>
                    <div class="orc-card__progress-info">
                        <span class="orc-card__spent">${Utils.formatCurrency(gasto)}</span>
                        <span class="orc-card__limit">de ${Utils.formatCurrency(limiteEfetivo)}</span>
                    </div>
                    ${limitHint}
                    <div class="orc-card__daily orc-card__daily--${dailyInfo.tone}">
                        <div class="orc-card__daily-label">
                            <i data-lucide="${dailyIcon}"></i>
                            <span>${Utils.escHtml(dailyInfo.label)}</span>
                        </div>
                        <strong class="orc-card__daily-value">${Utils.escHtml(dailyInfo.value)}</strong>
                        <span class="orc-card__daily-hint">${Utils.escHtml(dailyInfo.hint)}</span>
                    </div>
                </div>
                <div class="orc-card__footer">
                    <span class="orc-card__pct ${statusClass}">${pct.toFixed(0)}%</span>
                    <span class="orc-card__remaining ${excedido > 0 ? 'orc-card__remaining--negative' : ''}">
                        ${excedido > 0 ? 'Excedido' : 'Resta'} ${Utils.formatCurrency(excedido > 0 ? excedido : disponivel)}
                    </span>
                    ${rolloverTag}
                </div>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    },

    // ==================== RENDER: INSIGHTS ====================

    renderInsights(insights) {
        const section = document.getElementById('insightsSection');
        const grid = document.getElementById('insightsGrid');
        if (!grid) return;
        const combinedInsights = [...(insights || []), ...OrcamentoApp.buildDerivedInsights()];
        const dedupedInsights = Array.from(new Map(combinedInsights.map((item) => [`${item.tipo}:${item.titulo}`, item])).values()).slice(0, 8);

        if (!dedupedInsights.length) {
            if (section) section.style.display = 'none';
            return;
        }

        if (section) section.style.display = '';

        grid.innerHTML = dedupedInsights.map(insight => {
            const icon = insight.icone || Utils.getInsightIcon(insight.tipo);
            const levelMap = { alerta: 'warning', perigo: 'danger', positivo: 'success', info: 'info' };
            const level = insight.nivel || levelMap[insight.tipo] || 'info';
            const cta = insight.categoria_id
                ? `<button type="button" class="orc-insight-action" onclick="orcamentoManager.openOrcamentoModalByCategoria(${insight.categoria_id})">
                        <i data-lucide="pencil"></i>
                        <span>Ajustar</span>
                   </button>`
                : '';
            return `
            <div class="orc-insight-card surface-card surface-card--interactive ${level}" data-aos="fade-up">
                <div class="orc-insight-icon ${level}">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="orc-insight-content">
                    <span class="orc-insight-title">${Utils.escHtml(insight.titulo || '')}</span>
                    <p class="orc-insight-text">${Utils.escHtml(insight.mensagem || '')}</p>
                </div>
                ${cta}
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

    // ==================== ORÇAMENTO: CRUD ====================

    openOrcamentoModalByCategoria(categoriaId) {
        const existing = STATE.orcamentos.find((orc) => String(orc.categoria_id) === String(categoriaId));
        if (existing) {
            OrcamentoApp.openOrcamentoModal(existing.id);
            return;
        }

        OrcamentoApp.openOrcamentoModal();
        const select = document.getElementById('orcCategoria');
        if (select) {
            select.value = String(categoriaId);
            OrcamentoApp.loadCategorySuggestion(categoriaId);
        }
    },

    openOrcamentoModal(orcId = null) {
        STATE.editingOrcamentoId = orcId;
        const title = document.getElementById('modalOrcamentoTitle');
        const form = document.getElementById('formOrcamento');

        if (orcId) {
            const orc = STATE.orcamentos.find(o => o.id === orcId);
            if (!orc) return;
            if (preventDemoAction(orc)) return;
            if (title) title.textContent = 'Editar Orçamento';
            document.getElementById('orcCategoria').value = orc.categoria_id;
            document.getElementById('orcCategoria').disabled = true;
            document.getElementById('orcValor').value = Utils.formatNumber(orc.valor_limite);
            document.getElementById('orcRollover').checked = !!orc.rollover;
            document.getElementById('orcAlerta80').checked = orc.alerta_80 !== false && orc.alerta_80 !== 0;
            document.getElementById('orcAlerta100').checked = orc.alerta_100 !== false && orc.alerta_100 !== 0;
        } else {
            if (title) title.textContent = 'Novo Orçamento';
            form?.reset();
            document.getElementById('orcCategoria').disabled = false;
            document.getElementById('orcAlerta80').checked = true;
            document.getElementById('orcAlerta100').checked = true;
        }

        document.getElementById('orcSugestao').textContent = '';
        OrcamentoApp.openModal('modalOrcamento');
    },

    async handleOrcamentoSubmit(e) {
        e.preventDefault();

        const categoriaId = document.getElementById('orcCategoria').value;
        const valorLimite = Utils.parseMoney(document.getElementById('orcValor').value);
        const rollover = document.getElementById('orcRollover').checked;
        const alerta80 = document.getElementById('orcAlerta80').checked;
        const alerta100 = document.getElementById('orcAlerta100').checked;

        if (!categoriaId) return Utils.showToast('Selecione uma categoria', 'error');
        if (valorLimite <= 0) return Utils.showToast('Informe um valor válido', 'error');

        try {
            const res = await apiPost('api/financas/orcamentos', {
                categoria_id: parseInt(categoriaId),
                valor_limite: valorLimite,
                mes: STATE.currentMonth,
                ano: STATE.currentYear,
                rollover: rollover,
                alerta_80: alerta80,
                alerta_100: alerta100
            });

            if (handleLimitError(res)) return;

            if (res.success) {
                OrcamentoApp.closeModal('modalOrcamento');
                Utils.showToast('Orçamento salvo!', 'success');
                await OrcamentoApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao salvar', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao salvar orçamento'), 'error');
        }
    },

    async deleteOrcamento(id) {
        const orc = STATE.orcamentos.find(o => o.id === id);
        if (preventDemoAction(orc)) return;
        const nome = orc?.categoria?.nome || 'este orçamento';

        const result = await Swal.fire({
            title: 'Excluir orçamento',
            html: `Deseja excluir o orçamento de <strong>${Utils.escHtml(nome)}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await apiDelete(`api/financas/orcamentos/${id}`);
            if (res.success !== false) {
                Utils.showToast('Orçamento excluído', 'success');
                await OrcamentoApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao excluir', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao excluir'), 'error');
        }
    },

    // ==================== SUGESTÕES ====================

    async openSugestoes() {
        OrcamentoApp.openModal('modalSugestoes');
        const list = document.getElementById('sugestoesList');
        list.innerHTML = '<div class="lk-loading-state"><i data-lucide="loader-2"></i><p>Analisando seu histórico...</p></div>';
        if (window.lucide) lucide.createIcons();

        try {
            const res = await apiGet(`api/financas/orcamentos/sugestoes?mes=${STATE.currentMonth}&ano=${STATE.currentYear}`);
            if (res.success !== false && res.data?.length) {
                STATE.sugestoes = res.data;
                OrcamentoApp.renderSugestoes();
            } else {
                list.innerHTML = '<div class="fin-empty-state"><p>Não encontramos dados suficientes para gerar sugestões.<br>Continue registrando suas despesas!</p></div>';
            }
        } catch (e) {
            list.innerHTML = '<div class="fin-empty-state"><p>Erro ao carregar sugestões.</p></div>';
        }
    },

    renderSugestoes() {
        const list = document.getElementById('sugestoesList');
        list.innerHTML = STATE.sugestoes.map((sug, idx) => {
            const trendIcon = sug.tendencia === 'subindo' ? 'arrow-up' : sug.tendencia === 'descendo' ? 'arrow-down' : 'minus';
            const trendClass = sug.tendencia === 'subindo' ? 'up' : sug.tendencia === 'descendo' ? 'down' : 'stable';
            const catNome = sug.categoria?.nome || sug.categoria_nome || 'Categoria';
            const catIcone = sug.categoria?.icone || 'tag';
            const mediaGastos = sug.media_gastos || sug.media_3_meses || 0;
            const economiaTag = sug.economia_sugerida > 0
                ? `<span class="sugestao-economia"><i data-lucide="banknote" aria-hidden="true"></i> economia de ${Utils.formatCurrency(sug.economia_sugerida)}/mês</span>`
                : '';
            return `
            <div class="sugestao-item">
                <div class="sugestao-info">
                    <span class="sugestao-icon"><i data-lucide="${catIcone}" style="color:${getCategoryIconColor(catIcone)}"></i></span>
                    <div class="sugestao-detail">
                        <span class="sugestao-nome">${Utils.escHtml(catNome)}</span>
                        <span class="sugestao-media">Média: ${Utils.formatCurrency(mediaGastos)}
                            <i data-lucide="${trendIcon}" class="trend-${trendClass}"></i>
                        </span>
                        ${economiaTag}
                    </div>
                </div>
                <div class="sugestao-valor">
                    <input type="text" class="fin-input sugestao-input" id="sug_${idx}"
                           value="${Utils.formatNumber(sug.valor_sugerido || 0)}"
                           oninput="orcamentoManager.formatarDinheiro(this)">
                </div>
                <label class="sugestao-check">
                    <input type="checkbox" checked data-sug-idx="${idx}">
                    <span class="checkmark"><i data-lucide="check"></i></span>
                </label>
            </div>`;
        }).join('');
        if (window.lucide) lucide.createIcons();
    },

    async aplicarSugestoes() {
        const orcamentos = [];
        STATE.sugestoes.forEach((sug, idx) => {
            const checkbox = document.querySelector(`[data-sug-idx="${idx}"]`);
            if (checkbox?.checked) {
                const input = document.getElementById(`sug_${idx}`);
                const valor = Utils.parseMoney(input?.value || '0');
                if (valor > 0 && sug.categoria_id) {
                    orcamentos.push({
                        categoria_id: sug.categoria_id,
                        valor_limite: valor,
                        alerta_80: true,
                        alerta_100: true
                    });
                }
            }
        });

        if (!orcamentos.length) return Utils.showToast('Selecione ao menos uma categoria', 'error');

        try {
            const res = await apiPost('api/financas/orcamentos/aplicar-sugestoes', {
                mes: STATE.currentMonth,
                ano: STATE.currentYear,
                orcamentos: orcamentos
            });

            if (handleLimitError(res)) return;

            if (res.success) {
                OrcamentoApp.closeModal('modalSugestoes');
                Utils.showToast(`${res.data?.aplicados || orcamentos.length} orçamentos configurados!`, 'success');
                await OrcamentoApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao aplicar', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao aplicar sugestões'), 'error');
        }
    },

    async copiarMesAnterior() {
        let mesAnt = STATE.currentMonth - 1;
        let anoAnt = STATE.currentYear;
        if (mesAnt < 1) { mesAnt = 12; anoAnt--; }

        const meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        const result = await Swal.fire({
            title: 'Copiar mês anterior',
            html: `Deseja copiar os orçamentos de <strong>${meses[mesAnt]}/${anoAnt}</strong> para o mês atual?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, copiar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await apiPost('api/financas/orcamentos/copiar-mes', {
                mes_origem: mesAnt,
                ano_origem: anoAnt,
                mes_destino: STATE.currentMonth,
                ano_destino: STATE.currentYear
            });

            if (handleLimitError(res)) return;

            if (res.success) {
                Utils.showToast(`${res.data?.copiados || 0} orçamentos copiados!`, 'success');
                await OrcamentoApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao copiar', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao copiar mês'), 'error');
        }
    },

    async loadCategorySuggestion(categoriaId) {
        const hint = document.getElementById('orcSugestao');
        if (!hint || !categoriaId) { if (hint) hint.textContent = ''; return; }

        try {
            const res = await apiGet(`api/financas/orcamentos/sugestoes?mes=${STATE.currentMonth}&ano=${STATE.currentYear}`);
            if (res.success !== false && res.data?.length) {
                const sug = res.data.find(s => s.categoria_id == categoriaId);
                if (sug) {
                    hint.textContent = `💡 Sugestão: ${Utils.formatCurrency(sug.valor_sugerido)} (média: ${Utils.formatCurrency(sug.media_gastos)})`;
                } else {
                    hint.textContent = '';
                }
            }
        } catch (e) {
            hint.textContent = '';
        }
    },
};
