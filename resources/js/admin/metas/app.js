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
import { createMetasUi } from './app-ui.js';

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
                context: 'metas',
                message: msg || 'Este recurso está disponível no plano Pro.',
            }).catch(() => { /* ignore */ });
            return true;
        }

        if (window.LKFeedback?.upgradePrompt) {
            window.LKFeedback.upgradePrompt({
                context: 'metas',
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

// ── Gamification helper ────────────────────────────────────────

function processGamification(gamification) {
    if (!gamification) return;
    const achievements = gamification.achievements || [];
    if (achievements.length > 0 && typeof window.notifyMultipleAchievements === 'function') {
        window.notifyMultipleAchievements(achievements);
    }
}

const MetasUi = createMetasUi({
    STATE,
    Utils,
    isDemoItem,
});

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
            window.LK?.modalSystem?.prepareOverlay(overlay, { scope: 'page' });
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
        MetasApp.syncMetaAllocationField();
    },

    setupMoneyInputs() {
        MetasUi.setupMoneyInputs();
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
        MetasUi.onMetaContaChange();
    },

    syncMetaAllocationField() {
        MetasUi.syncMetaAllocationField();
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
        MetasUi.renderResumo(data);
    },

    getFilteredMetas() {
        return MetasUi.getFilteredMetas();
    },

    compareMetas(a, b, sort) {
        return MetasUi.compareMetas(a, b, sort);
    },

    renderFocusPanel() {
        MetasUi.renderFocusPanel();
    },

    buildInsights() {
        return MetasUi.buildInsights();
    },

    renderInsights() {
        MetasUi.renderInsights();
    },

    // ==================== RENDER: METAS ====================

    renderMetas() {
        MetasUi.renderMetas();
    },

    // ==================== MODAIS ====================

    openModal(id) {
        MetasUi.openModal(id);
    },

    closeModal(id) {
        MetasUi.closeModal(id);
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
        Swal.fire({
            icon: 'info',
            title: 'Use lançamentos para metas',
            html: `Para movimentar a meta <strong>${Utils.escHtml(meta.titulo)}</strong>, crie uma receita, despesa ou transferência e vincule a meta no lançamento.`,
            confirmButtonText: 'Entendi'
        });
    },

    async handleAporteSubmit(e) {
        e.preventDefault();
        MetasApp.closeModal('modalAporte');
        Utils.showToast('Para movimentar metas, use lançamentos com vínculo de meta.', 'info');
        return;

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
        } catch {
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
        MetasUi.updateAporteSugerido();
    },
};


