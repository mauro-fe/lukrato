<?php

use Application\Lib\Auth;

$headerMesUser = $currentUser ?? Auth::user();
$showHeaderMesCTA = !($headerMesUser && method_exists($headerMesUser, 'isPro') && $headerMesUser->isPro());

?>
<style>
    /* Header / Month selector */
    .dash-lk-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--spacing-3);
        margin: 0 auto;
        padding: var(--spacing-4);
        width: 100%;
    }

    .dash-lk-header .header-left {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 1 1 auto;
    }

    .dash-lk-header .header-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex: 0 0 auto;
        gap: var(--spacing-2);
        min-width: 220px;
    }

    .dash-lk-header .month-selector {
        display: flex;
        align-items: center;
        gap: var(--spacing-3);
        flex-wrap: wrap;
    }

    .lk-period,
    .lk-year-picker {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
    }

    .dash-lk-header .month-nav-btn,
    .dash-lk-header .month-dropdown-btn {
        background: none;
        border: 0;
        cursor: pointer;
        border-radius: var(--radius-md);
        transition: all var(--transition-normal);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-text);
        font-family: var(--font-primary);
        position: relative;
    }

    .dash-lk-header .month-nav-btn {
        width: 40px;
        height: 40px;
        color: var(--color-text-muted);
    }

    .dash-lk-header .month-nav-btn:hover {
        background-color: var(--color-primary);
        color: white;
        transform: scale(1.05);
        box-shadow: var(--shadow-md);
    }

    .dash-lk-header .month-nav-btn:active {
        transform: scale(0.95);
    }

    .dash-lk-header .month-dropdown-btn {
        gap: var(--spacing-2);
        font-weight: 600;
        font-size: var(--font-size-base);
        min-width: 180px;
        padding: var(--spacing-3) var(--spacing-4);
        color: var(--color-primary);
        position: relative;
    }

    .dash-lk-header .month-dropdown-btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--color-primary);
        opacity: 0;
        border-radius: var(--radius-md);
        transition: opacity var(--transition-fast);
    }

    .dash-lk-header .month-dropdown-btn:hover::before {
        opacity: 0.08;
    }

    .dash-lk-header .month-dropdown-btn:hover {
        transform: translateY(-1px);
    }

    .dash-lk-header .month-dropdown-btn i {
        transition: transform var(--transition-normal);
    }

    .dash-lk-header .month-dropdown-btn:hover i {
        transform: translateY(2px);
    }

    .lk-pro-cta {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-2);
        padding: var(--spacing-3) var(--spacing-5);
        border-radius: var(--radius-full, 999px);
        background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 60%, var(--color-secondary) 40%));
        color: #fff;
        font-weight: 600;
        font-size: var(--font-size-sm);
        text-decoration: none;
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        box-shadow: var(--shadow-md);
        white-space: nowrap;
    }

    .lk-pro-cta:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-lg);
        color: #fff;
    }

    .lk-pro-cta i {
        font-size: var(--font-size-base);
    }

    /* Month dropdown */
    .dash-lk-header .month-display {
        position: relative;
    }

    .dash-lk-header .month-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%) translateY(-8px);
        width: min(280px, calc(100vw - 48px));
        background: var(--color-surface);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        z-index: 1000;
        max-height: 380px;
        overflow-y: auto;
        overflow-x: hidden;
        opacity: 0;
        visibility: hidden;
        transition: all var(--transition-normal);
        padding: var(--spacing-3);
        backdrop-filter: var(--glass-backdrop);
    }

    .dash-lk-header .month-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    /* Custom scrollbar */
    .dash-lk-header .month-dropdown::-webkit-scrollbar {
        width: 6px;
    }

    .dash-lk-header .month-dropdown::-webkit-scrollbar-track {
        background: var(--glass-bg);
        border-radius: var(--radius-sm);
    }

    .dash-lk-header .month-dropdown::-webkit-scrollbar-thumb {
        background: var(--color-primary);
        border-radius: var(--radius-sm);
    }

    .dash-lk-header .month-dropdown::-webkit-scrollbar-thumb:hover {
        background: var(--color-secondary);
    }

    .dash-lk-header .month-dropdown .year-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--spacing-2);
        padding: var(--spacing-2) 0 var(--spacing-3);
    }

    .dash-lk-header .month-dropdown .year-label {
        grid-column: 1 / -1;
        font-weight: 700;
        font-size: var(--font-size-lg);
        color: var(--color-text);
        padding: var(--spacing-3) var(--spacing-2);
        text-align: center;
        background: var(--glass-bg);
        border-radius: var(--radius-md);
        margin-bottom: var(--spacing-2);
    }

    .lk-period {
        display: flex;
    }

    body.show-year-picker .lk-period {
        display: none;
    }

    #yearPicker {
        display: none;
        align-items: center;
        gap: var(--spacing-2);
    }

    body.show-year-picker #yearPicker {
        display: flex;
    }

    #yearPicker .year-btn {
        min-width: 140px;
    }

    #yearPicker .month-nav-btn {
        width: 36px;
        height: 36px;
    }

    #currentYearText {
        font-weight: 600;
    }

    .dash-lk-header .month-dropdown .m-btn {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        color: var(--color-text);
        padding: var(--spacing-3) var(--spacing-2);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all var(--transition-normal);
        text-align: center;
        font-size: var(--font-size-sm);
        font-weight: 500;
        font-family: var(--font-primary);
        position: relative;
        overflow: hidden;
    }

    .dash-lk-header .month-dropdown .m-btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        opacity: 0;
        transition: opacity var(--transition-fast);
    }

    .dash-lk-header .month-dropdown .m-btn:hover::before {
        opacity: 0.1;
    }

    .dash-lk-header .month-dropdown .m-btn:hover {
        border-color: var(--color-primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .dash-lk-header .month-dropdown .m-btn:active {
        transform: translateY(0);
    }

    .dash-lk-header .month-dropdown .m-btn.is-current {
        background: var(--color-primary);
        border-color: var(--color-primary);
        color: white;
        font-weight: 600;
        box-shadow: var(--shadow-md);
    }

    .dash-lk-header .month-dropdown .m-btn.is-current::before {
        display: none;
    }

    .dash-lk-header .month-dropdown .m-btn.is-current:hover {
        background: var(--color-secondary);
        border-color: var(--color-secondary);
        transform: translateY(-2px) scale(1.02);
    }

    /* Animação de entrada dos botões */
    .dash-lk-header .month-dropdown.active .m-btn {
        animation: slideInMonth 0.3s ease forwards;
        opacity: 0;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(1) {
        animation-delay: 0.02s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(2) {
        animation-delay: 0.04s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(3) {
        animation-delay: 0.06s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(4) {
        animation-delay: 0.08s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(5) {
        animation-delay: 0.10s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(6) {
        animation-delay: 0.12s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(7) {
        animation-delay: 0.14s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(8) {
        animation-delay: 0.16s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(9) {
        animation-delay: 0.18s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(10) {
        animation-delay: 0.20s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(11) {
        animation-delay: 0.22s;
    }

    .dash-lk-header .month-dropdown.active .m-btn:nth-child(12) {
        animation-delay: 0.24s;
    }

    @keyframes slideInMonth {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsividade */
    @media (max-width: 640px) {
        .dashboard-page .dash-lk-header {
            margin-top: var(--spacing-3);
            padding: var(--spacing-2);
            flex-direction: column;
            align-items: stretch;
        }

        .lk-period {
            padding: var(--spacing-1);
        }

        .dash-lk-header .month-nav-btn {
            width: 36px;
            height: 36px;
        }

        .dash-lk-header .month-dropdown-btn {
            min-width: 140px;
            padding: var(--spacing-2) var(--spacing-3);
            font-size: var(--font-size-sm);
        }

        .dash-lk-header .month-dropdown {
            width: calc(100vw - 32px);
        }

        .dash-lk-header .header-left,
        .dash-lk-header .header-actions {
            width: 100%;
            justify-content: center;
        }

        .lk-pro-cta {
            width: min(320px, 100%);
            justify-content: center;
        }
    }

    @media (max-width: 370px) {
        .month-dropdown-btn {
            font-size: .7rem !important;
        }

        .lk-period {
            gap: 1px;
            padding: 0;
        }

        .month-nav-btn {
            font-size: .6rem !important;
        }
    }
</style>

<header class="dash-lk-header" data-aos="fade-up">
    <div class="header-left">
        <div class="month-selector">
            <div class="lk-period">
                <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mês anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="month-dropdown-btn" id="monthDropdownBtn" type="button" data-bs-toggle="modal"
                    data-bs-target="#monthModal" aria-haspopup="true" aria-expanded="false">
                    <span id="currentMonthText">Carregando...</span>
                    <i class="fas fa-chevron-down"></i>
                </button>

                <div class="month-display">
                    <div class="month-dropdown" id="monthDropdown" role="menu"></div>
                </div>

                <button class="month-nav-btn" id="nextMonth" type="button" aria-label="Próximo mês">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="lk-year-picker" id="yearPicker" aria-hidden="true">
                <button class="month-nav-btn" id="prevYearBtn" type="button" aria-label="Ano anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="month-dropdown-btn year-btn" id="yearDropdownBtn" type="button" aria-haspopup="true"
                    aria-expanded="false">
                    <span id="currentYearText"><?= date('Y') ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <button class="month-nav-btn" id="nextYearBtn" type="button" aria-label="Próximo ano">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

</header>

<script>
    // public/assets/js/month-picker.js
    (() => {
        'use strict';
        if (window.__LK_MONTH_PICKER__) return;
        window.__LK_MONTH_PICKER__ = true;

        // ---- elementos do header/modal (use os mesmos IDs já existentes no seu HTML)
        const elText = document.getElementById('currentMonthText');
        const btnPrev = document.getElementById('prevMonth');
        const btnNext = document.getElementById('nextMonth');
        const btnOpen = document.getElementById('monthDropdownBtn');
        const modalEl = document.getElementById('monthModal');

        const mpYearLabel = document.getElementById('mpYearLabel');
        const mpPrevYear = document.getElementById('mpPrevYear');
        const mpNextYear = document.getElementById('mpNextYear');
        const mpGrid = document.getElementById('mpGrid');
        const mpTodayBtn = document.getElementById('mpTodayBtn');
        const mpInput = document.getElementById('mpInputMonth');
        const yearPickerWrap = document.getElementById('yearPicker');
        const btnPrevYear = document.getElementById('prevYearBtn');
        const btnNextYear = document.getElementById('nextYearBtn');
        const btnYearDropdown = document.getElementById('yearDropdownBtn');
        const yearTextEl = document.getElementById('currentYearText');
        const yearModalEl = document.getElementById('yearModal');
        const yearGrid = document.getElementById('yearGrid');
        const yearInput = document.getElementById('yearInput');
        const yearApplyBtn = document.getElementById('yearApplyBtn');

        // ---- helpers
        const STORAGE_KEY = 'lukrato.month.dashboard';
        const YEAR_STORAGE_KEY = 'lukrato.year.dashboard';
        const SHORT = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const toYM = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        const monthLabel = (ym) => {
            const [y, m] = ym.split('-').map(Number);
            return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', {
                month: 'long',
                year: 'numeric'
            });
        };
        const clampYear = (year) => {
            if (!Number.isFinite(year)) return null;
            return Math.min(2100, Math.max(2000, Math.round(year)));
        };
        let pickerMode = document.body?.classList.contains('show-year-picker') ? 'year' : 'month';
        const setPickerModeDisplay = (mode = 'month') => {
            const normalized = mode === 'year' ? 'year' : 'month';
            pickerMode = normalized;
            const showYear = normalized === 'year';
            if (document.body) {
                document.body.classList.toggle('show-year-picker', showYear);
            }
            if (yearPickerWrap) {
                yearPickerWrap.setAttribute('aria-hidden', showYear ? 'false' : 'true');
            }
            return showYear;
        };

        // ---- estado
        let state = sessionStorage.getItem(STORAGE_KEY) || toYM(new Date());
        let modalYear = Number(state.split('-')[0]) || (new Date()).getFullYear();
        let yearState = clampYear(Number(sessionStorage.getItem(YEAR_STORAGE_KEY) ?? modalYear)) ?? modalYear;

        // ---- bootstrap modal
        const ensureMonthModal = () => {
            if (!modalEl || !window.bootstrap?.Modal) return null;
            if (modalEl.parentElement && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
            return window.bootstrap.Modal.getOrCreateInstance(modalEl);
        };
        const cleanupModalArtifacts = () => {
            if (document.querySelector('.modal.show')) {
                return;
            }
            document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
        };
        const openMonthModal = () => ensureMonthModal()?.show();
        const closeMonthModal = () => {
            const instance = ensureMonthModal();
            if (!instance) return;
            instance.hide();
            setTimeout(cleanupModalArtifacts, 200);
        };
        let yearModalInstance = null;
        let yearModalFocus = yearState;
        const ensureYearModal = () => {
            if (!yearModalEl || !window.bootstrap?.Modal) return null;
            if (yearModalEl.parentElement && yearModalEl.parentElement !== document.body) {
                document.body.appendChild(yearModalEl);
            }
            yearModalInstance = window.bootstrap.Modal.getOrCreateInstance(yearModalEl);
            return yearModalInstance;
        };
        const openYearModal = () => {
            yearModalFocus = yearState;
            buildYearGrid();
            if (yearInput) yearInput.value = String(yearState);
            ensureYearModal()?.show();
        };
        const closeYearModal = () => {
            const inst = ensureYearModal();
            if (!inst) return;
            inst.hide();
            setTimeout(cleanupModalArtifacts, 200);
        };

        // ---- set/get state
        const applyYearDisplay = (year, {
            store = true
        } = {}) => {
            const normalized = clampYear(Number(year));
            if (normalized === null) return false;
            yearState = normalized;
            if (store) sessionStorage.setItem(YEAR_STORAGE_KEY, String(yearState));
            if (yearTextEl) yearTextEl.textContent = String(yearState);
            return true;
        };
        const setYearValue = (year, {
            silent = false
        } = {}) => {
            const changed = applyYearDisplay(year);
            if (!silent && changed) {
                document.dispatchEvent(new CustomEvent('lukrato:year-changed', {
                    detail: {
                        year: yearState
                    }
                }));
            }
        };

        const setState = (ym, {
            silent = false
        } = {}) => {
            if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) return;
            state = ym;
            sessionStorage.setItem(STORAGE_KEY, state);

            if (elText) {
                elText.textContent = monthLabel(state);
                elText.setAttribute('data-month', state);
            }
            const inferredYear = Number(state.split('-')[0]);
            if (Number.isFinite(inferredYear)) {
                applyYearDisplay(inferredYear, {
                    store: false
                });
            }
            if (!silent) {
                document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                    detail: {
                        month: state
                    }
                }));
            }
        };
        const getState = () => state;
        const getYear = () => yearState;

        const buildYearGrid = () => {
            if (!yearGrid) return;
            let html = '';
            const start = yearState - 5;
            for (let i = 0; i < 11; i++) {
                const y = start + i;
                if (y < 2000 || y > 2100) continue;
                const active = y === yearState ? 'active' : '';
                html +=
                    `<button type="button" class="btn btn-outline-light ${active}" data-year="${y}">${y}</button>`;
            }
            yearGrid.innerHTML = html || '<p class="text-center mb-0">Sem anos disponíveis</p>';
            yearGrid.querySelectorAll('button[data-year]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    setYearValue(Number(btn.dataset.year));
                    closeYearModal();
                }, {
                    once: true
                });
            });
        };

        // ---- grade de meses do modal
        const buildGrid = () => {
            if (!mpYearLabel || !mpGrid) return;
            mpYearLabel.textContent = modalYear;

            let html = '';
            for (let i = 0; i < 12; i++) {
                const ym = `${modalYear}-${String(i+1).padStart(2,'0')}`;
                const active = ym === state ? 'btn-warning text-dark fw-bold' : 'btn-outline-light';
                html += `
        <div class="col-4">
          <button type="button" class="mp-month btn ${active}" data-val="${ym}">
            ${SHORT[i]}
          </button>
        </div>`;
            }
            mpGrid.innerHTML = html;

            mpGrid.querySelectorAll('.mp-month').forEach(btn => {
                btn.addEventListener('click', () => {
                    setState(btn.getAttribute('data-val'));
                    closeMonthModal();
                });
            });
        };

        // ---- navegação mês/ano
        const shiftMonth = (delta) => {
            const [y, m] = state.split('-').map(Number);
            const d = new Date(y, (m - 1) + delta, 1);
            setState(toYM(d));
        };

        // ---- eventos de UI
        // abre o modal
        btnOpen?.addEventListener('click', (e) => {
            e.preventDefault();
            openMonthModal();
        });

        // prev/next do cabeçalho
        btnPrev?.addEventListener('click', (e) => {
            e.preventDefault();
            shiftMonth(-1);
        });
        btnNext?.addEventListener('click', (e) => {
            e.preventDefault();
            shiftMonth(+1);
        });

        // controles do modal
        modalEl?.addEventListener('shown.bs.modal', () => {
            modalYear = Number(state.split('-')[0]) || (new Date()).getFullYear();
            if (mpInput) mpInput.value = state;
            buildGrid();
        });
        modalEl?.addEventListener('hidden.bs.modal', () => {
            cleanupModalArtifacts();
        });
        mpPrevYear?.addEventListener('click', () => {
            modalYear--;
            buildGrid();
        });
        mpNextYear?.addEventListener('click', () => {
            modalYear++;
            buildGrid();
        });
        mpTodayBtn?.addEventListener('click', () => {
            setState(toYM(new Date()));
            closeMonthModal();
        });
        mpInput?.addEventListener('change', (e) => {
            const ym = e.target.value;
            if (/^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) {
                setState(ym);
                closeMonthModal();
            }
        });

        // controles de ano
        btnYearDropdown?.addEventListener('click', (e) => {
            e.preventDefault();
            openYearModal();
        });
        btnPrevYear?.addEventListener('click', () => setYearValue(yearState - 1));
        btnNextYear?.addEventListener('click', () => setYearValue(yearState + 1));
        yearInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const y = clampYear(Number(yearInput.value));
                if (y) {
                    setYearValue(y);
                    closeYearModal();
                }
            }
        });
        yearApplyBtn?.addEventListener('click', () => {
            const y = clampYear(Number(yearInput?.value));
            if (y) {
                setYearValue(y);
                closeYearModal();
            }
        });
        yearModalEl?.addEventListener('shown.bs.modal', () => {
            buildYearGrid();
            if (yearInput) yearInput.value = String(yearState);
        });
        yearModalEl?.addEventListener('hidden.bs.modal', () => {
            cleanupModalArtifacts();
        });

        // ---- inicialização (garante modal pronto após load)
        const bootstrapReady = () => {
            ensureMonthModal();
        };
        if (document.readyState === 'complete') bootstrapReady();
        else window.addEventListener('load', bootstrapReady, {
            once: true
        });

        // ---- API pública
        window.LukratoHeader = Object.assign({}, window.LukratoHeader, {
            getMonth: () => getState(),
            setMonth: (ym, opts) => setState(ym, opts),
            getYear: () => getYear(),
            setYear: (year, opts) => setYearValue(year, opts),
            openMonthPicker: () => openMonthModal(),
            closeMonthPicker: () => closeMonthModal(),
            setPickerMode: (mode) => setPickerModeDisplay(mode),
            showYearPicker: (show = true) => setPickerModeDisplay(show ? 'year' : 'month'),
        });

        // seta o texto inicial/estado atual sem disparar evento extra
        setState(state, {
            silent: true
        });
        setYearValue(yearState, {
            silent: true
        });
        setPickerModeDisplay(pickerMode);
    })();
</script>