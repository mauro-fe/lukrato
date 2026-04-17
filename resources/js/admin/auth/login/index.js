/**
 * ============================================================================
 * LUKRATO — Login / Register Page (Vite Module)
 * ============================================================================
 * CSRF refresh, password strength, referral codes, tabs, form handlers.
 *
 * Substitui: public/assets/js/auth/login.js + auth-shared.js
 * ============================================================================
 */

import { createParticles, initTogglePassword, getBaseUrl } from '../shared.js';
import { apiFetch, apiGet, apiPost, getCSRFToken, getErrorMessage } from '../../shared/api.js';
import {
    resolveAuthGoogleLoginEndpoint,
    resolveAuthGoogleRegisterEndpoint,
    resolveAuthLoginEndpoint,
    resolveAuthRegisterEndpoint,
} from '../../api/endpoints/auth.js';
import { resolveReferralValidateEndpoint } from '../../api/endpoints/engagement.js';
import { resolveCsrfRefreshEndpoint } from '../../api/endpoints/security.js';

const BASE = getBaseUrl();
let refreshAuthCardLayout = () => { };
let setAuthCardTab = () => { };

function getVerifyEmailNoticeUrl() {
    return document.querySelector('meta[name="verify-email-notice-url"]')?.content || `${BASE}verificar-email/aviso`;
}

// ── Init shared features ───────────────────────────────────────────────────
createParticles();
initTogglePassword();
if (typeof window.lucide !== 'undefined') {
    window.lucide.createIcons();
}
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
        refreshAuthCardLayout();
        window.setTimeout(refreshAuthCardLayout, 120);
        window.setTimeout(refreshAuthCardLayout, 320);
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
        refreshAuthCardLayout();
        window.setTimeout(refreshAuthCardLayout, 120);
        window.setTimeout(refreshAuthCardLayout, 320);
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
    const data = await apiPost(resolveCsrfRefreshEndpoint(), { token_id: tokenId });
    const payload = data?.data && typeof data.data === 'object' ? data.data : data;
    const token = typeof payload?.token === 'string' ? payload.token : '';

    if (token) {
        document.querySelectorAll(`meta[data-csrf-id="${tokenId}"]`).forEach((metaTag) => {
            metaTag.setAttribute('content', token);
        });

        if (tokenId === 'register_form') {
            const registerMeta = document.querySelector('meta[name="csrf-token-register"]');
            if (registerMeta) registerMeta.setAttribute('content', token);
        }

        document.querySelectorAll('input[name="csrf_token"]').forEach((input) => {
            const formId = input.closest('form')?.id;
            if (tokenId === 'login_form' && formId === 'loginForm') {
                input.value = token;
            } else if (tokenId === 'register_form' && formId === 'registerForm') {
                input.value = token;
            }
        });

        return token;
    }
    throw new Error('Token não recebido');
}

function getFormCsrfToken(formId, tokenId) {
    const formToken = document.querySelector(`#${formId} input[name="csrf_token"]`)?.value;
    if (formToken) {
        return formToken;
    }

    if (tokenId === 'register_form') {
        return document.querySelector('meta[name="csrf-token-register"]')?.content || '';
    }

    return document.querySelector(`meta[data-csrf-id="${tokenId}"]`)?.content
        || getCSRFToken()
        || '';
}

async function resolveApiResult(request) {
    try {
        const data = await request();
        return {
            response: { ok: true, status: 200 },
            data,
            error: null,
        };
    } catch (error) {
        return {
            response: { ok: false, status: Number(error?.status || 0) },
            data: error?.data || null,
            error,
        };
    }
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

    function refreshAuthFormTokens() {
        const refreshJobs = [];

        if (document.getElementById('loginForm')) {
            refreshJobs.push(refreshCsrfForForm('login_form'));
        }

        if (document.getElementById('registerForm')) {
            refreshJobs.push(refreshCsrfForForm('register_form'));
        }

        if (refreshJobs.length === 0) {
            return;
        }

        void Promise.allSettled(refreshJobs);
    }

    function maybeRefreshToken() {
        const now = Date.now();
        if (now - lastRefresh > MIN_REFRESH_GAP) {
            lastRefresh = now;
            refreshAuthFormTokens();
        }
    }

    document.querySelectorAll('#loginForm input, #registerForm input').forEach((input) => {
        input.addEventListener('focus', maybeRefreshToken, { passive: true });
    });

    setInterval(() => {
        refreshAuthFormTokens();
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

        pwd.addEventListener('focus', () => {
            panel.classList.add('visible');
            refreshAuthCardLayout();
        });

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
            refreshAuthCardLayout();
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
            refreshAuthCardLayout();
        }

        confirmEl.addEventListener('input', checkMatch);
        pwd.addEventListener('input', () => { if (confirmEl.value) checkMatch(); });
    }
}

// =====================
// Auth Card Layout
// =====================
const AuthCard = (() => {
    const card = document.querySelector('.card');
    const flipContainer = card?.querySelector('.flip-container');
    const flipInner = card?.querySelector('.flip-inner');
    const tabBtns = Array.from(document.querySelectorAll('.tab-btn'));
    const panels = {
        login: card?.querySelector('.flip-login') || null,
        register: card?.querySelector('.flip-register') || null,
    };
    let activeTab = card?.dataset.active === 'register' ? 'register' : 'login';
    let rafId = 0;
    let resizeObserver = null;

    function getPanelHeight(tab) {
        const panel = panels[tab];
        return panel ? Math.ceil(panel.scrollHeight) : 0;
    }

    function syncTabState() {
        tabBtns.forEach((btn) => {
            const isActive = btn.dataset.tab === activeTab;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        Object.entries(panels).forEach(([tab, panel]) => {
            if (!panel) return;
            const isActive = tab === activeTab;
            panel.classList.toggle('is-active', isActive);
            panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
    }

    function syncLayout() {
        if (!card || !flipContainer || !flipInner) return;

        card.dataset.active = activeTab;

        const loginHeight = getPanelHeight('login');
        const registerHeight = getPanelHeight('register');
        const activeHeight = activeTab === 'register' ? registerHeight : loginHeight;

        flipContainer.style.height = `${Math.max(activeHeight, 1)}px`;
        flipInner.style.height = `${Math.max(activeHeight, 1)}px`;
        syncTabState();
        card.classList.add('is-auth-ready');
    }

    function refresh() {
        window.cancelAnimationFrame(rafId);
        rafId = window.requestAnimationFrame(syncLayout);
    }

    function setActiveTab(tab) {
        if (!panels[tab]) return;
        if (activeTab === tab) return;
        activeTab = tab;
        if (card) {
            card.dataset.active = activeTab;
        }
        syncTabState();
        refresh();
    }

    function init() {
        if (!card || !flipContainer || !flipInner) return;

        tabBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                setActiveTab(btn.dataset.tab);
            });
        });

        if (typeof ResizeObserver === 'function') {
            resizeObserver = new ResizeObserver(() => {
                refresh();
            });

            Object.values(panels).forEach((panel) => {
                if (panel) resizeObserver.observe(panel);
            });
        }

        window.addEventListener('resize', refresh, { passive: true });
        window.addEventListener('load', refresh, { once: true });
        window.setTimeout(refresh, 180);
        window.setTimeout(refresh, 420);
        syncTabState();
        refresh();
    }

    return {
        init,
        refresh,
        setActiveTab,
    };
})();

refreshAuthCardLayout = AuthCard.refresh;
setAuthCardTab = AuthCard.setActiveTab;
AuthCard.init();

// =====================
// Referral Code
// =====================
{
    const referralInput = document.getElementById('referral_code');
    const referralHint = document.getElementById('referralHint');
    const referralError = document.getElementById('referralError');
    let referralValidationTimeout = null;

    function updateGoogleAuthLinks() {
        const googleLoginBtn = document.querySelector('[data-google-auth="login"]');
        if (googleLoginBtn) {
            googleLoginBtn.href = BASE + resolveAuthGoogleLoginEndpoint();
        }

        const googleRegisterBtn = document.querySelector('[data-google-auth="register"]');
        if (!googleRegisterBtn) return;

        const base = BASE + resolveAuthGoogleRegisterEndpoint();
        const code = referralInput ? referralInput.value.trim() : '';
        googleRegisterBtn.href = code ? `${base}?ref=${encodeURIComponent(code)}` : base;
    }

    function initReferralCode() {
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref');

        updateGoogleAuthLinks();

        if (refCode && referralInput) {
            referralInput.value = refCode.toUpperCase();
            validateReferralCode(refCode);
            updateGoogleAuthLinks();
            setAuthCardTab('register');
        }
    }

    async function validateReferralCode(code) {
        if (!code || code.length < 4) {
            referralHint.textContent = '';
            referralHint.className = 'field-hint';
            referralError.textContent = '';
            refreshAuthCardLayout();
            return;
        }

        try {
            const data = await apiGet(resolveReferralValidateEndpoint(), { code });

            if (data?.success) {
                referralHint.innerHTML =
                    `<i data-lucide="check"></i> Indicado por <strong>${data.data.referrer_name}</strong> - Você ganha ${data.data.reward_days} dias de PRO!`;
                referralHint.className = 'field-hint valid';
                referralError.textContent = '';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                refreshAuthCardLayout();
            } else {
                referralHint.textContent = '';
                referralHint.className = 'field-hint';
                referralError.textContent = getErrorMessage({ data }, 'Código inválido');
                refreshAuthCardLayout();
            }
        } catch (err) {
            referralHint.textContent = '';
            referralHint.className = 'field-hint';
            referralError.textContent = getErrorMessage(err, 'Erro ao validar código');
            refreshAuthCardLayout();
        }
    }

    updateGoogleAuthLinks();

    if (referralInput) {
        referralInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
            updateGoogleAuthLinks();

            clearTimeout(referralValidationTimeout);
            referralValidationTimeout = setTimeout(() => {
                validateReferralCode(e.target.value.trim());
            }, 500);
        });

        initReferralCode();
    }
}

// =====================
// Error Helpers
// =====================

function showError(inputId, errorId, message) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (error) error.textContent = message;
    refreshAuthCardLayout();
    if (input) {
        input.style.borderColor = 'var(--error)';
        input.addEventListener('input', () => {
            input.style.borderColor = 'transparent';
            if (error) error.textContent = '';
            refreshAuthCardLayout();
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
    refreshAuthCardLayout();
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

                if (TurnstileManager.loginRequired) {
                    const turnstileToken = TurnstileManager.getLoginToken();
                    if (!turnstileToken) {
                        const captchaError = document.getElementById('captchaError');
                        if (captchaError) captchaError.textContent = 'Complete a verificação de segurança.';
                        btn.disabled = false;
                        btn.innerHTML = originalBtnHtml;
                        return { response: { ok: false, status: 422 }, data: { success: false, message: 'Complete a verificação de segurança.', _captchaBlock: true } };
                    }
                    const captchaError = document.getElementById('captchaError');
                    if (captchaError) captchaError.textContent = '';
                    formData.set('cf-turnstile-response', turnstileToken);
                }

                const result = await resolveApiResult(() => apiFetch(resolveAuthLoginEndpoint(), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': getFormCsrfToken('loginForm', 'login_form'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }));

                if (isCsrfError(result.response, result.data) && !hasRetried) {
                    hasRetried = true;
                    try {
                        await refreshCsrfForForm('login_form');
                        return attemptLogin();
                    } catch {
                        return {
                            response: result.response,
                            data: {
                                success: false,
                                message: 'Sessão expirada. Por favor, recarregue a página e tente novamente.'
                            }
                        };
                    }
                }

                return result;
            }
            try {
                const { response, data } = await attemptLogin();

                // Se foi bloqueio local de captcha, não processar mais (botão já foi reabilitado)
                if (data?._captchaBlock) return;

                const success = data && data.success;

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

                    // Email não verificado: mostrar opção de reenviar
                    if (data?.errors?.email_not_verified) {
                        btn.disabled = false;
                        btn.innerHTML = originalBtnHtml;
                        window.location.href = getVerifyEmailNoticeUrl();
                        return;
                    }

                    // Contagem client-side de falhas (fallback para quando Redis não está disponível)
                    TurnstileManager.recordLoginFailure();

                    // Reset do Turnstile para nova tentativa
                    TurnstileManager.resetLogin();

                    if (generalError) {
                        generalError.textContent = message;
                        generalError.classList.add('show');
                        refreshAuthCardLayout();
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
                    refreshAuthCardLayout();
                }

                // Login OK: limpa contador de falhas
                TurnstileManager.clearLoginFailures();

                // Prioridade: intended (da meta tag) > redirect do servidor > dashboard
                const intendedMeta = document.querySelector('meta[name="intended-redirect"]');
                const intended = intendedMeta?.content || '';
                const redirectUrl = (intended ? BASE + intended : null) || data?.redirect || BASE + 'dashboard';
                setTimeout(() => { window.location.href = redirectUrl; }, 800);

            } catch (error) {
                console.error('Erro na requisição de login:', error);
                if (generalError) {
                    generalError.textContent =
                        'Não foi possível realizar o login. Tente novamente em instantes.';
                    generalError.classList.add('show');
                    refreshAuthCardLayout();
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

            const email = document.getElementById('reg_email').value.trim();
            const password = document.getElementById('reg_password').value;
            const confirmVal = document.getElementById('reg_password_confirm').value;
            let hasError = false;

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

                if (TurnstileManager.registerWidgetId !== null) {
                    const turnstileToken = TurnstileManager.getRegisterToken();
                    if (!turnstileToken) {
                        const captchaError = document.getElementById('regCaptchaError');
                        if (captchaError) captchaError.textContent = 'Complete a verificação de segurança.';
                        return { response: { ok: false, status: 422 }, data: { success: false, message: 'Complete a verificação de segurança.' } };
                    }
                    formData.set('cf-turnstile-response', turnstileToken);
                }

                const result = await resolveApiResult(() => apiFetch(resolveAuthRegisterEndpoint(), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': getFormCsrfToken('registerForm', 'register_form'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }));

                if (isCsrfError(result.response, result.data) && !hasRetried) {
                    hasRetried = true;
                    try {
                        await refreshCsrfForForm('register_form');
                        return attemptRegister();
                    } catch {
                        throw new Error('Sessão expirada. Por favor, recarregue a página.');
                    }
                }

                return result;
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
                    text: data.message || 'Verifique seu e-mail para ativar sua conta.',
                    timer: 2000,
                    showConfirmButton: false
                });

                setTimeout(() => {
                    window.location.href = data.redirect || BASE + 'login';
                }, 2000);

            } catch (err) {
                const message = getErrorMessage(err, 'Erro ao criar conta.');

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
