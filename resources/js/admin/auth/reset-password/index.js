/**
 * ============================================================================
 * LUKRATO - Reset Password Page (Vite Module)
 * ============================================================================
 */

import { createParticles, createConfetti, initTogglePassword } from '../shared.js';
import {
    resolveAuthResetPasswordEndpoint,
    resolveAuthResetPasswordValidateEndpoint,
} from '../../api/endpoints/auth.js';
import { apiFetch, buildUrl, getErrorMessage } from '../../shared/api.js';

// -- Init shared features ---------------------------------------------------
createParticles();
initTogglePassword();
if (typeof window.lucide !== 'undefined') {
    window.lucide.createIcons();
}

// =====================
// Password Strength Indicator
// =====================
{
    const passwordInput = document.getElementById('password');
    const strengthBarInner = document.querySelector('.password-strength-bar');

    if (passwordInput && strengthBarInner) {
        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;

            if (val.length >= 8) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;

            strengthBarInner.classList.remove('weak', 'medium', 'strong');

            if (val.length === 0) {
                strengthBarInner.style.width = '0';
            } else if (score <= 1) {
                strengthBarInner.style.width = '33%';
                strengthBarInner.classList.add('weak');
            } else if (score <= 2) {
                strengthBarInner.style.width = '66%';
                strengthBarInner.classList.add('medium');
            } else {
                strengthBarInner.style.width = '100%';
                strengthBarInner.classList.add('strong');
            }
        });
    }
}

// =====================
// Form Handler
// =====================
{
    const root = document.querySelector('[data-reset-password-root]');
    const form = document.getElementById('resetForm');
    const messageContainer = document.getElementById('messageContainer');
    const submitBtn = document.getElementById('submitBtn');
    const tokenInput = document.querySelector('[data-reset-token]');
    const selectorInput = document.querySelector('[data-reset-selector]');
    const validatorInput = document.querySelector('[data-reset-validator]');
    const forgotPasswordUrl = root?.dataset.forgotPasswordUrl || '/recuperar-senha';
    const loginUrl = root?.dataset.loginUrl || '/login';
    const resetSubmitEndpoint = root?.dataset.resetSubmitEndpoint || resolveAuthResetPasswordEndpoint();
    const resetValidateEndpoint = root?.dataset.resetValidateEndpoint || resolveAuthResetPasswordValidateEndpoint();
    let linkValidated = false;

    function showMessage(text, type = 'error') {
        if (!messageContainer) return;
        messageContainer.innerHTML = `<div style="padding:12px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;background:${type === 'success' ? 'rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.2)'}">${text}</div>`;
    }

    function clearMessage() {
        if (messageContainer) messageContainer.innerHTML = '';
    }

    function setSubmitState(enabled, label = 'Redefinir senha') {
        if (!submitBtn) {
            return;
        }

        submitBtn.disabled = !enabled;
        submitBtn.innerHTML = `<span>${label}</span>`;
    }

    function getResetCredentials() {
        const params = new URLSearchParams(window.location.search);

        return {
            token: params.get('token') || '',
            selector: params.get('selector') || '',
            validator: params.get('validator') || '',
        };
    }

    function hasValidCredentialShape({ token, selector, validator }) {
        return token !== '' || (selector !== '' && validator !== '');
    }

    function populateHiddenInputs({ token, selector, validator }) {
        if (tokenInput) {
            tokenInput.value = token;
        }

        if (selectorInput) {
            selectorInput.value = selector;
        }

        if (validatorInput) {
            validatorInput.value = validator;
        }
    }

    async function validateResetLink() {
        const credentials = getResetCredentials();

        if (!hasValidCredentialShape(credentials)) {
            showMessage('Link de redefinição inválido ou incompleto.');
            window.setTimeout(() => {
                window.location.href = forgotPasswordUrl;
            }, 1800);
            return;
        }

        setSubmitState(false, 'Validando link...');

        try {
            await apiFetch(buildUrl(resetValidateEndpoint, credentials), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            }, {
                suppressErrorLogging: true,
            });

            populateHiddenInputs(credentials);
            linkValidated = true;
            clearMessage();
            setSubmitState(true);
        } catch (err) {
            showMessage(getErrorMessage(err, 'Não foi possível validar seu link de redefinição.'));
            setSubmitState(false);

            const redirect = err?.data?.errors?.redirect || forgotPasswordUrl;
            window.setTimeout(() => {
                window.location.href = redirect;
            }, 1800);
        }
    }

    if (form && root) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearMessage();

            if (!linkValidated) {
                showMessage('Aguarde a validação do link antes de redefinir sua senha.');
                return;
            }

            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;

            if (password.length < 8) {
                showMessage('A senha deve ter pelo menos 8 caracteres.');
                return;
            }

            if (!/[a-z]/.test(password)) {
                showMessage('A senha deve conter pelo menos uma letra minúscula.');
                return;
            }

            if (!/[A-Z]/.test(password)) {
                showMessage('A senha deve conter pelo menos uma letra maiúscula.');
                return;
            }

            if (!/[0-9]/.test(password)) {
                showMessage('A senha deve conter pelo menos um número.');
                return;
            }

            if (!/[^a-zA-Z0-9]/.test(password)) {
                showMessage('A senha deve conter pelo menos um caractere especial.');
                return;
            }

            if (password !== confirm) {
                showMessage('As senhas não coincidem.');
                return;
            }

            const originalHtml = submitBtn?.innerHTML || '<span>Redefinir senha</span>';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Redefinindo...</span>';
            }

            try {
                const formData = new FormData(form);
                const data = await apiFetch(resetSubmitEndpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (data?.success) {
                    form.style.display = 'none';
                    showMessage(data.message || 'Senha redefinida com sucesso!', 'success');
                    createConfetti(200);
                    setTimeout(() => {
                        window.location.href = data?.data?.redirect || loginUrl;
                    }, 2000);
                } else {
                    showMessage(getErrorMessage({ data }, 'Erro ao redefinir senha.'));
                }
            } catch (err) {
                showMessage(getErrorMessage(err, 'Erro de conexão. Tente novamente.'));
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            }
        });

        validateResetLink();
    }
}
