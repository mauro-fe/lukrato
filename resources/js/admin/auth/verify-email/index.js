/**
 * ============================================================================
 * LUKRATO - Verify Email Page (Vite Module)
 * ============================================================================
 */

import { getBaseUrl } from '../shared.js';
import { apiFetch, getErrorMessage } from '../../shared/api.js';

const BASE = getBaseUrl();

// =====================
// Resend Verification Form
// =====================
{
    const resendForm = document.getElementById('resendForm');
    const resendMessage = document.getElementById('resendMessage');

    function showResendMsg(text, type = 'error') {
        if (!resendMessage) return;
        resendMessage.textContent = text;
        resendMessage.className = 'resend-message ' + (type === 'success' ? 'success' : 'error');
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
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const data = await apiFetch(resendForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                showResendMsg(
                    data?.message || 'E-mail reenviado com sucesso!',
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
}
