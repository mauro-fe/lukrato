/**
 * ============================================================================
 * LUKRATO — Onboarding Step 1: Conta (Vite Module)
 * ============================================================================
 * Extraído de views/admin/onboarding/index.php
 *
 * Currency mask, searchable institution select, form validation.
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    // ─── Currency mask for saldo_inicial ───
    const saldoInput = document.getElementById('saldoInput');
    if (saldoInput) {
        saldoInput.addEventListener('focus', function () {
            setTimeout(() => this.select(), 50);
        });

        saldoInput.addEventListener('input', function (e) {
            let val = e.target.value.replace(/[^\d]/g, '');
            if (val === '') { e.target.value = '0,00'; return; }
            val = parseInt(val, 10);
            const formatted = (val / 100).toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            e.target.value = formatted;
        });

        saldoInput.addEventListener('keydown', function (e) {
            if ([8, 9, 13, 27, 46, 37, 38, 39, 40].includes(e.keyCode)) return;
            if ((e.ctrlKey || e.metaKey) && [65, 67, 86, 88].includes(e.keyCode)) return;
            if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    }

    // ─── Searchable institution select ───
    const wrapper = document.getElementById('instSelectWrapper');
    const searchInput = document.getElementById('instSearchInput');
    const dropdown = document.getElementById('instDropdown');
    const hiddenInput = document.getElementById('instituicaoHidden');
    const options = dropdown ? Array.from(dropdown.querySelectorAll('.lk-select-option')) : [];

    let isOpen = false;

    function openDropdown() {
        wrapper.classList.add('open');
        searchInput.removeAttribute('readonly');
        isOpen = true;
        filterOptions('');
    }

    function closeDropdown() {
        wrapper.classList.remove('open');
        searchInput.setAttribute('readonly', '');
        isOpen = false;
        const selected = options.find(o => o.classList.contains('selected'));
        searchInput.value = selected ? selected.textContent.trim() : '';
    }

    function filterOptions(query) {
        const q = query.toLowerCase().trim();
        let visibleCount = 0;
        options.forEach(opt => {
            const match = !q || opt.textContent.toLowerCase().includes(q);
            opt.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        let emptyMsg = dropdown.querySelector('.lk-select-empty');
        if (visibleCount === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.className = 'lk-select-empty';
                emptyMsg.textContent = 'Nenhuma instituição encontrada';
                dropdown.appendChild(emptyMsg);
            }
            emptyMsg.style.display = '';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }

    function selectOption(opt) {
        options.forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        hiddenInput.value = opt.dataset.value;
        searchInput.value = opt.textContent.trim();
        closeDropdown();
    }

    if (searchInput) {
        searchInput.addEventListener('click', () => {
            if (!isOpen) { openDropdown(); searchInput.value = ''; }
        });

        searchInput.addEventListener('input', () => filterOptions(searchInput.value));
        searchInput.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDropdown(); });
    }

    options.forEach(opt => {
        opt.addEventListener('click', () => selectOption(opt));
    });

    document.addEventListener('click', (e) => {
        if (wrapper && !wrapper.contains(e.target)) closeDropdown();
    });

    // ─── Form validation ───
    const form = document.getElementById('onboardingContaForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!hiddenInput.value) {
                e.preventDefault();
                searchInput.focus();
                wrapper.classList.add('open');
                searchInput.style.borderColor = 'var(--color-danger)';
                setTimeout(() => searchInput.style.borderColor = '', 2000);
            }
        });
    }
});
