/**
 * ============================================================================
 * LUKRATO — Login / Register Page (Vite Module)
 * ============================================================================
 * CSRF refresh, password strength, referral codes, tabs, form handlers.
 *
 * Substitui: public/assets/js/auth/login.js + auth-shared.js
 * ============================================================================
 */

import { createParticles, createConfetti, initTogglePassword, getBaseUrl } from '../shared.js';

const BASE = getBaseUrl();

// ── Init shared features ───────────────────────────────────────────────────
createParticles();
initTogglePassword();
// =====================
// CLOUDFLARE TURNSTILE (CAPTCHA PROGRESSIVO)
// =====================
const TurnstileManager = {
    siteKey: null,
    loginWidgetId: null,
    registerWidgetId: null,
    loginRequired: false,
    ready: false,
    loginFailures: 0,
    failureThreshold: 3,

    init() {
        const keyMeta = document.querySelector('meta[name="turnstile-site-key"]');
        const requiredMeta = document.querySelector('meta[name="turnstile-required"]');
        if (!keyMeta || !keyMeta.content) return;

        this.siteKey = keyMeta.content;
        this.loginRequired = requiredMeta?.content === '1';

        // Recuperar contador de falhas do sessionStorage (persiste entre reloads)
        const stored = sessionStorage.getItem('turnstile_login_failures');
        if (stored) this.loginFailures = parseInt(stored, 10) || 0;

        // Esperar a API do Turnstile carregar
        this._waitForApi(() => {
            this.ready = true;
            // Registro: sempre mostra se configurado
            this.showRegisterCaptcha();
            // Login: mostra se servidor pediu OU se já atingiu threshold local
            if (this.loginRequired || this.loginFailures >= this.failureThreshold) {
                this.showLoginCaptcha();
            }
        });
    },

    /**
     * Incrementa falhas do lado do cliente e mostra CAPTCHA se atingir threshold.
     */
    recordLoginFailure() {
        this.loginFailures++;
        sessionStorage.setItem('turnstile_login_failures', String(this.loginFailures));
        if (this.loginFailures >= this.failureThreshold) {
            this.showLoginCaptcha();
        }
    },

    /**
     * Zera contador de falhas (chamado após login bem-sucedido).
     */
    clearLoginFailures() {
        this.loginFailures = 0;
        sessionStorage.removeItem('turnstile_login_failures');
    },

    _waitForApi(cb) {
        if (window.turnstile) { cb(); return; }
        let attempts = 0;
        const interval = setInterval(() => {
            if (window.turnstile || ++attempts > 50) {
                clearInterval(interval);
                if (window.turnstile) cb();
            }
        }, 200);
    },

    showLoginCaptcha() {
        const wrapper = document.getElementById('loginTurnstileWrapper');
        if (!wrapper || !this.ready || this.loginWidgetId !== null) return;
        wrapper.style.display = 'flex';
        this.loginWidgetId = window.turnstile.render('#loginTurnstileWidget', {
            sitekey: this.siteKey,
            theme: 'dark',
            language: 'pt-br',
            'response-field-name': 'cf-turnstile-response',
        });
        this.loginRequired = true;
        // Expandir containers para acomodar o widget
        const card = document.querySelector('.card');
        if (card) card.classList.add('captcha-visible');
    },

    showRegisterCaptcha() {
        const wrapper = document.getElementById('registerTurnstileWrapper');
        if (!wrapper || !this.ready || this.registerWidgetId !== null) return;
        wrapper.style.display = 'flex';
        this.registerWidgetId = window.turnstile.render('#registerTurnstileWidget', {
            sitekey: this.siteKey,
            theme: 'dark',
            language: 'pt-br',
            'response-field-name': 'cf-turnstile-response',
        });
        // Expandir containers para acomodar o widget
        const card = document.querySelector('.card');
        if (card) card.classList.add('captcha-visible');
    },

    resetLogin() {
        if (this.loginWidgetId !== null && window.turnstile) {
            window.turnstile.reset(this.loginWidgetId);
        }
    },

    resetRegister() {
        if (this.registerWidgetId !== null && window.turnstile) {
            window.turnstile.reset(this.registerWidgetId);
        }
    },

    getLoginToken() {
        if (this.loginWidgetId === null || !window.turnstile) return null;
        return window.turnstile.getResponse(this.loginWidgetId) || null;
    },

    getRegisterToken() {
        if (this.registerWidgetId === null || !window.turnstile) return null;
        return window.turnstile.getResponse(this.registerWidgetId) || null;
    },
};

TurnstileManager.init();
// =====================
// CSRF Helpers
// =====================

async function refreshCsrfForForm(tokenId) {
    const response = await fetch(BASE + 'api/csrf/refresh', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ token_id: tokenId })
    });

    const data = await response.json();

    if (data.token) {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) metaTag.content = data.token;

        document.querySelectorAll('input[name="csrf_token"]').forEach((input) => {
            const formId = input.closest('form')?.id;
            if (tokenId === 'login_form' && formId === 'loginForm') {
                input.value = data.token;
            } else if (tokenId === 'register_form' && formId === 'registerForm') {
                input.value = data.token;
            }
        });

        return data.token;
    }
    throw new Error('Token não recebido');
}

function isCsrfError(response, data) {
    if (response.status === 419) return true;
    if (response.status === 403 && data?.errors?.csrf_token) return true;
    if (data?.csrf_expired === true) return true;
    const msg = String(data?.message || '').toLowerCase();
    return msg.includes('csrf') || msg.includes('token');
}

// =====================
// CSRF Auto-refresh
// =====================
{
    const LOGIN_CSRF_REFRESH_INTERVAL = 5 * 60 * 1000;
    let lastRefresh = Date.now();
    const MIN_REFRESH_GAP = 30000;

    function maybeRefreshToken() {
        const now = Date.now();
        if (now - lastRefresh > MIN_REFRESH_GAP) {
            lastRefresh = now;
            if (typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken();
            }
        }
    }

    document.querySelectorAll('#loginForm input, #registerForm input').forEach((input) => {
        input.addEventListener('focus', maybeRefreshToken, { passive: true });
    });

    setInterval(() => {
        if (typeof window.refreshCsrfToken === 'function') {
            window.refreshCsrfToken();
        }
    }, LOGIN_CSRF_REFRESH_INTERVAL);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            maybeRefreshToken();
        }
    });
}

// =====================
// Password Strength + Confirm Match
// =====================
{
    const pwd = document.getElementById('reg_password');
    const confirmEl = document.getElementById('reg_password_confirm');
    const panel = document.getElementById('pwdStrength');
    const barFill = document.getElementById('pwdBarFill');
    const levelEl = document.getElementById('pwdLevel');
    const matchEl = document.getElementById('pwdMatch');

    if (pwd && confirmEl && panel) {
        const rules = [
            { id: 'req-length', test: (v) => v.length >= 8 },
            { id: 'req-lower', test: (v) => /[a-z]/.test(v) },
            { id: 'req-upper', test: (v) => /[A-Z]/.test(v) },
            { id: 'req-number', test: (v) => /[0-9]/.test(v) },
            { id: 'req-special', test: (v) => /[^a-zA-Z0-9]/.test(v) }
        ];

        const levels = [
            { cls: '', label: '' },
            { cls: 's1', label: 'Muito fraca' },
            { cls: 's2', label: 'Fraca' },
            { cls: 's3', label: 'Razoável' },
            { cls: 's4', label: 'Boa' },
            { cls: 's5', label: 'Forte' }
        ];

        pwd.addEventListener('focus', () => panel.classList.add('visible'));

        pwd.addEventListener('input', () => {
            const val = pwd.value;
            let score = 0;

            rules.forEach((rule) => {
                const el = document.getElementById(rule.id);
                const passed = rule.test(val);
                if (el) el.classList.toggle('pass', passed);
                if (passed) score++;
            });

            barFill.className = 'pwd-bar-fill' + (score > 0 ? ' s' + score : '');
            levelEl.className = 'pwd-level' + (score > 0 ? ' s' + score : '');
            levelEl.textContent = levels[score].label;

            if (confirmEl.value) checkMatch();
        });

        function checkMatch() {
            const pVal = pwd.value;
            const cVal = confirmEl.value;
            if (!cVal) {
                matchEl.classList.remove('visible');
                return;
            }
            matchEl.classList.add('visible');
            const ok = pVal === cVal;
            matchEl.classList.toggle('match', ok);
            matchEl.classList.toggle('no-match', !ok);
            const icon = matchEl.querySelector('.match-icon');
            const text = matchEl.querySelector('.match-text');
            icon.innerHTML = ok ? '<i data-lucide="check"></i>' : '<i data-lucide="x"></i>';
            text.textContent = ok ? 'Senhas coincidem' : 'Senhas não coincidem';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        confirmEl.addEventListener('input', checkMatch);
        pwd.addEventListener('input', () => { if (confirmEl.value) checkMatch(); });
    }
}

// =====================
// Referral Code
// =====================
{
    const referralInput = document.getElementById('referral_code');
    const referralHint = document.getElementById('referralHint');
    const referralError = document.getElementById('referralError');
    let referralValidationTimeout = null;
    let validatedReferralCode = null;

    function initReferralCode() {
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref');

        if (refCode && referralInput) {
            referralInput.value = refCode.toUpperCase();
            validateReferralCode(refCode);
            updateGoogleRegisterLink();

            const card = document.querySelector('.card');
            const registerBtn = document.querySelector('.tab-btn[data-tab="register"]');
            if (card && registerBtn) {
                card.dataset.active = 'register';
                document.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('is-active'));
                registerBtn.classList.add('is-active');
            }
        }
    }

    function updateGoogleRegisterLink() {
        const googleRegisterBtn = document.querySelector('a[href*="auth/google/register"]');
        if (!googleRegisterBtn) return;
        const base = BASE + 'auth/google/register';
        const code = referralInput ? referralInput.value.trim() : '';
        googleRegisterBtn.href = code ? `${base}?ref=${encodeURIComponent(code)}` : base;
    }

    async function validateReferralCode(code) {
        if (!code || code.length < 4) {
            referralHint.textContent = '';
            referralHint.className = 'field-hint';
            referralError.textContent = '';
            validatedReferralCode = null;
            return;
        }

        try {
            const response = await fetch(
                `${BASE}api/referral/validate?code=${encodeURIComponent(code)}`,
                { headers: { 'Accept': 'application/json' } }
            );
            const data = await response.json();

            if (response.ok && data.success) {
                referralHint.innerHTML =
                    `<i data-lucide="check"></i> Indicado por <strong>${data.data.referrer_name}</strong> - Você ganha ${data.data.reward_days} dias de PRO!`;
                referralHint.className = 'field-hint valid';
                referralError.textContent = '';
                validatedReferralCode = code;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                referralHint.textContent = '';
                referralHint.className = 'field-hint';
                referralError.textContent = data.message || 'Código inválido';
                validatedReferralCode = null;
            }
        } catch (err) {
            referralHint.textContent = '';
            referralHint.className = 'field-hint';
            referralError.textContent = 'Erro ao validar código';
            validatedReferralCode = null;
        }
    }

    if (referralInput) {
        referralInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
            updateGoogleRegisterLink();

            clearTimeout(referralValidationTimeout);
            referralValidationTimeout = setTimeout(() => {
                validateReferralCode(e.target.value.trim());
            }, 500);
        });

        initReferralCode();
    }
}

// =====================
// Tabs
// =====================
{
    const card = document.querySelector('.card');
    const tabBtns = document.querySelectorAll('.tab-btn');

    tabBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            card.dataset.active = tab;
            tabBtns.forEach((b) => b.classList.remove('is-active'));
            btn.classList.add('is-active');
        });
    });
}

// =====================
// Error Helpers
// =====================

function showError(inputId, errorId, message) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (error) error.textContent = message;
    if (input) {
        input.style.borderColor = 'var(--error)';
        input.addEventListener('input', () => {
            input.style.borderColor = 'transparent';
            if (error) error.textContent = '';
        }, { once: true });
    }
}

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach((el) => { el.textContent = ''; });
    form.querySelectorAll('input').forEach((el) => { el.style.borderColor = 'transparent'; });
    form.querySelectorAll('.general-message').forEach((el) => {
        el.textContent = '';
        el.classList.remove('show');
    });
}

// =====================
// LOGIN FORM (AJAX)
// =====================
{
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors(loginForm);

            try {
                await refreshCsrfForForm('login_form');
            } catch (err) {
                console.warn('Não foi possível renovar CSRF antes do submit, continuando...', err);
            }

            const emailVal = document.getElementById('email').value.trim();
            const passwordVal = document.getElementById('password').value;
            let hasError = false;

            if (!emailVal) {
                showError('email', 'emailError', 'Digite seu e-mail');
                hasError = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                showError('email', 'emailError', 'E-mail inválido');
                hasError = true;
            }

            if (!passwordVal) {
                showError('password', 'passwordError', 'Digite sua senha');
                hasError = true;
            }

            if (hasError) return;

            const btn = loginForm.querySelector('.btn-primary');
            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span>Entrando...</span>';

            const generalError = document.getElementById('generalError');
            const generalSuccess = document.getElementById('generalSuccess');

            let hasRetried = false;

            async function attemptLogin() {
                const formData = new FormData(loginForm);

                // Injetar token Turnstile se o CAPTCHA está visível
                if (TurnstileManager.loginRequired) {
                    const turnstileToken = TurnstileManager.getLoginToken();
                    if (!turnstileToken) {
                        const captchaError = document.getElementById('captchaError');
                        if (captchaError) captchaError.textContent = 'Complete a verificação de segurança.';
                        btn.disabled = false;
                        btn.innerHTML = originalBtnHtml;
                        return { response: { ok: false, status: 422 }, data: { success: false, message: 'Complete a verificação de segurança.', _captchaBlock: true } };
                    }
                    // Limpa mensagem de captcha anterior
                    const captchaError = document.getElementById('captchaError');
                    if (captchaError) captchaError.textContent = '';
                    formData.set('cf-turnstile-response', turnstileToken);
                }

                const response = await fetch(loginForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                let data = null;
                try { data = await response.json(); } catch (e) { /* non-JSON */ }

                if (isCsrfError(response, data) && !hasRetried) {
                    hasRetried = true;
                    try {
                        await refreshCsrfForForm('login_form');
                        return attemptLogin();
                    } catch (refreshErr) {
                        return {
                            response,
                            data: {
                                success: false,
                                message: 'Sessão expirada. Por favor, recarregue a página e tente novamente.'
                            }
                        };
                    }
                }

                return { response, data };
            }

            try {
                const { response, data } = await attemptLogin();

                // Se foi bloqueio local de captcha, não processar mais (botão já foi reabilitado)
                if (data?._captchaBlock) return;

                const success = data && (data.success === true || data.status === 'success');

                if (!response.ok || !success) {
                    let message;
                    if (isCsrfError(response, data)) {
                        message = 'Sessão expirada. A página será recarregada...';
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        message = (data && data.message) ||
                            (response.status === 429 ?
                                'Muitas tentativas. Aguarde um pouco e tente novamente.' :
                                'E-mail ou senha inválidos.');
                    }

                    // Se o servidor pede CAPTCHA, mostrar o widget
                    if (data?.errors?.require_captcha) {
                        TurnstileManager.showLoginCaptcha();
                    }

                    // Contagem client-side de falhas (fallback para quando Redis não está disponível)
                    TurnstileManager.recordLoginFailure();

                    // Reset do Turnstile para nova tentativa
                    TurnstileManager.resetLogin();

                    if (generalError) {
                        generalError.textContent = message;
                        generalError.classList.add('show');
                    }

                    if (data?.errors && typeof data.errors === 'object') {
                        if (data.errors.email) {
                            showError('email', 'emailError',
                                Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email);
                        }
                        if (data.errors.password) {
                            showError('password', 'passwordError',
                                Array.isArray(data.errors.password) ? data.errors.password[0] : data.errors.password);
                        }
                    }

                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                    return;
                }

                if (generalSuccess) {
                    generalSuccess.textContent = data.message || 'Login realizado com sucesso!';
                    generalSuccess.classList.add('show');
                }

                // Login OK: limpa contador de falhas
                TurnstileManager.clearLoginFailures();

                const redirectUrl = data?.redirect || BASE + 'dashboard';
                setTimeout(() => { window.location.href = redirectUrl; }, 800);

            } catch (error) {
                console.error('Erro na requisição de login:', error);
                if (generalError) {
                    generalError.textContent =
                        'Não foi possível realizar o login. Tente novamente em instantes.';
                    generalError.classList.add('show');
                }
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        });
    }
}

// =====================
// REGISTER FORM (AJAX)
// =====================
{
    const registerForm = document.getElementById('registerForm');

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors(registerForm);

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('reg_email').value.trim();
            const password = document.getElementById('reg_password').value;
            const confirmVal = document.getElementById('reg_password_confirm').value;
            let hasError = false;

            if (!name) {
                showError('name', 'nameError', 'Digite seu nome completo');
                hasError = true;
            }

            if (!email) {
                showError('reg_email', 'regEmailError', 'Digite seu e-mail');
                hasError = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('reg_email', 'regEmailError', 'E-mail inválido');
                hasError = true;
            }

            if (!password) {
                showError('reg_password', 'regPasswordError', 'Digite sua senha');
                hasError = true;
            } else {
                const pwdErrors = [];
                if (password.length < 8) pwdErrors.push('mínimo 8 caracteres');
                if (!/[a-z]/.test(password)) pwdErrors.push('uma letra minúscula');
                if (!/[A-Z]/.test(password)) pwdErrors.push('uma letra maiúscula');
                if (!/[0-9]/.test(password)) pwdErrors.push('um número');
                if (!/[^a-zA-Z0-9]/.test(password)) pwdErrors.push('um caractere especial');
                if (pwdErrors.length) {
                    showError('reg_password', 'regPasswordError', 'Falta: ' + pwdErrors.join(', '));
                    hasError = true;
                }
            }

            if (!confirmVal) {
                showError('reg_password_confirm', 'regPasswordConfirmError', 'Confirme sua senha');
                hasError = true;
            } else if (password !== confirmVal) {
                showError('reg_password_confirm', 'regPasswordConfirmError', 'As senhas não coincidem');
                hasError = true;
            }

            if (hasError) return;

            const btn = registerForm.querySelector('.btn-primary');
            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span>Criando conta...</span>';

            let hasRetried = false;

            async function attemptRegister() {
                const formData = new FormData(registerForm);

                // Injetar token Turnstile no registro
                if (TurnstileManager.registerWidgetId !== null) {
                    const turnstileToken = TurnstileManager.getRegisterToken();
                    if (!turnstileToken) {
                        const captchaError = document.getElementById('regCaptchaError');
                        if (captchaError) captchaError.textContent = 'Complete a verificação de segurança.';
                        return { response: { ok: false, status: 422 }, data: { success: false, message: 'Complete a verificação de segurança.' } };
                    }
                    formData.set('cf-turnstile-response', turnstileToken);
                }

                const response = await fetch(registerForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                let data = null;
                try { data = await response.json(); } catch (e) { /* non-JSON */ }

                if (isCsrfError(response, data) && !hasRetried) {
                    hasRetried = true;
                    try {
                        await refreshCsrfForForm('register_form');
                        return attemptRegister();
                    } catch (refreshErr) {
                        throw new Error('Sessão expirada. Por favor, recarregue a página.');
                    }
                }

                return { response, data };
            }

            try {
                const { response, data } = await attemptRegister();

                if (!response.ok || !(data?.success === true)) {
                    if (isCsrfError(response, data)) {
                        throw new Error('Sessão expirada. A página será recarregada...');
                    }

                    if (response.status === 422 && data?.errors) {
                        if (data.errors.email) {
                            showError('reg_email', 'regEmailError',
                                Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email);
                        }
                        if (data.errors.name) {
                            showError('name', 'nameError',
                                Array.isArray(data.errors.name) ? data.errors.name[0] : data.errors.name);
                        }
                        if (data.errors.password) {
                            showError('reg_password', 'regPasswordError',
                                Array.isArray(data.errors.password) ? data.errors.password[0] : data.errors.password);
                        }
                        if (data.errors.password_confirmation) {
                            showError('reg_password_confirm', 'regPasswordConfirmError',
                                Array.isArray(data.errors.password_confirmation)
                                    ? data.errors.password_confirmation[0]
                                    : data.errors.password_confirmation);
                        }

                        const apiMessage = data.message || '';
                        const isEmailDuplicate = apiMessage.toLowerCase().includes('cadastrado') ||
                            apiMessage.toLowerCase().includes('já existe') ||
                            (data.errors.email && String(data.errors.email).toLowerCase().includes('cadastrado'));

                        Swal.fire({
                            icon: isEmailDuplicate ? 'warning' : 'error',
                            title: isEmailDuplicate ? 'E-mail já cadastrado' : 'Erro no cadastro',
                            text: isEmailDuplicate ?
                                'Já existe uma conta com este e-mail. Tente fazer login ou use outro e-mail.' :
                                (apiMessage || 'Corrija os campos destacados e tente novamente.'),
                        });

                        TurnstileManager.resetRegister();
                        btn.disabled = false;
                        btn.innerHTML = originalBtnHtml;
                        return;
                    }

                    throw new Error(data?.message || 'Erro ao criar conta.');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Conta criada com sucesso!',
                    text: data.message || 'Agora você pode fazer login.',
                    timer: 2000,
                    showConfirmButton: false
                });

                setTimeout(() => {
                    window.location.href = data.redirect || BASE + 'login';
                }, 2000);

            } catch (err) {
                const message = err.message || 'Erro ao criar conta.';

                if (message.toLowerCase().includes('sessão expirada') || message.toLowerCase().includes('csrf')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sessão expirada',
                        text: 'A página será recarregada...',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                if (message.toLowerCase().includes('sucesso')) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Conta criada com sucesso!',
                        text: message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => { window.location.href = BASE + 'login'; }, 2000);
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Não foi possível criar a conta',
                    text: message
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        });
    }
}
