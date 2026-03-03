/**
 * ============================================================================
 * LUKRATO — Relatórios / Index (Entry Point)
 * ============================================================================
 * Imports every relatórios module, bootstraps the page, and exposes a
 * backward-compatible window.ReportsAPI proxy for PHP views.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Modules } from './state.js';
import { ChartManager } from './charts.js';
import {
    API, UI,
    renderReport, handleExport,
    syncPickerMode, handleTabChange, handleTypeChange,
    handleAccountChange, onExternalMonthChange, onExternalYearChange,
    refreshActiveSection
} from './app.js';

// Previne inicialização dupla
if (window.__LK_REPORTS_LOADED__) {
    // Already loaded — skip
} else {
    window.__LK_REPORTS_LOADED__ = true;

    // ── Initialize ───────────────────────────────────────────────────────────

    async function initialize() {

        ChartManager.setupDefaults();

        // Carregar contas
        STATE.accounts = await API.fetchAccounts();
        const accountSelect = document.getElementById('accountFilter');
        if (accountSelect) {
            STATE.accounts.forEach(acc => {
                const option = document.createElement('option');
                option.value = acc.id;
                option.textContent = acc.name;
                accountSelect.appendChild(option);
            });
        }

        // Event listeners
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => handleTabChange(btn.dataset.view));
        });

        // === Section tabs (Relatórios / Insights / Comparativos) ===
        const switchSection = (section) => {
            document.querySelectorAll('.rel-section-tab').forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('.rel-section-panel').forEach(p => p.classList.remove('active'));

            const activeTab = document.querySelector(`.rel-section-tab[data-section="${section}"]`);
            if (activeTab) {
                activeTab.classList.add('active');
                activeTab.setAttribute('aria-selected', 'true');
            }
            const panel = document.getElementById('section-' + section);
            if (panel) {
                panel.classList.add('active');
            }

            localStorage.setItem('rel_active_section', section);

            // Carregar dados da seção PRO quando ativada
            refreshActiveSection(section);

            if (window.lucide) {
                window.lucide.createIcons();
            }
        };

        const proLockedSections = ['insights', 'comparativos'];

        document.querySelectorAll('.rel-section-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const section = tab.dataset.section;
                if (!window.IS_PRO && proLockedSections.includes(section)) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Recurso Premium',
                        html: 'Esta funcionalidade é exclusiva do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!',
                        confirmButtonText: '<i class="lucide-crown" style="margin-right:6px"></i> Fazer Upgrade',
                        showCancelButton: true,
                        cancelButtonText: 'Agora não',
                        confirmButtonColor: '#f59e0b',
                        cancelButtonColor: '#64748b'
                    }).then(result => {
                        if (result.isConfirmed) {
                            window.location.href = (window.BASE_URL || '/') + 'billing';
                        }
                    });
                    return;
                }
                switchSection(section);
            });
        });

        // Restaurar última aba selecionada
        const savedSection = localStorage.getItem('rel_active_section');
        if (savedSection && document.getElementById('section-' + savedSection)) {
            // Não restaurar aba PRO-locked para free users
            if (!window.IS_PRO && proLockedSections.includes(savedSection)) {
                switchSection('relatorios');
            } else {
                switchSection(savedSection);
            }
        }

        const reportType = document.getElementById('reportType');
        if (reportType) {
            reportType.addEventListener('change', (e) => handleTypeChange(e.target.value));
        }

        if (accountSelect) {
            accountSelect.addEventListener('change', (e) => handleAccountChange(e.target.value));
        }

        // Botão Limpar Filtros
        const btnLimparRel = document.getElementById('btnLimparFiltrosRel');
        const clearFiltersWrapper = document.getElementById('clearFiltersWrapper');

        const showClearBtn = () => {
            if (!clearFiltersWrapper) return;
            const hasTypeFilter = reportType && reportType.selectedIndex > 0;
            const hasAccountFilter = accountSelect && accountSelect.value !== '';
            clearFiltersWrapper.style.display = (hasTypeFilter || hasAccountFilter) ? 'flex' : 'none';
        };

        if (reportType) reportType.addEventListener('change', showClearBtn);
        if (accountSelect) accountSelect.addEventListener('change', showClearBtn);

        if (btnLimparRel) {
            btnLimparRel.addEventListener('click', () => {
                if (reportType) { reportType.selectedIndex = 0; handleTypeChange(reportType.value); }
                if (accountSelect) { accountSelect.value = ''; handleAccountChange(''); }
                showClearBtn();
            });
        }

        document.addEventListener('lukrato:theme-changed', () => {
            ChartManager.setupDefaults();
            renderReport();
        });

        const headerMonth = window.LukratoHeader?.getMonth?.();
        if (headerMonth) {
            STATE.currentMonth = headerMonth;
        }

        document.addEventListener('lukrato:month-changed', onExternalMonthChange);
        document.addEventListener('lukrato:year-changed', onExternalYearChange);

        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', handleExport);
        }

        // Event delegation: open card detail modal
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-action="open-card-detail"]');
            if (!trigger) return;
            e.stopPropagation();

            const cardId = parseInt(trigger.dataset.cardId, 10);
            const cardNome = trigger.dataset.cardNome || '';
            const cardCor = trigger.dataset.cardCor || '#E67E22';
            const cardMonth = trigger.dataset.cardMonth || STATE.currentMonth;

            if (!cardId) return;

            if (window.LK_CardDetail?.open) {
                window.LK_CardDetail.open(cardId, cardNome, cardCor, cardMonth);
            } else {
                console.error('[Relatórios] LK_CardDetail module not loaded');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'error',
                        title: 'Módulo de detalhes não carregado',
                        text: 'Recarregue a página.',
                        showConfirmButton: false, timer: 3000
                    });
                }
            }
        });

        // Renderização inicial
        syncPickerMode();
        UI.updateMonthLabel();
        UI.updateControls();
        renderReport();

    }

    // Iniciar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    // ── API Global ───────────────────────────────────────────────────────────

    window.ReportsAPI = {
        setMonth: (yearMonth) => {
            if (!/^\d{4}-\d{2}$/.test(yearMonth)) return;
            STATE.currentMonth = yearMonth;
            UI.updateMonthLabel();
            renderReport();
        },
        setView: (view) => {
            if (Object.values(CONFIG.VIEWS).includes(view)) {
                handleTabChange(view);
            }
        },
        refresh: () => renderReport(),
        getState: () => ({ ...STATE })
    };
}
