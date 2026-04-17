/**
 * Financas Manager – Main application logic
 * Data loading, rendering, charts, filters, events, CRUD
 * Extracted from admin-financas-index.js (monolith → modules)
 */

import { CONFIG, STATE, Modules, Utils, getCategoryIconColor } from './state.js';
import {
    resolveAccountsEndpoint,
    resolveCategoriesEndpoint,
    resolveFinanceGoalEndpoint,
    resolveFinanceGoalTemplatesEndpoint,
    resolveFinanceGoalsEndpoint,
    resolveFinanceBudgetsEndpoint,
    resolveFinanceInsightsEndpoint,
    resolveFinanceSummaryEndpoint,
} from '../api/endpoints/finance.js';
import {
    apiDelete as sharedApiDelete,
    apiGet as sharedApiGet,
    apiPost as sharedApiPost,
    apiPut as sharedApiPut,
    getErrorMessage,
} from '../shared/api.js';
import { createFinancasUi } from './app-ui.js';
import { createFinancasOrcamentos } from './app-orcamentos.js';

// ── API Helpers ────────────────────────────────────────────────

async function apiGet(endpoint, params) {
    return sharedApiGet(endpoint, params);
}

async function apiPost(endpoint, data) {
    return sharedApiPost(endpoint, data);
}

async function apiPut(endpoint, data) {
    return sharedApiPut(endpoint, data);
}

async function apiDelete(endpoint) {
    return sharedApiDelete(endpoint);
}

function requestErrorMessage(error, fallback) {
    return getErrorMessage(error, fallback);
}

// ── Plan limit error handler ───────────────────────────────────

function handleLimitError(res) {
    const isError = res.success === false;
    if (!isError) return false;

    const msg = res.message || '';
    const isLimitError = /limite|plano gratuito|upgrade|faça upgrade/i.test(msg);

    if (isLimitError) {
        if (window.PlanLimits?.promptUpgrade) {
            window.PlanLimits.promptUpgrade({
                context: 'financas',
                message: msg || 'Este recurso está disponível no plano Pro.',
            }).catch(() => { /* ignore */ });
            return true;
        }

        if (window.LKFeedback?.upgradePrompt) {
            window.LKFeedback.upgradePrompt({
                context: 'financas',
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
    if (typeof openBillingModal === 'function') {
        openBillingModal();
    } else {
        window.location.href = `${CONFIG.BASE_URL}billing`;
    }
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
    if (!isDemoItem(item)) {
        return false;
    }

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

const FinancasUi = createFinancasUi({
    STATE,
    Utils,
    getCategoryIconColor,
    isDemoItem,
});

const FinancasOrcamentos = createFinancasOrcamentos({
    STATE,
    Utils,
    apiGet,
    apiPost,
    apiDelete,
    handleLimitError,
    requestErrorMessage,
    preventDemoAction,
    getCategoryIconColor,
    openModal: (...args) => FinancasApp.openModal(...args),
    closeModal: (...args) => FinancasApp.closeModal(...args),
    loadAll: (...args) => FinancasApp.loadAll(...args),
});

// ── Initialization ─────────────────────────────────────────────

export const FinancasApp = {
    async init() {
        FinancasApp.syncFromHeader();
        FinancasApp.attachEventListeners();
        FinancasApp.setupMoneyInputs();
        await Promise.all([FinancasApp.loadCategorias(), FinancasApp.loadContas()]);

        // Restore tab from hash or localStorage
        const hash = location.hash.replace('#', '');
        const validTabs = ['orcamentos', 'metas'];
        let initialTab = 'orcamentos';
        if (hash && validTabs.includes(hash)) {
            initialTab = hash;
        } else {
            try {
                const stored = localStorage.getItem('financas_tab');
                if (stored && validTabs.includes(stored)) initialTab = stored;
            } catch { }
        }
        if (initialTab !== 'orcamentos') FinancasApp.switchTab(initialTab);

        await FinancasApp.loadAll();
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
        // Navegação de mês via header global
        document.addEventListener('lukrato:month-changed', (e) => {
            const ym = e.detail?.month;
            if (ym && /^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
                const [y, m] = ym.split('-').map(Number);
                STATE.currentYear = y;
                STATE.currentMonth = m;
                FinancasApp.loadAll();
            }
        });

        // Tabs + keyboard nav
        const tabButtons = document.querySelectorAll('.fin-tab');
        const tabNames = [...tabButtons].map(t => t.dataset.tab);
        tabButtons.forEach(tab => {
            tab.addEventListener('click', () => FinancasApp.switchTab(tab.dataset.tab));
            tab.addEventListener('keydown', (e) => {
                const idx = tabNames.indexOf(tab.dataset.tab);
                let next = -1;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = (idx + 1) % tabNames.length;
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = (idx - 1 + tabNames.length) % tabNames.length;
                if (next >= 0) {
                    e.preventDefault();
                    tabButtons[next].focus();
                    FinancasApp.switchTab(tabNames[next]);
                }
            });
        });

        // Orçamentos
        document.getElementById('btnAutoSugerir')?.addEventListener('click', () => FinancasApp.openSugestoes());
        document.getElementById('btnAutoSugerirEmpty')?.addEventListener('click', () => FinancasApp.openSugestoes());
        document.getElementById('btnCopiarMes')?.addEventListener('click', () => FinancasApp.copiarMesAnterior());
        document.getElementById('btnNovoOrcamento')?.addEventListener('click', () => FinancasApp.openOrcamentoModal());
        document.getElementById('formOrcamento')?.addEventListener('submit', (e) => FinancasApp.handleOrcamentoSubmit(e));
        document.getElementById('btnAplicarSugestoes')?.addEventListener('click', () => FinancasApp.aplicarSugestoes());

        // Metas
        document.getElementById('btnNovaMeta')?.addEventListener('click', () => FinancasApp.openMetaModal());
        document.getElementById('btnTemplates')?.addEventListener('click', () => FinancasApp.openTemplates());
        document.getElementById('btnTemplatesEmpty')?.addEventListener('click', () => FinancasApp.openTemplates());
        document.getElementById('formMeta')?.addEventListener('submit', (e) => FinancasApp.handleMetaSubmit(e));
        document.getElementById('formAporte')?.addEventListener('submit', (e) => FinancasApp.handleAporteSubmit(e));

        // Color picker
        document.querySelectorAll('#metaCorPicker .color-dot').forEach(dot => {
            dot.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => d.classList.remove('active'));
                dot.classList.add('active');
                document.getElementById('metaCor').value = dot.dataset.color;
            });
        });

        // Modal overlays (close on backdrop click)
        document.querySelectorAll('.fin-modal-overlay').forEach(overlay => {
            window.LK?.modalSystem?.prepareOverlay(overlay, { scope: 'page' });
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) FinancasApp.closeModal(overlay.id);
            });
        });

        // Close buttons
        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => FinancasApp.closeModal(btn.dataset.closeModal));
        });

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.fin-modal-overlay.active').forEach(m => FinancasApp.closeModal(m.id));
            }
        });

        // Auto-suggest hint on category change
        document.getElementById('orcCategoria')?.addEventListener('change', (e) => FinancasApp.loadCategorySuggestion(e.target.value));

        // Meta deadline change → show suggested monthly contribution
        document.getElementById('metaPrazo')?.addEventListener('change', () => FinancasApp.updateAporteSugerido());
        document.getElementById('metaValorAlvo')?.addEventListener('input', () => FinancasApp.updateAporteSugerido());
        document.getElementById('metaValorAtual')?.addEventListener('input', () => FinancasApp.updateAporteSugerido());

        // Conta vinculada → toggle valor_atual field
        FinancasApp.syncMetaAllocationField();
    },

    setupMoneyInputs() {
        FinancasUi.setupMoneyInputs();
    },

    // ==================== DATA LOADING ====================

    async loadAll() {
        try {
            await Promise.all([
                FinancasApp.loadResumo(),
                FinancasApp.loadOrcamentos(),
                FinancasApp.loadMetas(),
                FinancasApp.loadInsights()
            ]);
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
        }
    },

    async loadCategorias() {
        try {
            const res = await apiGet(resolveCategoriesEndpoint());
            if (res.success !== false && res.data) {
                // Filter only expense categories (despesa)
                STATE.categorias = (res.data || []).filter(c => c.tipo === 'despesa');
                FinancasApp.populateCategoriaSelect();
            }
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    },

    async loadContas() {
        try {
            const res = await apiGet(resolveAccountsEndpoint(), { only_active: 1, with_balances: 1 });
            // A API de contas retorna array direto (sem wrapper {success, data})
            if (Array.isArray(res)) {
                STATE.contas = res;
            } else if (res.success !== false && Array.isArray(res.data)) {
                STATE.contas = res.data;
            }
        } catch (e) {
            console.error('Erro ao carregar contas:', e);
        }
    },

    syncMetaAllocationField() {
        FinancasUi.syncMetaAllocationField();
    },

    onMetaContaChange() {
        FinancasUi.onMetaContaChange();
    },

    populateCategoriaSelect() {
        FinancasUi.populateCategoriaSelect();
    },

    async loadResumo() {
        try {
            const res = await apiGet(resolveFinanceSummaryEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                FinancasApp.renderResumo(res.data);
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
                FinancasApp.renderOrcamentos();
            }
        } catch (e) {
            console.error('Erro ao carregar orçamentos:', e);
        }
    },

    async loadMetas() {
        try {
            const res = await apiGet(resolveFinanceGoalsEndpoint());
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                STATE.metas = getCollectionPayload(res.data, 'metas');
                FinancasApp.renderMetas();
            }
        } catch (e) {
            console.error('Erro ao carregar metas:', e);
        }
    },

    async loadInsights() {
        try {
            const res = await apiGet(resolveFinanceInsightsEndpoint(), { mes: STATE.currentMonth, ano: STATE.currentYear });
            if (res.success !== false) {
                applyPreviewMeta(res.data?.meta);
                FinancasApp.renderInsights(getCollectionPayload(res.data, 'insights'));
            }
        } catch (e) {
            console.error('Erro ao carregar insights:', e);
        }
    },

    // ==================== RENDER: RESUMO ====================

    renderResumo(data) {
        FinancasUi.renderResumo(data);
    },

    // ==================== RENDER: ORÇAMENTOS ====================

    renderOrcamentos() {
        FinancasUi.renderOrcamentos();
    },

    // ==================== RENDER: METAS ====================

    renderMetas() {
        FinancasUi.renderMetas();
    },

    // ==================== RENDER: INSIGHTS ====================

    renderInsights(insights) {
        FinancasUi.renderInsights(insights);
    },

    // ==================== TABS ====================

    switchTab(tabName) {
        FinancasUi.switchTab(tabName);
    },

    // ==================== MODAIS ====================

    openModal(id) {
        FinancasUi.openModal(id);
    },

    closeModal(id) {
        FinancasUi.closeModal(id);
    },

    // ==================== ORÇAMENTO: CRUD ====================

    openOrcamentoModal(orcId = null) {
        return FinancasOrcamentos.openOrcamentoModal(orcId);
    },

    async handleOrcamentoSubmit(e) {
        return FinancasOrcamentos.handleOrcamentoSubmit(e);
    },

    async deleteOrcamento(id) {
        return FinancasOrcamentos.deleteOrcamento(id);
    },

    // ==================== ORÇAMENTO: SUGESTÕES ====================

    async openSugestoes() {
        return FinancasOrcamentos.openSugestoes();
    },

    renderSugestoes() {
        return FinancasOrcamentos.renderSugestoes();
    },

    async aplicarSugestoes() {
        return FinancasOrcamentos.aplicarSugestoes();
    },

    async copiarMesAnterior() {
        return FinancasOrcamentos.copiarMesAnterior();
    },

    async loadCategorySuggestion(categoriaId) {
        return FinancasOrcamentos.loadCategorySuggestion(categoriaId);
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
            document.getElementById('metaCor').value = meta.cor || '#6366f1';
            document.getElementById('metaId').value = meta.id;
            if (contaSelect) contaSelect.value = meta.conta_id || '';

            // Set active color
            document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                d.classList.toggle('active', d.dataset.color === (meta.cor || '#6366f1'));
            });
        } else {
            if (title) title.textContent = 'Nova Meta';
            form?.reset();
            document.getElementById('metaValorAtual').value = '0,00';
            document.getElementById('metaCor').value = '#6366f1';
            document.getElementById('metaId').value = '';
            if (contaSelect) contaSelect.value = '';
            document.querySelectorAll('#metaCorPicker .color-dot').forEach(d => {
                d.classList.toggle('active', d.dataset.color === '#6366f1');
            });
        }

        FinancasApp.onMetaContaChange();
        FinancasApp.updateAporteSugerido();
        FinancasApp.openModal('modalMeta');
    },

    async handleMetaSubmit(e) {
        e.preventDefault();

        const metaId = document.getElementById('metaId').value;
        const contaIdRaw = document.getElementById('metaContaId')?.value;
        const valorInicial = contaIdRaw ? 0 : Utils.parseMoney(document.getElementById('metaValorAtual').value);
        const data = {
            titulo: document.getElementById('metaTitulo').value.trim(),
            valor_alvo: Utils.parseMoney(document.getElementById('metaValorAlvo').value),
            tipo: document.getElementById('metaTipo').value,
            prioridade: document.getElementById('metaPrioridade').value,
            data_prazo: document.getElementById('metaPrazo').value || null,
            cor: document.getElementById('metaCor').value,
            conta_id: contaIdRaw ? parseInt(contaIdRaw) : null,
        };
        if (!metaId) data.valor_alocado = valorInicial;

        if (!data.titulo) return Utils.showToast('Informe o título da meta', 'error');
        if (data.valor_alvo <= 0) return Utils.showToast('Informe o valor da meta', 'error');

        try {
            let res;
            if (metaId) {
                res = await apiPut(resolveFinanceGoalEndpoint(metaId), data);
            } else {
                res = await apiPost(resolveFinanceGoalsEndpoint(), data);
            }

            // Verificar se é erro de limite do plano
            if (handleLimitError(res)) return;

            if (res.success) {
                // Processar conquistas desbloqueadas
                const responseData = res.data;
                const gamification = responseData?.gamification || res.gamification;
                processGamification(gamification);

                FinancasApp.closeModal('modalMeta');
                Utils.showToast(metaId ? 'Meta atualizada!' : 'Meta criada!', 'success');
                await FinancasApp.loadAll();
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
            const res = await apiDelete(resolveFinanceGoalEndpoint(id));
            if (res.success !== false) {
                Utils.showToast('Meta excluída', 'success');
                await FinancasApp.loadAll();
            } else {
                Utils.showToast(res.message || 'Erro ao excluir', 'error');
            }
        } catch (e) {
            Utils.showToast(requestErrorMessage(e, 'Erro ao excluir'), 'error');
        }
    },

    // ==================== METAS: APORTE ====================

    openAporteModal(metaId) {
        const meta = STATE.metas.find(m => m.id === metaId);
        if (!meta) return;
        if (preventDemoAction(meta)) return;
        Swal.fire({
            icon: 'info',
            title: 'Use lançamentos para metas',
            html: `Para movimentar a meta <strong>${Utils.escHtml(meta.titulo)}</strong>, crie uma receita, despesa ou transferência e vincule a meta no lançamento.`,
            confirmButtonText: 'Entendi'
        });
    },

    async handleAporteSubmit(e) {
        e.preventDefault();
        FinancasApp.closeModal('modalAporte');
        Utils.showToast('Para movimentar metas, use lançamentos com vínculo de meta.', 'info');
    },

    // ==================== METAS: TEMPLATES ====================

    async openTemplates() {
        FinancasApp.openModal('modalTemplates');
        const grid = document.getElementById('templatesGrid');
        grid.innerHTML = '<div class="lk-loading-state"><i data-lucide="loader-2"></i><p>Carregando templates...</p></div>';
        if (window.lucide) lucide.createIcons();

        try {
            const res = await apiGet(resolveFinanceGoalTemplatesEndpoint());
            if (res.success !== false && res.data?.length) {
                const _faToLucide = {
                    'fa-arrow-down': 'arrow-down', 'fa-arrow-up': 'arrow-up', 'fa-calendar-alt': 'calendar-days',
                    'fa-check': 'check', 'fa-check-circle': 'circle-check', 'fa-chevron-right': 'chevron-right',
                    'fa-credit-card': 'credit-card', 'fa-exclamation-circle': 'circle-alert',
                    'fa-exclamation-triangle': 'triangle-alert', 'fa-eye': 'eye', 'fa-eye-slash': 'eye-off',
                    'fa-info-circle': 'info', 'fa-pencil': 'pencil', 'fa-pencil-alt': 'pencil',
                    'fa-plus': 'plus', 'fa-plus-circle': 'circle-plus', 'fa-redo': 'refresh-cw',
                    'fa-shopping-cart': 'shopping-cart', 'fa-sort': 'arrow-up-down', 'fa-sort-down': 'arrow-down',
                    'fa-sort-up': 'arrow-up', 'fa-spinner': 'loader-2', 'fa-times': 'x', 'fa-trash': 'trash-2',
                    'fa-undo': 'undo-2', 'fa-university': 'landmark', 'fa-wallet': 'wallet'
                };
                grid.innerHTML = res.data.map(tmpl => {
                    // icone agora vem como nome Lucide direto (ex: 'shield', 'smartphone')
                    const iconeHtml = tmpl.icone
                        ? `<i data-lucide="${tmpl.icone}" style="color:${getCategoryIconColor(tmpl.icone)}"></i>`
                        : '<i data-lucide="target" style="color:#ef4444"></i>';
                    return `
                    <div class="template-card" onclick="financasManager.useTemplate(${JSON.stringify(tmpl).replace(/"/g, '&quot;')})">
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
        } catch {
            grid.innerHTML = '<div class="fin-empty-state"><p>Erro ao carregar templates.</p></div>';
        }
    },

    useTemplate(tmpl) {
        FinancasApp.closeModal('modalTemplates');
        FinancasApp.openMetaModal();

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
            FinancasApp.updateAporteSugerido();
        }, 100);
    },

    updateAporteSugerido() {
        const hint = document.getElementById('metaAporteSugerido');
        if (!hint) return;

        const valorAlvo = Utils.parseMoney(document.getElementById('metaValorAlvo')?.value || '0');
        const prazo = document.getElementById('metaPrazo')?.value;

        // Determinar valor atual: se conta vinculada, usar saldo da conta
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

// Register on Modules for cross-module access
Modules.App = FinancasApp;




