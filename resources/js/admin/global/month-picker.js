/**
 * ============================================================================
 * LUKRATO — Month Picker
 * ============================================================================
 * Seletor de mês/ano do header do dashboard.
 * Extraído de: views/admin/partials/header-mes.php
 * ============================================================================
 */

(() => {
    'use strict';
    if (window.__LK_MONTH_PICKER__) return;
    window.__LK_MONTH_PICKER__ = true;

    // ---- elementos do header/modal
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
    const toYM = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
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
    const modalSystem = window.LK?.modalSystem;

    // ---- bootstrap modal
    const ensureMonthModal = () => {
        if (!modalEl || !window.bootstrap?.Modal) return null;
        modalSystem?.prepareBootstrapModal(modalEl, { scope: 'page' });
        return window.bootstrap.Modal.getOrCreateInstance(modalEl);
    };

    const cleanupModalArtifacts = () => {
        if (document.querySelector('.modal.show')) return;
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
        modalSystem?.prepareBootstrapModal(yearModalEl, { scope: 'page' });
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
    const applyYearDisplay = (year, { store = true } = {}) => {
        const normalized = clampYear(Number(year));
        if (normalized === null) return false;
        yearState = normalized;
        if (store) sessionStorage.setItem(YEAR_STORAGE_KEY, String(yearState));
        if (yearTextEl) yearTextEl.textContent = String(yearState);
        return true;
    };

    const setYearValue = (year, { silent = false } = {}) => {
        const changed = applyYearDisplay(year);
        if (!silent && changed) {
            document.dispatchEvent(new CustomEvent('lukrato:year-changed', {
                detail: { year: yearState }
            }));
        }
    };

    const setState = (ym, { silent = false } = {}) => {
        if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(ym)) return;
        state = ym;
        sessionStorage.setItem(STORAGE_KEY, state);

        if (elText) {
            elText.textContent = monthLabel(state);
            elText.setAttribute('data-month', state);
        }
        const inferredYear = Number(state.split('-')[0]);
        if (Number.isFinite(inferredYear)) {
            applyYearDisplay(inferredYear, { store: false });
        }
        if (!silent) {
            document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
                detail: { month: state }
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
            html += `<button type="button" class="btn btn-outline-light ${active}" data-year="${y}">${y}</button>`;
        }
        yearGrid.innerHTML = html || '<p class="text-center mb-0">Sem anos disponíveis</p>';
        yearGrid.querySelectorAll('button[data-year]').forEach((btn) => {
            btn.addEventListener('click', () => {
                setYearValue(Number(btn.dataset.year));
                closeYearModal();
            }, { once: true });
        });
    };

    // ---- grade de meses do modal
    const buildGrid = () => {
        if (!mpYearLabel || !mpGrid) return;
        mpYearLabel.textContent = modalYear;

        let html = '';
        for (let i = 0; i < 12; i++) {
            const ym = `${modalYear}-${String(i + 1).padStart(2, '0')}`;
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
    let shiftTimeout = null;

    const shiftMonth = (delta) => {
        if (shiftTimeout) return;
        shiftTimeout = setTimeout(() => { shiftTimeout = null; }, 150);

        const [y, m] = state.split('-').map(Number);
        const d = new Date(y, (m - 1) + delta, 1);
        setState(toYM(d));
    };

    // ---- eventos de UI
    btnOpen?.addEventListener('click', (e) => {
        e.preventDefault();
        openMonthModal();
    });

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

    mpPrevYear?.addEventListener('click', () => { modalYear--; buildGrid(); });
    mpNextYear?.addEventListener('click', () => { modalYear++; buildGrid(); });

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
            if (y) { setYearValue(y); closeYearModal(); }
        }
    });

    yearApplyBtn?.addEventListener('click', () => {
        const y = clampYear(Number(yearInput?.value));
        if (y) { setYearValue(y); closeYearModal(); }
    });

    yearModalEl?.addEventListener('shown.bs.modal', () => {
        buildYearGrid();
        if (yearInput) yearInput.value = String(yearState);
    });

    yearModalEl?.addEventListener('hidden.bs.modal', () => {
        cleanupModalArtifacts();
    });

    // ---- inicialização
    const bootstrapReady = () => { ensureMonthModal(); };
    if (document.readyState === 'complete') bootstrapReady();
    else window.addEventListener('load', bootstrapReady, { once: true });

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

    // Seta o texto inicial / estado atual sem disparar evento extra
    setState(state, { silent: true });
    setYearValue(yearState, { silent: true });
    setPickerModeDisplay(pickerMode);

    // ---- Atalhos de teclado para navegação de mês
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
        if (window.LK?.modalSystem?.hasBlockingDialog?.()) return;
        if (e.target.closest('.swal2-container')) return;

        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            shiftMonth(-1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            shiftMonth(+1);
        }
    });
})();
