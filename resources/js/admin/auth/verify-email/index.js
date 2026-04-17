/**
 * ============================================================================
 * LUKRATO - Verify Email Page (Vite Module)
 * ============================================================================
 */

import {
    resolveAuthEmailNoticeEndpoint,
    resolveAuthResendVerificationEndpoint,
    resolveAuthVerifyEmailEndpoint,
} from '../../api/endpoints/auth.js';
import { apiFetch, buildUrl, getCSRFToken, getErrorMessage } from '../../shared/api.js';

// =====================
// Notice + Resend Verification Form
// =====================
{
    const root = document.querySelector('[data-verify-email-root]');
    const resendForm = document.getElementById('resendForm');
    const resendMessage = document.getElementById('resendMessage');
    const messageElement = document.querySelector('[data-verify-email-message]');
    const emailHighlight = document.querySelector('[data-verify-email-highlight]');
    const emailAddress = document.querySelector('[data-verify-email-address]');
    const emailInput = document.querySelector('[data-verify-email-input]');
    const loginLink = document.querySelector('[data-verify-email-login-link]');
    const loginUrl = root?.dataset.loginUrl || '/login';
    const verifyEndpoint = root?.dataset.verifyEndpoint || resolveAuthVerifyEmailEndpoint();
    const noticeEndpoint = root?.dataset.noticeEndpoint || resolveAuthEmailNoticeEndpoint();
    const resendEndpoint = root?.dataset.resendEndpoint || resolveAuthResendVerificationEndpoint();
    let activeResendEndpoint = resendEndpoint;

    function showResendMsg(text, type = 'error') {
        if (!resendMessage) return;
        resendMessage.textContent = text;
        resendMessage.className = 'resend-message ' + (type === 'success' ? 'success' : 'error');
    }

    function setResendVisibility(visible) {
        if (!resendForm) {
            return;
        }

        resendForm.hidden = !visible;
    }

    function getVerificationCredentials() {
        const params = new URLSearchParams(window.location.search);

        return {
            token: params.get('token') || '',
            selector: params.get('selector') || '',
            validator: params.get('validator') || '',
        };
    }

    function hasVerificationCredentials({ token, selector, validator }) {
        return token !== '' || (selector !== '' && validator !== '');
    }

    function populateNotice(notice, actions = {}) {
        if (messageElement) {
            messageElement.textContent = notice?.message || 'Por favor, verifique seu email antes de fazer login.';
        }

        if (emailInput) {
            emailInput.value = notice?.email || '';
        }

        if (emailHighlight && emailAddress) {
            if (notice?.email) {
                emailAddress.textContent = notice.email;
                emailHighlight.hidden = false;
            } else {
                emailAddress.textContent = '';
                emailHighlight.hidden = true;
            }
        }

        const resolvedLoginUrl = actions?.login_url || loginUrl;
        if (loginLink) {
            loginLink.setAttribute('href', resolvedLoginUrl);
        }

        activeResendEndpoint = typeof actions?.resend_url === 'string' && actions.resend_url.trim() !== ''
            ? actions.resend_url
            : resendEndpoint;
    }

    async function processVerificationLink() {
        if (!root) {
            return false;
        }

        const credentials = getVerificationCredentials();
        if (!hasVerificationCredentials(credentials)) {
            return false;
        }

        if (messageElement) {
            messageElement.textContent = 'Validando seu link de verificacao...';
        }

        showResendMsg('');
        setResendVisibility(false);

        try {
            const data = await apiFetch(buildUrl(verifyEndpoint, credentials), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            }, {
                suppressErrorLogging: true,
            });

            const redirect = data?.data?.redirect || loginUrl;

            populateNotice({
                email: '',
                message: data?.message || 'Email verificado com sucesso! voce ja pode fazer login.',
                expired: false,
            }, {
                login_url: redirect,
            });
            showResendMsg('Email verificado com sucesso. Redirecionando para o login...', 'success');

            window.setTimeout(() => {
                window.location.href = redirect;
            }, 1800);

            return true;
        } catch (err) {
            const redirect = err?.data?.errors?.redirect || loginUrl;
            const expired = Boolean(err?.data?.errors?.expired);
            const message = getErrorMessage(err, 'Nao foi possivel validar seu link de verificacao.');

            populateNotice({
                email: '',
                message,
                expired,
            }, {
                login_url: redirect,
                resend_url: resendEndpoint,
            });

            if (expired) {
                setResendVisibility(true);
                showResendMsg('Voce pode solicitar um novo email de verificacao abaixo.');
                return true;
            }

            showResendMsg(message);
            window.setTimeout(() => {
                window.location.href = redirect;
            }, 1800);

            return true;
        }
    }

    async function hydrateNotice() {
        if (!root) {
            return;
        }

        try {
            const data = await apiFetch(noticeEndpoint, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            }, {
                suppressErrorLogging: true,
            });

            populateNotice(data?.data?.notice || {}, data?.data?.actions || {});
        } catch (err) {
            showResendMsg(getErrorMessage(err, 'Não foi possível carregar seu aviso de verificação.'));
            const redirect = err?.data?.errors?.redirect || loginUrl;

            window.setTimeout(() => {
                window.location.href = redirect;
            }, 1800);
        }
    }

    if (resendForm) {
        resendForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = resendForm.querySelector('button[type="submit"]');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span>Enviando...</span>';

            try {
                const formData = new FormData(resendForm);
                const csrfToken = getCSRFToken();

                const data = await apiFetch(activeResendEndpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                showResendMsg(
                    data?.data?.message || data?.message || 'E-mail reenviado com sucesso!',
                    data?.success === false ? 'error' : 'success'
                );
            } catch (err) {
                showResendMsg(getErrorMessage(err, 'Erro de conexão. Tente novamente.'));
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }

    processVerificationLink().then((handled) => {
        if (!handled) {
            hydrateNotice();
        }
    });
}
