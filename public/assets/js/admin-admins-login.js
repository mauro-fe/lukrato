document.addEventListener('DOMContentLoaded', () => {
    // ===================== TABS =====================
    const card = document.querySelector('.auth-tabs-card');
    const tabBtns = document.querySelectorAll('.auth-tabs-card .tab-btn');
    const panels = {
        login: document.getElementById('tab-login'),
        register: document.getElementById('tab-register')
    };

    function activateTab(tab) {
        if (!panels[tab]) return;

        // Estado visual das abas
        tabBtns.forEach(btn => {
            const active = btn.dataset.tab === tab;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            btn.setAttribute('tabindex', active ? '0' : '-1');
        });

        // Acessibilidade (sem mexer em display)
        Object.entries(panels).forEach(([name, el]) => {
            if (!el) return;
            el.setAttribute('aria-hidden', name === tab ? 'false' : 'true');
        });

        // Aqui é o gatilho do flip
        if (card) card.dataset.active = tab;

        // Foco no primeiro campo da aba ativa
        const first = panels[tab].querySelector('input,select,textarea,button');
        if (first) first.focus();
    }

    // Bind dos botões
    if (card && !card.dataset.tabsBound) {
        card.dataset.tabsBound = '1';
        tabBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                activateTab(btn.dataset.tab);
            });
        });

        const hashTab = (location.hash || '').replace('#', '');
        activateTab(
            hashTab === 'register' || hashTab === 'login'
                ? hashTab
                : (card.dataset.active || 'login')
        );
    }
    // ===================== TOGGLE SENHA (login) =====================
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

    // ===================== HELPERS =====================
    const $ = (s, root = document) => root.querySelector(s);
    const $$ = (s, root = document) => Array.from(root.querySelectorAll(s));

    function showFieldErrorById(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId + 'Error');
        if (!field || !errorDiv) return;
        field.classList.add('error');
        errorDiv.textContent = message || '';
        errorDiv.classList.add('show');
        field.addEventListener('input', () => { field.classList.remove('error'); errorDiv.classList.remove('show'); }, { once: true });
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
        const message = msg || (type === 'error' ? 'Ocorreu um erro.' : 'Ação concluida com sucesso.');
        if (window.Swal) {
            // Toast para sucesso; modal para erro
            const Toast = Swal.mixin({
                toast: true,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            if (type === 'error') {
                Swal.fire({ icon: 'error', title: 'Ops...', text: message, confirmButtonText: 'Fechar' });
            } else {
                Toast.fire({ icon: 'success', title: message });
            }
            return;
        }
        // Fallback visual na pagina
        const id = type === 'error' ? 'generalError' : 'generalSuccess';
        const box = document.getElementById(id);
        if (!box) return;
        $$('.general-message').forEach(x => x.classList.remove('show'));
        box.textContent = message;
        box.classList.add('show');
        setTimeout(() => box.classList.remove('show'), 3000);
    }

    // ===================== EMAIL VERIFICATION ALERT =====================
    async function showEmailVerificationAlert(email) {
        if (!window.Swal) {
            showMessage('error', 'Você precisa verificar seu e-mail antes de fazer login. Verifique sua caixa de entrada.');
            return;
        }

        const result = await Swal.fire({
            icon: 'warning',
            title: 'E-mail não verificado',
            html: `
                <p style="margin-bottom: 16px;">Você precisa verificar seu e-mail antes de fazer login.</p>
                <p style="font-size: 14px; color: #666; margin-bottom: 8px;">Enviamos um link de verificação para:</p>
                <p style="font-weight: 600; color: #0369a1; background: #f0f9ff; padding: 8px 12px; border-radius: 6px; margin-bottom: 16px;">${email}</p>
                <p style="font-size: 13px; color: #888;">Não recebeu? Verifique a pasta de spam ou clique abaixo para reenviar.</p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Reenviar e-mail',
            cancelButtonText: 'Fechar',
            confirmButtonColor: '#e67e22',
            cancelButtonColor: '#6c757d',
        });

        if (result.isConfirmed) {
            await resendVerificationEmail(email);
        }
    }

    async function resendVerificationEmail(email) {
        try {
            Swal.fire({
                title: 'Enviando...',
                text: 'Aguarde enquanto reenviamos o e-mail.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            const resp = await fetch(BASE_URL + 'verificar-email/reenviar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ email })
            });

            const data = await resp.json();

            if (resp.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'E-mail enviado!',
                    text: data.message || 'Verifique sua caixa de entrada e clique no link para ativar sua conta.',
                    confirmButtonColor: '#27ae60'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message || 'Não foi possível reenviar o e-mail. Tente novamente.',
                    confirmButtonColor: '#e74c3c'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Erro de conexão',
                text: 'Não foi possível conectar ao servidor. Tente novamente.',
                confirmButtonColor: '#e74c3c'
            });
        }
    }

    // ===================== LOGIN (AJAX) =====================
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
            if (!emailVal) { showFieldErrorById('email', 'Este campo e obrigatorio.'); has = true; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) { showFieldErrorById('email', 'Informe um e-mail valido.'); has = true; }

            const pwdVal = (pwdField?.value || '').trim();
            if (!pwdVal) { showFieldErrorById('password', 'Este campo e obrigatorio.'); has = true; }
            else if (pwdVal.length < 4) { showFieldErrorById('password', 'Senha deve ter pelo menos 4 caracteres.'); has = true; }

            if (has) { loginForm.dataset.loading = '0'; return; }

            if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('loading'); }

            let keepDisabled = false;
            try {
                const resp = await fetch(loginForm.action, {
                    method: 'POST',
                    body: new FormData(loginForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                let data = null; try { data = await resp.json(); } catch { }

                if (resp.ok && data && data.status === 'success') {
                    keepDisabled = true;
                    showMessage('success', 'Login realizado! Redirecionando...');
                    setTimeout(() => {
                        window.location.href = (data && data.redirect) ? data.redirect : (BASE_URL + 'login');
                    }, 800);
                } else {
                    // Verificar se é erro de email não verificado - mostra APENAS o modal bonito
                    if (data?.errors?.email_not_verified) {
                        const userEmail = data?.errors?.user_email || emailVal;
                        showEmailVerificationAlert(userEmail);
                        return; // Importante: não mostra mais nada
                    }
                    
                    // Outros erros
                    if (data?.field === 'password') {
                        showFieldErrorById('password', data.message || 'Senha incorreta.');
                        pwdField?.focus();
                    } else if (data?.field === 'email') {
                        showFieldErrorById('email', data.message || 'E-mail nao encontrado.');
                        emailField?.focus();
                    } else if (data?.errors?.credentials) {
                        // Erro de credenciais - mostra mensagem bonita
                        showMessage('error', 'E-mail ou senha incorretos.');
                    } else if (data?.errors?.email && !data?.errors?.email_not_verified) {
                        // Erro específico de email (ex: conta Google)
                        showMessage('error', data.errors.email);
                    } else {
                        showMessage('error', data?.message || 'E-mail ou senha incorretos.');
                    }
                }
            } catch {
                showMessage('error', 'Falha ao conectar. Tente novamente.');
            } finally {
                if (!keepDisabled) {
                    loginForm.dataset.loading = '0';
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('loading'); }
                }
            }
        });

        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 'Enter' && !submitBtn?.disabled) loginForm.requestSubmit();
        });
    }

    // ===================== CADASTRO (AJAX) =====================
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
            if (el) { el.classList.add('error'); el.addEventListener('input', () => el.classList.remove('error'), { once: true }); }
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

            // validacao rapida no cliente
            let has = false;
            if (!nameInput.value.trim()) { showField(nameInput, errName, 'Informe seu nome.'); has = true; }
            const em = emailInput.value.trim().toLowerCase();
            if (!em) { showField(emailInput, errEmail, 'Informe seu e-mail.'); has = true; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { showField(emailInput, errEmail, 'E-mail invalido.'); has = true; }
            if (passInput.value.length < 8) { showField(passInput, errPass, 'Minimo de 8 caracteres.'); has = true; }
            if (confInput.value !== passInput.value) { showField(confInput, errConf, 'As senhas nao coincidem.'); has = true; }
            if (has) { regForm.dataset.loading = '0'; return; }

            const submitBtn = regForm.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('loading'); }

            try {
                const resp = await fetch(regForm.action, {
                    method: 'POST',
                    body: new FormData(regForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                let data = null; try { data = await resp.json(); } catch (_) { }

                if (resp.ok && data) {
                    // Mostra mensagem de sucesso com aviso sobre verificação de email
                    const successMsg = data.message || 'Conta criada com sucesso! Verifique seu e-mail para ativar.';
                    showMessage('success', successMsg);
                    setTimeout(() => {
                        window.location.href = (data && data.redirect) ? data.redirect : (BASE_URL + 'login');
                    }, 1500);
                    return;
                }

                if (data && data.errors) {
                    if (data.errors.name) showField(nameInput, errName, data.errors.name);
                    if (data.errors.email) showField(emailInput, errEmail, data.errors.email);
                    if (data.errors.password) showField(passInput, errPass, data.errors.password);
                    if (data.errors.password_confirmation) showField(confInput, errConf, data.errors.password_confirmation);

                    const first = Object.values(data.errors).find(Boolean);
                    if (first) {
                        showMessage('error', 'Verifique os campos destacados.');
                        if (boxErr) boxErr.textContent = Array.isArray(first) ? first[0] : first;
                    }
                    return;
                }

                if (data && data.message) {
                    showMessage('error', 'Nao foi possivel criar a conta.');
                    if (boxErr) boxErr.textContent = data.message;
                    return;
                }

                const fallbackMsg = `Falha (${resp.status}). Tente novamente.`;
                showMessage('error', fallbackMsg);
                if (boxErr) boxErr.textContent = fallbackMsg;
            } catch (err) {
                showMessage('error', 'Erro de conexao. Tente novamente.');
                if (boxErr) boxErr.textContent = 'Erro de conexao. Tente novamente.';
            } finally {
                regForm.dataset.loading = '0';
                if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('loading'); }
            }
        });
    })();

    // ===================== FILLED =====================
    $$('.form-floating-group input').forEach(input => {
        const update = () => input.classList.toggle('filled', input.value.trim() !== '');
        input.addEventListener('input', update);
        input.addEventListener('blur', update);
        update();
    });
});



