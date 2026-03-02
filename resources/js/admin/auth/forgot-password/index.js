/**
 * ============================================================================
 * LUKRATO — Forgot Password Page (Vite Module)
 * ============================================================================
 */

import { createParticles, createConfetti, getBaseUrl } from '../shared.js';

const BASE = getBaseUrl();

// ── Init particles ─────────────────────────────────────────────────────────
createParticles();

// =====================
// Form Handler
// =====================
{
    const form = document.getElementById('forgotForm');
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    const btn = form?.querySelector('.btn-primary');
    const successMsg = document.querySelector('.success-message');
    const errorMsg = document.querySelector('.error-message');
    const formContainer = document.querySelector('.forgot-form');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (emailError) emailError.textContent = '';
            if (successMsg) { successMsg.textContent = ''; successMsg.classList.remove('show'); }
            if (errorMsg) { errorMsg.textContent = ''; errorMsg.classList.remove('show'); }

            const email = emailInput.value.trim();

            if (!email) {
                if (emailError) emailError.textContent = 'Digite seu e-mail';
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (emailError) emailError.textContent = 'E-mail inválido';
                return;
            }

            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span>Enviando...</span>';

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
                        successMsg.textContent = data.message || 'Link de recuperação enviado para seu e-mail!';
                        successMsg.classList.add('show');
                    }
                    createConfetti(150);
                } else {
                    if (errorMsg) {
                        errorMsg.textContent = data.message || 'Erro ao enviar link de recuperação.';
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

// =====================
// Konami Code Easter Egg
// =====================
{
    const konamiCode = [
        'ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown',
        'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight',
        'b', 'a'
    ];
    let konamiIndex = 0;

    document.addEventListener('keydown', (e) => {
        if (e.key === konamiCode[konamiIndex]) {
            konamiIndex++;
            if (konamiIndex === konamiCode.length) {
                konamiIndex = 0;
                createConfetti(500);
            }
        } else {
            konamiIndex = 0;
        }
    });
}
