/**
 * Orçamento – Main application logic
 * Data loading, rendering, CRUD, sugestões, insights
 */

import { CONFIG, STATE, Utils, getCategoryIconColor } from './state.js';
import {
    resolveCategoriesEndpoint,
    resolveFinanceBudgetApplySuggestionsEndpoint,
    resolveFinanceBudgetCopyMonthEndpoint,
    resolveFinanceBudgetEndpoint,
    resolveFinanceBudgetSuggestionsEndpoint,
    resolveFinanceBudgetsEndpoint,
    resolveFinanceInsightsEndpoint,
    resolveFinanceSummaryEndpoint,
} from '../api/endpoints/finance.js';
import {
    apiDelete as sharedApiDelete,
    apiGet as sharedApiGet,
    apiPost as sharedApiPost,
    getErrorMessage,
} from '../shared/api.js';
import { createOrcamentoUi } from './app-ui.js';

// ── API Helpers ────────────────────────────────────────────────

async function apiGet(endpoint, params) { return sharedApiGet(endpoint, params); }
async function apiPost(endpoint, data) { return sharedApiPost(endpoint, data); }
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
    window.location.href = `${CONFIG.BASE_URL}billing`;
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

const OrcamentoUi = createOrcamentoUi({
    STATE,
    Utils,
    getCategoryIconColor,
    isDemoItem,
});

function buildAppUrl(path, params = {}) {
    const url = new URL(String(path || '').replace(/^\/+/, ''), CONFIG.BASE_URL);
    Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, value);
        }
    });

    return url.toString();
}

function getCurrentYearMonth() {
    return `${STATE.currentYear}-${String(STATE.currentMonth).padStart(2, '0')}`;
}

function getValidQueryMonth() {
    const ym = new URLSearchParams(window.location.search).get('mes');

    return ym && /^\d{4}-(0[1-9]|1[0-2])$/.test(ym) ? ym : null;
}

// ── Initialization ─────────────────────────────────────────────

export const OrcamentoApp = {
    async init() {
        OrcamentoApp.syncFromHeader();

        if (OrcamentoApp.isSugestoesPage()) {
            OrcamentoApp.attachSugestoesPageListeners();
            await OrcamentoApp.loadSugestoes();
            return;
        }

        OrcamentoApp.attachEventListeners();
        OrcamentoApp.setupMoneyInputs();
        await OrcamentoApp.loadCategorias();
        await OrcamentoApp.loadAll();
    },

    // ==================== SYNC HEADER ====================

    syncFromHeader() {
        const ym = getValidQueryMonth() || window.LukratoHeader?.getMonth?.() || sessionStorage.getItem('lkMes');
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
            window.LK?.modalSystem?.prepareOverlay(overlay, { scope: 'page' });
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

    attachSugestoesPageListeners() {
        document.addEventListener('lukrato:month-changed', (e) => {
            const ym = e.detail?.month;
            if (ym && /^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
                const [y, m] = ym.split('-').map(Number);
                STATE.currentYear = y;
                STATE.currentMonth = m;
                OrcamentoApp.syncSugestoesPageUrl();
                OrcamentoApp.loadSugestoes();
            }
        });

        document.getElementById('btnAplicarSugestoes')?.addEventListener('click', () => OrcamentoApp.aplicarSugestoes());
        document.getElementById('btnRecarregarSugestoes')?.addEventListener('click', () => OrcamentoApp.loadSugestoes());
    },

    isSugestoesPage() {
        return Boolean(document.getElementById('orcSugestoesPage'));
    },

    getSugestoesReturnUrl() {
        return document.getElementById('orcSugestoesPage')?.dataset.returnUrl || buildAppUrl('orcamento');
    },

    getSugestoesPageUrl() {
        return buildAppUrl('orcamento/sugestao-inteligente', {
            mes: getCurrentYearMonth(),
            return: 'orcamento',
        });
    },

    syncSugestoesPageUrl() {
        if (!OrcamentoApp.isSugestoesPage()) return;

        const url = new URL(window.location.href);
        url.searchParams.set('mes', getCurrentYearMonth());
        window.history.replaceState({}, '', url.toString());
    },

    setupMoneyInputs() {
        OrcamentoUi.setupMoneyInputs();
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
            const res = await apiGet(resolveCategoriesEndpoint());
            if (res.success !== false && res.data) {
                STATE.categorias = (res.data || []).filter(c => c.tipo === 'despesa');
                OrcamentoApp.populateCategoriaSelect();
            }
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    },

    populateCategoriaSelect() {
        OrcamentoUi.populateCategoriaSelect();
    },

    async loadResumo() {
        try {
            const res = await apiGet(resolveFinanceSummaryEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
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
            const res = await apiGet(resolveFinanceBudgetsEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
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
            const res = await apiGet(resolveFinanceInsightsEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
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
        OrcamentoUi.renderResumo(data);
    },

    // ==================== RENDER: ORÇAMENTOS ====================

    getPeriodBudgetContext() {
        return OrcamentoUi.getPeriodBudgetContext();
    },

    formatDayWindow(days, fallback = 'periodo') {
        return OrcamentoUi.formatDayWindow(days, fallback);
    },

    getDailyBudgetInfo(orc) {
        return OrcamentoUi.getDailyBudgetInfo(orc);
    },

    getFilteredOrcamentos() {
        return OrcamentoUi.getFilteredOrcamentos();
    },

    compareOrcamentos(a, b, sort) {
        return OrcamentoUi.compareOrcamentos(a, b, sort);
    },

    renderFocusPanel() {
        OrcamentoUi.renderFocusPanel();
    },

    buildDerivedInsights() {
        return OrcamentoUi.buildDerivedInsights();
    },

    renderOrcamentos() {
        OrcamentoUi.renderOrcamentos();
    },

    // ==================== RENDER: INSIGHTS ====================

    renderInsights(insights) {
        OrcamentoUi.renderInsights(insights);
    },

    // ==================== MODAIS ====================

    openModal(id) {
        OrcamentoUi.openModal(id);
    },

    closeModal(id) {
        OrcamentoUi.closeModal(id);
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
            const res = await apiPost(resolveFinanceBudgetsEndpoint(), {
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
            const res = await apiDelete(resolveFinanceBudgetEndpoint(id));
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
        if (!OrcamentoApp.isSugestoesPage()) {
            window.location.href = OrcamentoApp.getSugestoesPageUrl();
            return;
        }

        await OrcamentoApp.loadSugestoes();
    },

    async loadSugestoes() {
        const list = document.getElementById('sugestoesList');
        if (!list) return;
        list.innerHTML = '<div class="lk-loading-state"><i data-lucide="loader-2"></i><p>Analisando seu histórico...</p></div>';
        if (window.lucide) lucide.createIcons();

        try {
            const res = await apiGet(resolveFinanceBudgetSuggestionsEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
            if (res.success !== false && res.data?.length) {
                STATE.sugestoes = res.data;
                OrcamentoApp.renderSugestoes();
            } else {
                list.innerHTML = '<div class="fin-empty-state"><p>Não encontramos dados suficientes para gerar sugestões.<br>Continue registrando suas despesas!</p></div>';
            }
        } catch {
            list.innerHTML = '<div class="fin-empty-state"><p>Erro ao carregar sugestões.</p></div>';
        }
    },

    renderSugestoes() {
        const list = document.getElementById('sugestoesList');
        if (!list) return;

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
            <div class="sugestao-item surface-card">
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
            const res = await apiPost(resolveFinanceBudgetApplySuggestionsEndpoint(), {
                mes: STATE.currentMonth,
                ano: STATE.currentYear,
                orcamentos: orcamentos
            });

            if (handleLimitError(res)) return;

            if (res.success) {
                const appliedCount = res.data?.aplicados || orcamentos.length;
                await OrcamentoApp.showSugestoesAppliedAlert(appliedCount);
            } else {
                Utils.showToast(res.message || 'Erro ao aplicar', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao aplicar sugestões'), 'error');
        }
    },

    async showSugestoesAppliedAlert(appliedCount) {
        if (typeof Swal === 'undefined') {
            Utils.showToast(`${appliedCount} orçamentos configurados!`, 'success');
            return;
        }

        const result = await Swal.fire({
            icon: 'success',
            title: 'Orçamentos aplicados com sucesso',
            text: `${appliedCount} limite${appliedCount === 1 ? '' : 's'} foram configurados para este mês.`,
            showDenyButton: true,
            confirmButtonText: 'Voltar para orçamento',
            denyButtonText: 'Revisar sugestões',
        });

        if (result.isConfirmed) {
            window.location.href = OrcamentoApp.getSugestoesReturnUrl();
            return;
        }

        await OrcamentoApp.loadSugestoes();
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
            const res = await apiPost(resolveFinanceBudgetCopyMonthEndpoint(), {
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
            const res = await apiGet(resolveFinanceBudgetSuggestionsEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
            if (res.success !== false && res.data?.length) {
                const sug = res.data.find(s => s.categoria_id == categoriaId);
                if (sug) {
                    hint.textContent = `💡 Sugestão: ${Utils.formatCurrency(sug.valor_sugerido)} (média: ${Utils.formatCurrency(sug.media_gastos)})`;
                } else {
                    hint.textContent = '';
                }
            }
        } catch {
            hint.textContent = '';
        }
    },
};



