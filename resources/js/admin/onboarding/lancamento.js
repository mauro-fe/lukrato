/**
 * ============================================================================
 * LUKRATO — Onboarding Step 2: Lançamento (Vite Module)
 * ============================================================================
 * Extraído de views/admin/onboarding/lancamento.php
 *
 * Tipo toggle (receita/despesa), category filter, currency mask.
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    const tipoInput = document.getElementById('tipoInput');
    const btnDespesa = document.getElementById('btnDespesa');
    const btnReceita = document.getElementById('btnReceita');
    const categoriaSelect = document.getElementById('categoriaSelect');
    const valorInput = document.getElementById('valorInput');
    const btnSkip = document.getElementById('btnSkipOnboarding');
    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ─── Toggle tipo receita/despesa ───
    function setTipo(tipo) {
        tipoInput.value = tipo;

        btnDespesa.className = 'lk-tipo-btn' + (tipo === 'despesa' ? ' active-despesa' : '');
        btnReceita.className = 'lk-tipo-btn' + (tipo === 'receita' ? ' active-receita' : '');

        const options = categoriaSelect.querySelectorAll('option[data-tipo]');
        categoriaSelect.value = '';

        options.forEach(opt => {
            if (opt.dataset.tipo === tipo) {
                opt.style.display = '';
                opt.disabled = false;
            } else {
                opt.style.display = 'none';
                opt.disabled = true;
            }
        });
    }

    btnDespesa?.addEventListener('click', () => setTipo('despesa'));
    btnReceita?.addEventListener('click', () => setTipo('receita'));

    // ─── Máscara de valor (formato BR) ───
    if (valorInput) {
        valorInput.addEventListener('input', function (e) {
            let val = e.target.value.replace(/[^\d]/g, '');
            if (val === '') { e.target.value = ''; return; }
            val = parseInt(val, 10);
            const formatted = (val / 100).toFixed(2)
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            e.target.value = formatted;
        });

        valorInput.addEventListener('keydown', function (e) {
            if ([8, 9, 13, 27, 46, 37, 38, 39, 40].includes(e.keyCode)) return;
            if ((e.ctrlKey || e.metaKey) && [65, 67, 86, 88].includes(e.keyCode)) return;
            if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    }

    // ─── Botão "Explorar o Lukrato" (skip lançamento) ───
    if (btnSkip) {
        btnSkip.addEventListener('click', function () {
            btnSkip.disabled = true;
            btnSkip.innerHTML = '<span class="lk-skip-loading"></span> Preparando...';

            fetch(BASE_URL + 'api/onboarding/complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success && res.data?.redirect) {
                        window.location.href = res.data.redirect;
                    } else {
                        window.location.href = BASE_URL + 'dashboard';
                    }
                })
                .catch(function () {
                    window.location.href = BASE_URL + 'dashboard';
                });
        });
    }
});
