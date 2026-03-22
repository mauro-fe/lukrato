/**
 * ============================================================================
 * LUKRATO - Reset Password Page (Vite Module)
 * ============================================================================
 */

import { createParticles, createConfetti, initTogglePassword, getBaseUrl } from '../shared.js';
import { apiFetch, getErrorMessage } from '../../shared/api.js';

const BASE = getBaseUrl();

// -- Init shared features ---------------------------------------------------
createParticles();
initTogglePassword();

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
    const form = document.getElementById('resetForm');
    const messageContainer = document.getElementById('messageContainer');

    function showMessage(text, type = 'error') {
        if (!messageContainer) return;
        messageContainer.innerHTML = `<div style="padding:12px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;background:${type === 'success' ? 'rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.2)'}">${text}</div>`;
    }

    function clearMessage() {
        if (messageContainer) messageContainer.innerHTML = '';
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearMessage();

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

            const btn = form.querySelector('.btn-primary');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span>Redefinindo...</span>';

            try {
                const formData = new FormData(form);
                const data = await apiFetch(form.action, {
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
                        window.location.href = data.redirect || BASE + 'login';
                    }, 2000);
                } else {
                    showMessage(getErrorMessage({ data }, 'Erro ao redefinir senha.'));
                }
            } catch (err) {
                showMessage(getErrorMessage(err, 'Erro de conexão. Tente novamente.'));
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }
}
