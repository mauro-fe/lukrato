/**
 * ============================================================================
 * LUKRATO — Lançamentos / Entry Point
 * ============================================================================
 * Main orchestrator: imports all sub-modules, defines the API layer and
 * EventListeners, wires everything together and bootstraps on DOMContentLoaded.
 * ============================================================================
 */

import { CONFIG, DOM, initDOM, STATE, Utils, MoneyMask, Notifications, Modules } from './state.js';
import { TableManager } from './table.js';
import { MobileCards } from './mobile.js';
import { OptionsManager, ModalManager } from './modal.js';
import { CustomSelectManager } from './custom-select.js';
import {
    ExportManager,
    FilterBadges,
    DataManager,
    ParcelamentoGrouper,
    FaturaDetalhes
} from './features.js';

// ─── API ─────────────────────────────────────────────────────────────────────

export const API = {
    fetchJsonList: async (url) => {
        try {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return [];
            const body = await res.json().catch(() => null);
            return Utils.normalizeDataList(body);
        } catch {
            return [];
        }
    },

    fetchLancamentos: async (
        { month, tipo = '', categoria = '', conta = '', limit, startDate = '', endDate = '', search = '', status = '' },
        { signal } = {}
    ) => {
        const qs = API.buildQuery({ month, tipo, categoria, conta, limit, startDate, endDate, search, status });

        try {
            const res = await fetch(`${CONFIG.ENDPOINT}?${qs.toString()}`, {
                headers: { 'Accept': 'application/json' },
                signal
            });

            if (res.status === 204 || res.status === 404) return [];
            if (!res.ok) throw new Error('Falha ao carregar lançamentos.');

            const data = await res.json().catch(() => null);
            if (Array.isArray(data)) return data;
            if (data && Array.isArray(data.data)) return data.data;
            return [];
        } catch (error) {
            if (error?.name === 'AbortError') throw error;
            throw error;
        }
    },

    buildQuery: ({ month, tipo, categoria, conta, limit, startDate, endDate, search, status }) => {
        const qs = new URLSearchParams();
        if (month) qs.set('month', month);
        if (tipo) qs.set('tipo', tipo);
        if (categoria !== undefined && categoria !== null && categoria !== '') {
            qs.set('categoria_id', categoria);
        }
        if (conta !== undefined && conta !== null && conta !== '') {
            qs.set('account_id', conta);
        }
        if (limit !== undefined && limit !== null) {
            qs.set('limit', String(limit));
        }
        if (startDate) qs.set('start_date', startDate);
        if (endDate) qs.set('end_date', endDate);
        if (search) qs.set('q', search);
        if (status) qs.set('status', status);
        return qs;
    },

    deleteOne: async (id, scope = 'single') => {
        try {
            const token = Utils.getCSRFToken();
            const url = `${CONFIG.ENDPOINT}/${encodeURIComponent(id)}${scope !== 'single' ? `?scope=${scope}` : ''}`;
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            return res.ok;
        } catch {
            return false;
        }
    },

    bulkDelete: async (ids) => {
        try {
            const token = Utils.getCSRFToken();
            const payload = {
                ids,
                _token: token,
                csrf_token: token
            };

            const res = await fetch(`${CONFIG.ENDPOINT}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });

            if (res.ok) return true;
        } catch { /* fall through to fallback */ }

        // Fallback: deletar individualmente
        const results = await Promise.all(ids.map(API.deleteOne));
        return results.every(Boolean);
    },

    updateLancamento: async (id, payload) => {
        const token = Utils.getCSRFToken();
        return fetch(`${CONFIG.ENDPOINT}/${encodeURIComponent(id)}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        });
    },

    exportLancamentos: async (params, format) => {
        const qs = API.buildQuery(params);
        qs.set('format', format);

        const res = await fetch(`${CONFIG.EXPORT_ENDPOINT}?${qs.toString()}`, {
            credentials: 'include'
        });

        if (!res.ok) {
            let message = 'Falha ao exportar lançamentos.';
            const maybeJson = await res.json().catch(() => null);
            if (maybeJson?.message) message = maybeJson.message;
            throw new Error(message);
        }

        return res;
    }
};

// Register API on shared Modules
Modules.API = API;

// ─── EVENT LISTENERS ─────────────────────────────────────────────────────────

const EventListeners = {
    init() {
        // Money mask on edit modal value fields
        MoneyMask.bind(DOM.inputLancValor);
        MoneyMask.bind(DOM.inputTransValor);

        // Tipo de lançamento mudou — atualizar categorias
        DOM.selectLancTipo?.addEventListener('change', () => {
            OptionsManager.populateCategoriaSelect(
                DOM.selectLancCategoria,
                DOM.selectLancTipo.value,
                DOM.selectLancCategoria?.value || ''
            );
        });

        // Modal fechou — limpar dados
        DOM.modalEditLancEl?.addEventListener('hidden.bs.modal', () => {
            STATE.editingLancamentoId = null;
            DOM.formLanc?.reset?.();
            ModalManager.clearLancAlert();
        });

        // Submit do formulário de edição
        DOM.formLanc?.addEventListener('submit', ModalManager.submitEditForm);

        // Submit do formulário de edição de transferência
        DOM.formTrans?.addEventListener('submit', ModalManager.submitTransForm);

        // Botão editar do modal de visualização
        DOM.btnEditFromView?.addEventListener('click', () => {
            if (!STATE.viewingLancamento) return;
            const lancamento = STATE.viewingLancamento;

            // Fechar modal de visualização
            if (STATE.modalViewLanc) {
                STATE.modalViewLanc.hide();
            }

            // Abrir modal de edição após um pequeno delay para evitar conflitos
            setTimeout(() => {
                ModalManager.openEditLancamento(lancamento);
            }, 300);
        });

        // Modal de visualização fechou — limpar dados
        DOM.modalViewLancEl?.addEventListener('hidden.bs.modal', () => {
            STATE.viewingLancamento = null;
        });

        // Filtro de busca por texto
        DOM.filtroTexto?.addEventListener('input', () => {
            DataManager.load();
        });

        // Botão de limpar filtros
        DOM.btnLimparFiltros?.addEventListener('click', () => {
            if (DOM.selectTipo) DOM.selectTipo.value = '';
            if (DOM.selectCategoria) DOM.selectCategoria.value = '';
            if (DOM.selectConta) DOM.selectConta.value = '';
            if (DOM.filtroTexto) DOM.filtroTexto.value = '';
            if (DOM.filtroStatus) DOM.filtroStatus.value = '';
            if (DOM.filtroDataInicio) DOM.filtroDataInicio.value = '';
            if (DOM.filtroDataFim) DOM.filtroDataFim.value = '';
            updateChipActiveStates();
            document.dispatchEvent(new CustomEvent('lk:custom-select-sync'));
            DataManager.load();
        });

        // Helper: toggle .active class on chip-selects based on value
        function updateChipActiveStates() {
            document.querySelectorAll('.lk-filter-chip-select select').forEach(sel => {
                sel.closest('.lk-filter-chip-select')?.classList.toggle('active', sel.value !== '');
            });
        }

        // Filtros automáticos ao mudar select (also update chip active state)
        const chipSelects = [DOM.selectTipo, DOM.selectCategoria, DOM.selectConta, DOM.filtroStatus];
        chipSelects.forEach(sel => {
            sel?.addEventListener('change', () => {
                updateChipActiveStates();
                DataManager.load();
            });
        });

        const loadWhenPeriodComplete = () => {
            const startDate = Utils.getTrimmedDateValue(DOM.filtroDataInicio);
            const endDate = Utils.getTrimmedDateValue(DOM.filtroDataFim);

            if ((startDate && endDate) || (!startDate && !endDate)) {
                DataManager.load();
            } else if (Modules.ListContext?.update) {
                Modules.ListContext.update();
            }
        };

        DOM.filtroDataInicio?.addEventListener('change', loadWhenPeriodComplete);
        DOM.filtroDataFim?.addEventListener('change', loadWhenPeriodComplete);

        DOM.periodPresetButtons?.forEach(btn => {
            btn.addEventListener('click', () => {
                const preset = btn.dataset.periodPreset;
                if (!preset) return;

                if (preset === 'today') {
                    const today = Utils.getTodayYMD();
                    if (DOM.filtroDataInicio) DOM.filtroDataInicio.value = today;
                    if (DOM.filtroDataFim) DOM.filtroDataFim.value = today;
                } else {
                    const range = Utils.getRangeFromToday(Number(preset));
                    if (DOM.filtroDataInicio) DOM.filtroDataInicio.value = range.startDate;
                    if (DOM.filtroDataFim) DOM.filtroDataFim.value = range.endDate;
                }

                DataManager.load();
            });
        });

        DOM.btnUsarMesDoTopo?.addEventListener('click', () => {
            if (DOM.filtroDataInicio) DOM.filtroDataInicio.value = '';
            if (DOM.filtroDataFim) DOM.filtroDataFim.value = '';
            DataManager.load();
        });

        // Botão de exportar
        DOM.btnExportar?.addEventListener('click', () => ExportManager.export());

        // Botão de excluir selecionados
        DOM.btnExcluirSel?.addEventListener('click', DataManager.bulkDelete);

        // Eventos globais do sistema
        document.addEventListener('lukrato:month-changed', () => {
            const hasCustomRange = Utils.getTrimmedDateValue(DOM.filtroDataInicio) && Utils.getTrimmedDateValue(DOM.filtroDataFim);
            if (hasCustomRange) {
                Modules.ListContext?.update?.();
                return;
            }
            DataManager.load({ immediate: true });
        });
        document.addEventListener('lukrato:export-click', () => ExportManager.export());

        document.addEventListener('lukrato:data-changed', (e) => {
            const res = e.detail?.resource;
            if (!res || res === 'transactions') {
                const preservePage = e.detail?.action !== 'create';
                DataManager.load({ immediate: true, preservePage });
            }
            if (res === 'categorias' || res === 'contas') OptionsManager.loadFilterOptions();
        });

        // Cliques nos cards (mobile)
        DOM.lanCards?.addEventListener('click', MobileCards.handleClick);

        // Paginação (mobile)
        DOM.lanPagerFirst?.addEventListener('click', () => MobileCards.firstPage());
        DOM.lanPagerPrev?.addEventListener('click', () => MobileCards.prevPage());
        DOM.lanPagerNext?.addEventListener('click', () => MobileCards.nextPage());
        DOM.lanPagerLast?.addEventListener('click', () => MobileCards.lastPage());
    }
};

// ─── INIT ────────────────────────────────────────────────────────────────────

const init = async () => {
    // Populate DOM refs
    initDOM();
    CustomSelectManager.init();

    // Inicializar tabela HTML
    TableManager.init();

    // Instalar sistema de agrupamento de parcelamentos
    ParcelamentoGrouper.installInterceptor();
    ParcelamentoGrouper.installListeners();

    // Instalar sistema de detalhes de fatura
    FaturaDetalhes.installListeners();

    // Inicializar componentes
    ExportManager.initDefaults();
    EventListeners.init();

    // Bind view buttons
    document.getElementById('btnNovoLancamento')?.addEventListener('click', () => {
        if (window.lancamentoGlobalManager) window.lancamentoGlobalManager.openModal();
    });
    DOM.btnRefreshPage?.addEventListener('click', () => DataManager.load({
        immediate: true,
        preservePage: true,
        showToast: true
    }));

    // Carregar dados iniciais
    await OptionsManager.loadFilterOptions();
    CustomSelectManager.syncAll();
    await DataManager.load();
};

// Expor funções globais necessárias
window.refreshLancamentos = () => DataManager.load({ immediate: true, preservePage: true });

// Iniciar aplicação
document.addEventListener('DOMContentLoaded', () => init());
