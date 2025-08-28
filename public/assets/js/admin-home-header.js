/* =========================================================
 * Lukrato - JS do HEADER (somente UI, SEM chamadas de API)
 * - mês global (localStorage)
 * - month picker modal
 * - export click (evento)
 * - abertura de modais (evento)
 * - FAB toggle + fechar modais
 * ======================================================= */
(() => {
    // --------- Utils de DOM/strings ---------
    const $ = (s, sc = document) => sc.querySelector(s);
    const $$ = (s, sc = document) => Array.from(sc.querySelectorAll(s));

    // --------- BASE_URL (caso alguém precise depois) ---------
    window.BASE_URL = (document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/?$/, '/');

    // --------- Controle de mês (persistente) ---------
    const STORAGE_KEY = 'lukrato:currentMonth';
    const validMonth = (s) => typeof s === 'string' && /^\d{4}-(0[1-9]|1[0-2])$/.test(s);
    const monthLabel = (m) => {
        const [y, mm] = m.split('-').map(Number);
        return new Date(y, mm - 1, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    };
    const clampMonth = (m, delta) => {
        const [y, mm] = m.split('-').map(Number);
        const d = new Date(y, mm - 1 + delta, 1);
        return d.toISOString().slice(0, 7);
    };

    let currentMonth = (() => {
        const saved = localStorage.getItem(STORAGE_KEY);
        return validMonth(saved) ? saved : new Date().toISOString().slice(0, 7);
    })();

    function renderMonthLabel() {
        const el = $('#currentMonthText');
        if (el) el.textContent = monthLabel(currentMonth);
    }

    function setMonth(m) {
        if (!validMonth(m) || m === currentMonth) return;
        currentMonth = m;
        localStorage.setItem(STORAGE_KEY, m);
        renderMonthLabel();
        document.dispatchEvent(new CustomEvent('lukrato:month-changed', { detail: { month: m } }));
    }

    // expõe uma API mínima pra outras páginas
    window.LukratoHeader = {
        getMonth: () => currentMonth,
        setMonth,
        monthLabel,
        clampMonth,
    };

    // --------- Month Picker (modal) ---------
    const mpState = { month: currentMonth, selectedDate: `${currentMonth}-01` };

    function openMonthPicker() {
        Object.assign(mpState, { month: currentMonth, selectedDate: `${currentMonth}-01` });
        renderMonthPicker();
        toggleModal('monthPickerModal', true);
    }
    function closeMonthPicker() { toggleModal('monthPickerModal', false); }

    function renderMonthPicker() {
        const grid = $('#calendarGrid');
        const label = $('#mpLabel');
        if (!grid || !label) return;

        label.textContent = monthLabel(mpState.month);

        const [year, month] = mpState.month.split('-').map(Number);
        const firstDay = new Date(year, month - 1, 1);
        const firstWeekday = firstDay.getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const cells = [];
        const todayIso = new Date().toISOString().slice(0, 10);

        // dias do mês anterior
        const prevMonthLastDay = new Date(year, month - 1, 0).getDate();
        for (let i = firstWeekday - 1; i >= 0; i--) {
            cells.push({ date: new Date(year, month - 2, prevMonthLastDay - i).toISOString().slice(0, 10), muted: true });
        }
        // dias do mês atual
        for (let d = 1; d <= daysInMonth; d++) {
            cells.push({ date: new Date(year, month - 1, d).toISOString().slice(0, 10), muted: false });
        }
        // completa a grade
        while (cells.length % 7 !== 0) {
            const lastDate = new Date(cells.at(-1).date);
            lastDate.setDate(lastDate.getDate() + 1);
            cells.push({ date: lastDate.toISOString().slice(0, 10), muted: true });
        }

        grid.innerHTML = cells.map(c => `
      <button type="button"
        class="calendar-day ${c.muted ? 'muted' : ''} ${c.date === todayIso ? 'today' : ''} ${c.date === mpState.selectedDate ? 'selected' : ''}"
        data-date="${c.date}">
        ${c.date.split('-')[2]}
      </button>
    `).join('');

        $$('.calendar-day', grid).forEach(btn => {
            btn.addEventListener('click', () => {
                const date = btn.dataset.date;
                mpState.selectedDate = date;
                mpState.month = date.slice(0, 7);
                grid.querySelector('.selected')?.classList.remove('selected');
                btn.classList.add('selected');
                label.textContent = monthLabel(mpState.month);
            });
        });
    }

    function bindMonthPickerControls() {
        $('#mpPrev')?.addEventListener('click', () => {
            mpState.month = clampMonth(mpState.month, -1);
            renderMonthPicker();
        });
        $('#mpNext')?.addEventListener('click', () => {
            mpState.month = clampMonth(mpState.month, +1);
            renderMonthPicker();
        });
        $('#mpConfirm')?.addEventListener('click', () => {
            setMonth(mpState.month);
            closeMonthPicker();
        });
        $$('[data-close-month]').forEach(el => el.addEventListener('click', closeMonthPicker));
    }

    // --------- Modais (abrir/fechar – sem API) ---------
    function toggleModal(id, open) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.toggle('active', open);
        m.setAttribute('aria-hidden', String(!open));
        if (open) {
            // coloca data de hoje em inputs date que estiverem vazios
            const today = new Date().toISOString().slice(0, 10);
            m.querySelectorAll('input[type="date"]').forEach(inp => { if (!inp.value) inp.value = today; });
        }
    }

    // fecha por X/backdrop/botão com data-dismiss
    function bindModalClose() {
        $$('.modal .modal-close, .modal .modal-backdrop, .modal [data-dismiss="modal"]').forEach(el => {
            el.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                if (modal) toggleModal(modal.id, false);
            });
        });
    }

    // --------- FAB (aside) ---------
    function bindFab() {
        const fab = $('#fabButton');
        const menu = $('#fabMenu');
        if (!fab || !menu) return;

        let open = false;
        fab.addEventListener('click', () => {
            open = !open;
            fab.classList.toggle('active', open);
            fab.setAttribute('aria-expanded', String(open));
            menu.classList.toggle('active', open);
        });

        // itens do FAB abrem modais via evento
        $$('.fab-menu-item[data-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = String(btn.dataset.modal || '').toLowerCase(); // exemplo: "despesa-cartao"
                document.dispatchEvent(new CustomEvent('lukrato:open-modal', { detail: { key } }));
            });
        });
    }

    // --------- Header: prev/next, dropdown, export, abrir modal ---------
    function bindHeaderControls() {
        $('#prevMonth')?.addEventListener('click', () => setMonth(clampMonth(currentMonth, -1)));
        $('#nextMonth')?.addEventListener('click', () => setMonth(clampMonth(currentMonth, +1)));

        // abrir seletor de mês (mesmo botão que mostra o dropdown)
        $('#monthDropdownBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            openMonthPicker();
        });

        // exportar: o header só emite evento; a página decide o que fazer
        $('#exportBtn')?.addEventListener('click', () => {
            document.dispatchEvent(new CustomEvent('lukrato:export-click', { detail: { month: currentMonth } }));
        });

        // se você tiver botões no header para abrir modais:
        // <button data-open-modal="receita">...</button>
        $$('[data-open-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = String(btn.dataset.openModal || '').toLowerCase();
                document.dispatchEvent(new CustomEvent('lukrato:open-modal', { detail: { key } }));
            });
        });
    }

    // --------- Sidebar toggle (mobile) ---------
    function bindSidebarToggle() {
        const toggle = $('#sidebarToggle');
        const sidebar = $('#sidebar-main');
        toggle?.addEventListener('click', () => sidebar?.classList.toggle('open'));
    }

    // --------- Boot ---------
    document.addEventListener('DOMContentLoaded', () => {
        // render inicial do mês e broadcast 1x
        renderMonthLabel();
        document.dispatchEvent(new CustomEvent('lukrato:month-changed', { detail: { month: currentMonth } }));

        bindHeaderControls();
        bindMonthPickerControls();
        bindModalClose();
        bindFab();
        bindSidebarToggle();

        // atalhos globais: outras partes podem pedir o seletor de mês
        document.addEventListener('lukrato:open-month-picker', openMonthPicker);

        // abrir modal por evento (vindo do header/FAB)
        document.addEventListener('lukrato:open-modal', (e) => {
            const key = e.detail?.key;
            if (!key) return;
            // mapeia "despesa-cartao" -> "modalDespesaCartao"
            const id = 'modal' + key.replace(/(^|-)(\w)/g, (_, __, b) => b.toUpperCase());
            toggleModal(id, true);
        });
    });
})();
