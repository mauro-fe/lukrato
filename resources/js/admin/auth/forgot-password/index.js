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
    const form = document.getElementById('recoverForm');
    const emailInput = document.getElementById('email');
    const messageContainer = document.getElementById('messageContainer');
    const btn = form?.querySelector('.btn-primary');

    function showMessage(text, type = 'error') {
        if (!messageContainer) return;
        messageContainer.innerHTML = `<div class="alert alert-${type === 'success' ? 'success' : 'danger'}" style="padding:12px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;background:${type === 'success' ? 'rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.2)'}">${text}</div>`;
    }

    function clearMessage() {
        if (messageContainer) messageContainer.innerHTML = '';
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearMessage();

            const email = emailInput.value.trim();

            if (!email) {
                showMessage('Digite seu e-mail');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMessage('E-mail inválido');
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
                    form.style.display = 'none';
                    showMessage(data.message || 'Link de recuperação enviado para seu e-mail!', 'success');
                    createConfetti(150);
                } else {
                    showMessage(data.message || 'Erro ao enviar link de recuperação.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            } catch (err) {
                showMessage('Erro de conexão. Tente novamente.');
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
