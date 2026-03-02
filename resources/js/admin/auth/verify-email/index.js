/**
 * ============================================================================
 * LUKRATO — Verify Email Page (Vite Module)
 * ============================================================================
 */

import { getBaseUrl } from '../shared.js';

const BASE = getBaseUrl();

// =====================
// Resend Verification Form
// =====================
{
    const resendForm = document.getElementById('resendForm');

    if (resendForm) {
        resendForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = resendForm.querySelector('button[type="submit"]');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span>Enviando...</span>';

            try {
                const formData = new FormData(resendForm);
                const response = await fetch(resendForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                const msg = resendForm.querySelector('.resend-message') ||
                    document.querySelector('.resend-message');

                if (msg) {
                    msg.textContent = data.message || (response.ok
                        ? 'E-mail reenviado com sucesso!'
                        : 'Erro ao reenviar e-mail.');
                    msg.className = 'resend-message ' + (response.ok ? 'success' : 'error');
                }
            } catch (err) {
                const msg = resendForm.querySelector('.resend-message') ||
                    document.querySelector('.resend-message');
                if (msg) {
                    msg.textContent = 'Erro de conexão. Tente novamente.';
                    msg.className = 'resend-message error';
                }
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }
}
