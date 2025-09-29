

document.addEventListener('DOMContentLoaded', () => {
    /* ===================== TABS ===================== */
    const card = document.querySelector('.auth-tabs-card');
    const tabBtns = document.querySelectorAll('.auth-tabs-card .tab-btn');
    const panels = {
        login: document.getElementById('tab-login'),
        register: document.getElementById('tab-register'),
    };

    function activateTab(tab) {
        if (!panels[tab]) return;
        // botão ativo
        tabBtns.forEach(btn => {
            const active = btn.dataset.tab === tab;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.setAttribute('tabindex', active ? '0' : '-1');
        });
        // painel visível
        Object.entries(panels).forEach(([name, el]) => {
            if (!el) return;
            el.classList.toggle('is-hidden', name !== tab);
            el.hidden = (name !== tab);
        });
        if (card) card.dataset.active = tab;
        // foco
        const first = panels[tab].querySelector('input,select,textarea,button');
        if (first) first.focus();
    }

    if (card && !card.dataset.tabsBound) {
        card.dataset.tabsBound = '1';
        tabBtns.forEach(btn => btn.addEventListener('click', e => {
            e.preventDefault();
            activateTab(btn.dataset.tab);
        }));
        const hashTab = (location.hash || '').replace('#', '');
        activateTab(hashTab === 'register' || hashTab === 'login'
            ? hashTab
            : (card.dataset.active || 'login'));
    }

    /* ===================== TOGGLE SENHA (login) ===================== */
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    if (toggleIcon && passwordField && !toggleIcon.dataset.bound) {
        toggleIcon.dataset.bound = '1';
        toggleIcon.addEventListener('click', e => {
            e.preventDefault();
            const showing = passwordField.type === 'text';
            passwordField.type = showing ? 'password' : 'text';
            toggleIcon.classList.toggle('fa-eye', showing);
            toggleIcon.classList.toggle('fa-eye-slash', !showing);
            toggleIcon.style.color = showing ? '#888' : '#12453E';
            toggleIcon.title = showing ? 'Mostrar senha' : 'Ocultar senha';
            toggleIcon.style.transform = 'translateY(-50%) scale(1.2)';
            setTimeout(() => toggleIcon.style.transform = 'translateY(-50%) scale(1)', 150);
        });
    }

    /* ===================== HELPERS ===================== */
    const $ = (s, root = document) => root.querySelector(s);
    const $$ = (s, root = document) => Array.from(root.querySelectorAll(s));

    function showFieldErrorById(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId + 'Error');
        if (!field || !errorDiv) return;
        field.classList.add('error');
        errorDiv.textContent = message || '';
        errorDiv.classList.add('show');
        field.addEventListener('input', () => {
            field.classList.remove('error');
            errorDiv.classList.remove('show');
        }, { once: true });
    }
    function showFieldError(fieldEl, errorEl, message) {
        if (errorEl) errorEl.textContent = message || '';
        if (fieldEl) {
            fieldEl.classList.add('error');
            fieldEl.addEventListener('input', () => fieldEl.classList.remove('error'), { once: true });
        }
    }
    function clearAllErrors(scope = document) {
        $$('.field-error', scope).forEach(el => { el.textContent = ''; el.classList.remove('show'); });
        $$('input,select,textarea', scope).forEach(el => el.classList.remove('error'));
        $$('.general-message', scope).forEach(el => { el.textContent = ''; el.classList.remove('show'); });
    }
    function showMessage(type, msg) {
        const id = type === 'error' ? 'generalError' : 'generalSuccess';
        const box = document.getElementById(id);
        if (!box) return;
        $$('.general-message').forEach(x => x.classList.remove('show'));
        box.textContent = msg || '';
        setTimeout(() => box.classList.add('show'), 10);
        setTimeout(() => box.classList.remove('show'), 5000);
    }

    /* ===================== LOGIN (AJAX) ===================== */
    const loginForm = document.getElementById('loginForm');
    if (loginForm && loginForm.dataset.bound !== '1') {
        loginForm.dataset.bound = '1';
        loginForm.noValidate = true;
        const submitBtn = document.getElementById('submitBtn');

        loginForm.addEventListener('submit', async e => {
            e.preventDefault();
            if (loginForm.dataset.loading === '1') return; // anti-duplo
            loginForm.dataset.loading = '1';
            clearAllErrors(loginForm);

            const emailField = $('#email');
            const pwdField = $('#password');
            let has = false;

            const emailVal = (emailField?.value || '').trim().toLowerCase();
            if (!emailVal) { showFieldErrorById('email', 'Este campo é obrigatório.'); has = true; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) { showFieldErrorById('email', 'Informe um e-mail válido.'); has = true; }

            const pwdVal = (pwdField?.value || '').trim();
            if (!pwdVal) { showFieldErrorById('password', 'Este campo é obrigatório.'); has = true; }
            else if (pwdVal.length < 4) { showFieldErrorById('password', 'Senha deve ter pelo menos 4 caracteres.'); has = true; }

            if (has) { loginForm.dataset.loading = '0'; return; }

            if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('loading'); }

            try {
                const resp = await fetch(loginForm.action, {
                    method: 'POST',
                    body: new FormData(loginForm), // inclui CSRF
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                let data = null; try { data = await resp.json(); } catch { }

                if (resp.ok && data && data.status === 'success') {
                    if (window.Swal) Swal.fire({ icon: 'success', title: 'Login bem-sucedido!', timer: 1200, showConfirmButton: false });
                    else showMessage('success', 'Login realizado! Redirecionando…');
                    setTimeout(() => {
                        window.location.href = (data && data.redirect) ? data.redirect : (BASE_URL + 'login');
                    }, 1200);

                } else {
                    if (data?.field === 'password') {
                        showFieldErrorById('password', data.message || 'Senha incorreta.');
                        pwdField?.focus();
                    } else if (data?.field === 'email') {
                        showFieldErrorById('email', data.message || 'E-mail não encontrado.');
                        emailField?.focus();
                    } else {
                        showMessage('error', data?.message || 'E-mail ou senha incorretos.');
                    }
                }
            } catch {
                showMessage('error', 'Falha ao conectar. Tente novamente.');
            } finally {
                loginForm.dataset.loading = '0';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('loading'); }
            }
        });

        // Atalho Ctrl+Enter
        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 'Enter' && !submitBtn?.disabled) loginForm.requestSubmit();
        });
    }

    // ===================== CADASTRO (AJAX) ROBUSTO =====================
    (() => {
        const regForm = document.getElementById('registerForm');
        if (!regForm || regForm.dataset.bound === '1') return;
        regForm.dataset.bound = '1';
        regForm.noValidate = true;

        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('reg_email');
        const passInput = document.getElementById('reg_password');
        const confInput = document.getElementById('reg_password_confirm');

        const errName = document.getElementById('nameError');
        const errEmail = document.getElementById('regEmailError');
        const errPass = document.getElementById('regPasswordError');
        const errConf = document.getElementById('regPasswordConfirmError');

        const boxOk = document.getElementById('registerGeneralSuccess');
        const boxErr = document.getElementById('registerGeneralError');

        const showField = (el, box, msg) => {
            if (box) box.textContent = msg || '';
            if (el) {
                el.classList.add('error');
                el.addEventListener('input', () => el.classList.remove('error'), { once: true });
            }
        };
        const clear = () => {
            [errName, errEmail, errPass, errConf, boxOk, boxErr].forEach(el => { if (el) el.textContent = ''; });
            [nameInput, emailInput, passInput, confInput].forEach(el => el && el.classList.remove('error'));
        };

        regForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (regForm.dataset.loading === '1') return; // anti-duplo
            regForm.dataset.loading = '1';
            clear();

            // validação rápida no cliente
            let has = false;
            if (!nameInput.value.trim()) { showField(nameInput, errName, 'Informe seu nome.'); has = true; }
            const em = emailInput.value.trim().toLowerCase();
            if (!em) { showField(emailInput, errEmail, 'Informe seu e-mail.'); has = true; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { showField(emailInput, errEmail, 'E-mail inválido.'); has = true; }
            if (passInput.value.length < 8) { showField(passInput, errPass, 'Mínimo de 8 caracteres.'); has = true; }
            if (confInput.value !== passInput.value) { showField(confInput, errConf, 'As senhas não coincidem.'); has = true; }
            if (has) { regForm.dataset.loading = '0'; return; }

            const submitBtn = regForm.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('loading'); }

            try {
                const resp = await fetch(regForm.action, {
                    method: 'POST',
                    body: new FormData(regForm),           // inclui o CSRF do helper
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                // tente ler JSON mesmo em 422/500
                let data = null;
                try { data = await resp.json(); } catch (_) { }

                // SUCESSO
                if (resp.ok && data) {
                    if (boxOk) boxOk.textContent = data.message || 'Conta criada com sucesso!';
                    setTimeout(() => {
                        window.location.href = (data && data.redirect) ? data.redirect : (BASE_URL + 'login');
                    }, 800);

                    return;
                }

                // ERROS PADRONIZADOS { errors: { ... } }
                if (data && data.errors) {
                    if (data.errors.name) showField(nameInput, errName, data.errors.name);
                    if (data.errors.email) showField(emailInput, errEmail, data.errors.email);
                    if (data.errors.password) showField(passInput, errPass, data.errors.password);
                    if (data.errors.password_confirmation) showField(confInput, errConf, data.errors.password_confirmation);

                    const first = Object.values(data.errors).find(Boolean);
                    if (boxErr && first) boxErr.textContent = Array.isArray(first) ? first[0] : first;
                    return;
                }

                // ERRO COM MENSAGEM LIVRE { message: '...' }
                if (data && data.message) {
                    if (boxErr) boxErr.textContent = data.message;
                    return;
                }

                // Fallback: mostra status
                if (boxErr) boxErr.textContent = `Falha (${resp.status}). Tente novamente.`;
            } catch (err) {
                if (boxErr) boxErr.textContent = 'Erro de conexão. Tente novamente.';
            } finally {
                regForm.dataset.loading = '0';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('loading'); }
            }
        });
    })();


    /* ===================== FILLED ===================== */
    $$('.form-floating-group input').forEach(input => {
        const update = () => input.classList.toggle('filled', input.value.trim() !== '');
        input.addEventListener('input', update);
        input.addEventListener('blur', update);
        update();
    });
});
