/* =========================================================
 * Lukrato - Header Controller (persistente)
 * Mantém o mês atual no localStorage e emite eventos globais.
 * Eventos:
 *  - 'lukrato:month-changed' { detail: { month: 'YYYY-MM' } }
 *  - 'lukrato:open-month-picker'
 * Coloque este arquivo em todas as páginas que têm o header.
 * ======================================================= */
(() => {
    const STORAGE_KEY = 'lukrato:currentMonth';

    // Utils
    function monthLabel(monthStr) {
        const [y, m] = monthStr.split('-').map(Number);
        const d = new Date(y, m - 1, 1);
        return d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
    }
    function clampMonth(monthStr, delta) {
        const [y, m] = monthStr.split('-').map(Number);
        const d = new Date(y, m - 1 + delta, 1);
        return d.toISOString().slice(0, 7);
    }
    function validMonth(s) {
        return typeof s === 'string' && /^\d{4}-(0[1-9]|1[0-2])$/.test(s);
    }

    // Estado (carrega do storage ou fallback para mês atual)
    function loadMonth() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (validMonth(saved)) return saved;
        return new Date().toISOString().slice(0, 7);
    }
    let currentMonth = loadMonth();

    // Render
    function renderMonthLabel() {
        const label = document.getElementById('currentMonthText');
        if (label) label.textContent = monthLabel(currentMonth);
    }

    // Troca de mês + persistência + evento
    function setMonth(newMonth) {
        if (!validMonth(newMonth) || newMonth === currentMonth) return;
        currentMonth = newMonth;
        localStorage.setItem(STORAGE_KEY, currentMonth);
        renderMonthLabel();
        document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
            detail: { month: currentMonth }
        }));
    }

    // Controles do header
    function bindHeaderControls() {
        const prev = document.getElementById('prevMonth');
        const next = document.getElementById('nextMonth');
        const openBtn = document.getElementById('monthDropdownBtn');

        if (prev) prev.addEventListener('click', () => setMonth(clampMonth(currentMonth, -1)));
        if (next) next.addEventListener('click', () => setMonth(clampMonth(currentMonth, +1)));
        if (openBtn) openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.dispatchEvent(new CustomEvent('lukrato:open-month-picker'));
        });
    }

    // Expor API mínima
    window.LukratoHeader = {
        getMonth: () => currentMonth,
        setMonth,
        monthLabel,
        clampMonth,
    };

    document.addEventListener('DOMContentLoaded', () => {
        // Se o HTML tiver um texto default (“Janeiro 2025”), aqui sobrescreve.
        renderMonthLabel();
        bindHeaderControls();
        // Dispara 1x no load para sincronizar quem precisar (dashboard, lançamentos etc.)
        document.dispatchEvent(new CustomEvent('lukrato:month-changed', {
            detail: { month: currentMonth }
        }));
    });
})();
