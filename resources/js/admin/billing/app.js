import { apiPost, buildAppUrl, getErrorMessage } from '../shared/api.js';
import { resolvePremiumCancelEndpoint } from '../api/endpoints/billing.js';
import { initCustomize } from './customize.js';

function calcTotal(baseMonthly, months, discountPct) {
    const base = Number(baseMonthly) || 0;
    const m = Number(months) || 1;
    const d = Number(discountPct) || 0;
    return Math.round((base * m * (1 - (d / 100)) + Number.EPSILON) * 100) / 100;
}

function cycleLabel(months) {
    if (Number(months) === 12) return 'ano';
    if (Number(months) === 6) return 'semestre';
    return 'mes';
}

function getActiveCycle(group) {
    const active = group?.querySelector('.plan-billing-toggle__btn.is-active');
    if (!active) {
        return { cycle: 'monthly', months: 1, discount: 0 };
    }

    return {
        cycle: active.dataset.cycle || 'monthly',
        months: Number(active.dataset.months || '1'),
        discount: Number(active.dataset.discount || '0'),
    };
}

function buildCheckoutUrl(planCode, cycle) {
    return buildAppUrl('billing/checkout', {
        plan: planCode,
        cycle: cycle.cycle,
        months: cycle.months,
        discount: cycle.discount,
    });
}

function updatePlanPrice(group, button) {
    const card = group.closest('.plan-card');
    const priceElement = card ? card.querySelector('.plan-card__price') : null;
    if (!priceElement) {
        return;
    }

    const basePrice = Number(priceElement.dataset.basePrice || 0);
    const months = Number(button.dataset.months || 1);
    const discount = Number(button.dataset.discount || 0);
    const total = calcTotal(basePrice, months, discount);
    const period = cycleLabel(months);

    const priceValueElement = priceElement.querySelector('.plan-card__price-value');
    const pricePeriodElement = priceElement.querySelector('.plan-card__price-period');

    if (priceValueElement) {
        priceValueElement.style.animation = 'none';
        void priceValueElement.offsetWidth;
        priceValueElement.textContent = `R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        priceValueElement.style.animation = 'priceAppear 0.5s ease';
    }

    if (pricePeriodElement) {
        pricePeriodElement.textContent = `/${period}`;
    }
}

function setupPlanSelection() {
    document.querySelectorAll('.plan-billing-toggle').forEach((group) => {
        const buttons = group.querySelectorAll('.plan-billing-toggle__btn');

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                buttons.forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                updatePlanPrice(group, button);
            });
        });
    });

    document.querySelectorAll('[data-plan-button]').forEach((button) => {
        button.addEventListener('click', (event) => {
            const planCode = button.dataset.planCode || null;
            const monthlyBase = Number(button.dataset.planMonthly ?? button.dataset.planAmount ?? '0');

            if (!planCode || !monthlyBase || Number.isNaN(monthlyBase)) {
                event.preventDefault();
                window.Swal?.fire('Plano invalido', 'Nao foi possivel identificar o plano. Recarregue a pagina.', 'warning');
                return;
            }

            event.preventDefault();
            const group = button.closest('.plan-card')?.querySelector('.plan-billing-toggle');
            window.location.href = buildCheckoutUrl(planCode, getActiveCycle(group));
        });
    });
}

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
            text: 'Aguardando confirmação do pagamento...',
            icon: 'info',
        },
        error: {
            title: 'Ops! Algo deu errado',
            text: 'Pagamento não aprovado. Tente novamente.',
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
                        <li style="margin-bottom: 0.5rem;">Você perderá acesso aos recursos Pro</li>
                        <li style="margin-bottom: 0.5rem;">Agendamentos serão desativados</li>
                        <li style="margin-bottom: 0.5rem;">Relatórios avançados serão bloqueados</li>
                        <li>Seus dados serão mantidos</li>
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
                    return 'Você precisa digitar "CANCELAR" para confirmar';
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
            const data = await apiPost(resolvePremiumCancelEndpoint());

            if (data?.success === false) {
                throw new Error(getErrorMessage({ data }, 'Erro ao cancelar assinatura.'));
            }

            await Swal.fire({
                icon: 'success',
                title: 'Assinatura cancelada!',
                html: '<p>Sua assinatura Pro foi cancelada com sucesso.</p><p style="color: var(--color-text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Você ainda terá acesso aos recursos Pro até o fim do período pago.</p>',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#e67e22',
            });

            window.location.reload();
        } catch (error) {
            console.error('Erro ao cancelar assinatura:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: getErrorMessage(error, 'Não foi possível cancelar a assinatura. Tente novamente.'),
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
                    <p style="margin-bottom: 1rem;">Ao ${actionText} sua assinatura você terá acesso imediato a:</p>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text-muted);">
                        <li style="margin-bottom: 0.5rem;">Lançamentos ilimitados</li>
                        <li style="margin-bottom: 0.5rem;">Importação automática de extratos</li>
                        <li style="margin-bottom: 0.5rem;">Relatórios avançados</li>
                        <li>Categorização inteligente com IA</li>
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

        window.location.href = buildCheckoutUrl(planCode, { cycle: 'monthly', months: 1, discount: 0 });
    });
}

function setupRenewSubscription() {
    setupRenewButton(document.getElementById('btn-renew-subscription'), 'renew');
    setupRenewButton(document.getElementById('btn-reactivate-subscription'), 'reactivate');
}

export function bootBillingPage() {
    initCustomize();
    showStatusFeedback();
    setupPlanSelection();
    setupCancelSubscription();
    setupRenewSubscription();
}
