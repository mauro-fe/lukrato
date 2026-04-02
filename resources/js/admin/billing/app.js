import { apiPost, getBaseUrl, getCSRFToken, getErrorMessage } from '../shared/api.js';
import { initCustomize } from './customize.js';

const BASE_URL = getBaseUrl();

function showStatusFeedback() {
    const status = new URLSearchParams(window.location.search).get('status');
    if (!status || typeof Swal === 'undefined') {
        return;
    }

    const messages = {
        success: {
            title: 'Tudo certo!',
            text: 'Pagamento aprovado com sucesso. Bem-vindo ao Pro!',
            icon: 'success',
        },
        pending: {
            title: 'Pagamento pendente',
            text: 'Aguardando confirmacao do pagamento...',
            icon: 'info',
        },
        error: {
            title: 'Ops! Algo deu errado',
            text: 'Pagamento nao aprovado. Tente novamente.',
            icon: 'error',
        },
        cancelled: {
            title: 'Assinatura cancelada',
            text: 'Sua assinatura Pro foi cancelada com sucesso.',
            icon: 'success',
        },
    };

    const config = messages[status] || messages.error;

    Swal.fire({
        title: config.title,
        text: config.text,
        icon: config.icon,
        confirmButtonText: 'Entendi',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#e67e22',
    });

    if (window.history?.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

function setupCancelSubscription() {
    const cancelBtn = document.getElementById('btn-cancel-subscription');
    if (!cancelBtn) {
        return;
    }

    cancelBtn.addEventListener('click', async () => {
        if (typeof Swal === 'undefined') {
            return;
        }

        const result = await Swal.fire({
            title: 'Cancelar assinatura Pro?',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 1rem;">Ao cancelar sua assinatura:</p>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text-muted);">
                        <li style="margin-bottom: 0.5rem;">Voce perdera acesso aos recursos Pro</li>
                        <li style="margin-bottom: 0.5rem;">Agendamentos serao desativados</li>
                        <li style="margin-bottom: 0.5rem;">Relatorios avancados serao bloqueados</li>
                        <li>Seus dados serao mantidos</li>
                    </ul>
                </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Sim, cancelar assinatura',
            cancelButtonText: 'Manter plano Pro',
            focusCancel: true,
        });

        if (!result.isConfirmed) {
            return;
        }

        const finalConfirm = await Swal.fire({
            title: 'Ultima confirmacao',
            text: 'Digite "CANCELAR" para confirmar o cancelamento',
            input: 'text',
            inputPlaceholder: 'Digite: CANCELAR',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Confirmar cancelamento',
            cancelButtonText: 'Voltar',
            inputValidator: (value) => {
                if (value !== 'CANCELAR') {
                    return 'Voce precisa digitar "CANCELAR" para confirmar';
                }
                return null;
            },
        });

        if (!finalConfirm.isConfirmed) {
            return;
        }

        Swal.fire({
            title: 'Cancelando assinatura...',
            text: 'Por favor aguarde',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const csrfToken = getCSRFToken();
            const data = await apiPost(`${BASE_URL}premium/cancel`, { csrf_token: csrfToken });

            if (data?.success === false) {
                throw new Error(getErrorMessage({ data }, 'Erro ao cancelar assinatura.'));
            }

            await Swal.fire({
                icon: 'success',
                title: 'Assinatura cancelada!',
                html: '<p>Sua assinatura Pro foi cancelada com sucesso.</p><p style="color: var(--color-text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Voce ainda tera acesso aos recursos Pro ate o fim do periodo pago.</p>',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#e67e22',
            });

            window.location.reload();
        } catch (error) {
            console.error('Erro ao cancelar assinatura:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Nao foi possivel cancelar a assinatura. Tente novamente.'),
            });
        }
    });
}

function setupRenewButton(button, action) {
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        if (typeof Swal === 'undefined') {
            return;
        }

        const planId = button.dataset.planId;
        const planCode = button.dataset.planCode;
        const actionText = action === 'reactivate' ? 'reativar' : 'renovar';
        const titleText = action === 'reactivate' ? 'Reativar' : 'Renovar';

        const result = await Swal.fire({
            title: `${titleText} assinatura Pro?`,
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 1rem;">Ao ${actionText} sua assinatura voce tera acesso imediato a:</p>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text-muted);">
                        <li style="margin-bottom: 0.5rem;">Lancamentos ilimitados</li>
                        <li style="margin-bottom: 0.5rem;">Importacao automatica de extratos</li>
                        <li style="margin-bottom: 0.5rem;">Relatorios avancados</li>
                        <li>Categorizacao inteligente com IA</li>
                    </ul>
                </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#27ae60',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: `${titleText} agora`,
            cancelButtonText: 'Cancelar',
        });

        if (!result.isConfirmed) {
            return;
        }

        const buttonId = `btnAssinar${planCode.charAt(0).toUpperCase()}${planCode.slice(1).toLowerCase()}`;
        const signButton = document.getElementById(buttonId);
        if (signButton) {
            signButton.click();
            return;
        }

        if (typeof window.openBillingModal === 'function') {
            const priceEl = document.querySelector(`[data-plan-code="${planCode}"] [data-base-price]`)
                || document.getElementById('planProPrice');
            const monthlyBase = priceEl ? Number(priceEl.dataset.basePrice || 0) : 14.90;

            window.openBillingModal({
                planId,
                planCode,
                planName: `Lukrato ${planCode.toUpperCase()}`,
                monthlyBase,
                cycle: 'monthly',
                months: 1,
                discount: 0,
            });
            return;
        }

        window.location.href = `${BASE_URL}billing?action=renew&plan=${planCode}`;
    });
}

function setupRenewSubscription() {
    setupRenewButton(document.getElementById('btn-renew-subscription'), 'renew');
    setupRenewButton(document.getElementById('btn-reactivate-subscription'), 'reactivate');
}

export function bootBillingPage() {
    initCustomize();
    showStatusFeedback();
    setupCancelSubscription();
    setupRenewSubscription();
}
