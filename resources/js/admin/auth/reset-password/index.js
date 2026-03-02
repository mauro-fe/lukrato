/**
 * ============================================================================
 * LUKRATO — Reset Password Page (Vite Module)
 * ============================================================================
 */

import { createParticles, createConfetti, initTogglePassword, getBaseUrl } from '../shared.js';

const BASE = getBaseUrl();

// ── Init shared features ───────────────────────────────────────────────────
createParticles();
initTogglePassword();

// =====================
// Password Strength Indicator
// =====================
{
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');

    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;

            if (val.length >= 8) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;

            strengthBar.classList.remove('weak', 'medium', 'strong');

            if (val.length === 0) {
                strengthBar.style.width = '0';
                if (strengthText) strengthText.textContent = '';
            } else if (score <= 1) {
                strengthBar.style.width = '33%';
                strengthBar.classList.add('weak');
                if (strengthText) strengthText.textContent = 'Fraca';
            } else if (score <= 2) {
                strengthBar.style.width = '66%';
                strengthBar.classList.add('medium');
                if (strengthText) strengthText.textContent = 'Média';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.classList.add('strong');
                if (strengthText) strengthText.textContent = 'Forte';
            }
        });
    }
}

// =====================
// Form Handler
// =====================
{
    const form = document.getElementById('resetForm');
    const errorMsg = document.querySelector('.error-message');
    const successMsg = document.querySelector('.success-message');
    const formContainer = document.querySelector('.reset-form');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (errorMsg) { errorMsg.textContent = ''; errorMsg.classList.remove('show'); }
            if (successMsg) { successMsg.textContent = ''; successMsg.classList.remove('show'); }

            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;

            if (password.length < 8) {
                if (errorMsg) {
                    errorMsg.textContent = 'A senha deve ter pelo menos 8 caracteres.';
                    errorMsg.classList.add('show');
                }
                return;
            }

            if (password !== confirm) {
                if (errorMsg) {
                    errorMsg.textContent = 'As senhas não coincidem.';
                    errorMsg.classList.add('show');
                }
                return;
            }

            const btn = form.querySelector('.btn-primary');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span>Redefinindo...</span>';

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (formContainer) formContainer.style.display = 'none';
                    if (successMsg) {
                        successMsg.textContent = data.message || 'Senha redefinida com sucesso!';
                        successMsg.classList.add('show');
                    }
                    createConfetti(200);
                    setTimeout(() => {
                        window.location.href = data.redirect || BASE + 'login';
                    }, 2000);
                } else {
                    if (errorMsg) {
                        errorMsg.textContent = data.message || 'Erro ao redefinir senha.';
                        errorMsg.classList.add('show');
                    }
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            } catch (err) {
                if (errorMsg) {
                    errorMsg.textContent = 'Erro de conexão. Tente novamente.';
                    errorMsg.classList.add('show');
                }
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }
}
