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
        Swal.fire({
            icon: 'warning',
            title: '🚀 Limite Atingido',
            html: `
                <p>${msg}</p>
                <p style="margin-top: 12px; color: #6c757d; font-size: 0.9em;">
                    Desbloqueie orçamentos ilimitados com o plano Pro!
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
                OrcamentoApp.renderInsights(getCollectionPayload(res.data, 'insights'));
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
        Utils.setText('totalOrcado', Utils.formatCurrency(totalOrcado));
        Utils.setText('totalGasto', Utils.formatCurrency(totalGasto));
        Utils.setText('totalDisponivel', Utils.formatCurrency(totalOrcado - totalGasto));
    },

    // ==================== RENDER: ORÇAMENTOS ====================

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

        grid.innerHTML = STATE.orcamentos.map(orc => {
            const pct = orc.percentual || 0;
            const isDemo = isDemoItem(orc);
            const statusClass = pct >= 100 ? 'over' : pct >= 80 ? 'warn' : 'ok';
            const catNome = orc.categoria?.nome || orc.categoria_nome || 'Categoria';
            const catIcone = orc.categoria?.icone || 'tag';
            const gasto = orc.gasto_real || 0;
            const limite = orc.valor_limite || 0;
            const disponivel = limite - gasto;
            const rolloverTag = orc.rollover && orc.rollover_valor > 0
                ? `<span class="orc-card__badge" title="Inclui R$ ${Utils.formatNumber(orc.rollover_valor)} do mês anterior">+${Utils.formatNumber(orc.rollover_valor)} rollover</span>`
                : '';
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
                        <span class="orc-card__limit">de ${Utils.formatCurrency(limite)}</span>
                    </div>
                </div>
                <div class="orc-card__footer">
                    <span class="orc-card__pct ${statusClass}">${pct.toFixed(0)}%</span>
                    <span class="orc-card__remaining ${disponivel < 0 ? 'orc-card__remaining--negative' : ''}">
                        ${disponivel >= 0 ? 'Resta' : 'Excedido'} ${Utils.formatCurrency(Math.abs(disponivel))}
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

        if (!insights.length) {
            if (section) section.style.display = 'none';
            return;
        }

        if (section) section.style.display = '';

        grid.innerHTML = insights.map(insight => {
            const icon = Utils.getInsightIcon(insight.tipo);
            const level = insight.nivel || 'info';
            return `
            <div class="orc-insight-card surface-card surface-card--interactive ${level}" data-aos="fade-up">
                <div class="orc-insight-icon ${level}">
                    <i data-lucide="${icon}"></i>
                </div>
                <div class="orc-insight-content">
                    <span class="orc-insight-title">${Utils.escHtml(insight.titulo || '')}</span>
                    <p class="orc-insight-text">${Utils.escHtml(insight.mensagem || '')}</p>
                </div>
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
                ? `<span class="sugestao-economia">economia de ${Utils.formatCurrency(sug.economia_sugerida)}/mês</span>`
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
