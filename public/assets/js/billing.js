/**
 * Billing Page Scripts - Lukrato PRO
 * Feedback de status, cancelamento e renovação de assinatura
 */

// ============================================================================
// FEEDBACK DE STATUS (chamado se houver ?status= na URL)
// ============================================================================
(function () {
    'use strict';

    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    if (!status) return;

    if (typeof Swal === 'undefined') {
        console.warn('[Billing] SweetAlert2 não disponível');
        return;
    }

    const messages = {
        success: {
            title: 'Tudo certo! 🎉',
            text: 'Pagamento aprovado com sucesso. Bem-vindo ao Pro!',
            icon: 'success'
        },
        pending: {
            title: 'Pagamento pendente ⏳',
            text: 'Aguardando confirmação do pagamento...',
            icon: 'info'
        },
        error: {
            title: 'Ops! Algo deu errado 😕',
            text: 'Pagamento não aprovado. Tente novamente.',
            icon: 'error'
        },
        cancelled: {
            title: 'Assinatura cancelada',
            text: 'Sua assinatura Pro foi cancelada com sucesso.',
            icon: 'success'
        }
    };

    const config = messages[status] || messages.error;

    Swal.fire({
        title: config.title,
        text: config.text,
        icon: config.icon,
        confirmButtonText: 'Entendi',
        confirmButtonColor: getComputedStyle(document.documentElement)
            .getPropertyValue('--color-primary').trim() || '#e67e22'
    });

    // Limpa URL
    if (window.history?.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
})();

// ============================================================================
// SCRIPT DE CANCELAMENTO DE ASSINATURA
// ============================================================================
(function () {
    'use strict';

    const cancelBtn = document.getElementById('btn-cancel-subscription');
    if (!cancelBtn) return;

    cancelBtn.addEventListener('click', async () => {
        if (typeof Swal === 'undefined') {
            alert('Erro: SweetAlert não carregado');
            return;
        }

        // Primeira confirmação
        const result = await Swal.fire({
            title: '⚠️ Cancelar assinatura Pro?',
            html: `
                <div style="text-align: left; padding: 1rem 0;">
                    <p style="margin-bottom: 1rem;">Ao cancelar sua assinatura:</p>
                    <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text-muted);">
                        <li style="margin-bottom: 0.5rem;">Você perderá acesso aos recursos Pro</li>
                        <li style="margin-bottom: 0.5rem;">Agendamentos serão desativados</li>
                        <li style="margin-bottom: 0.5rem;">Relatórios avançados serão bloqueados</li>
                        <li>Seus dados serão mantidos</li>
                    </ul>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Sim, cancelar assinatura',
            cancelButtonText: 'Manter plano Pro',
            focusCancel: true
        });

        if (!result.isConfirmed) return;

        // Segunda confirmação
        const finalConfirm = await Swal.fire({
            title: 'Última confirmação',
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
            }
        });

        if (!finalConfirm.isConfirmed) return;

        // Mostrar loading
        Swal.fire({
            title: 'Cancelando assinatura...',
            text: 'Por favor aguarde',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '/';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const response = await fetch(`${baseUrl}premium/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    csrf_token: csrfToken
                })
            });

            const data = await response.json();

            if (!response.ok || data.status !== 'success') {
                throw new Error(data.message || 'Erro ao cancelar assinatura');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Assinatura cancelada!',
                html: `
                    <p>Sua assinatura Pro foi cancelada com sucesso.</p>
                    <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-top: 0.5rem;">
                        Você ainda terá acesso aos recursos Pro até o fim do período pago.
                    </p>
                `,
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#e67e22'
            });

            // Recarregar a página
            window.location.reload();

        } catch (err) {
            console.error('Erro ao cancelar assinatura:', err);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: err.message || 'Não foi possível cancelar a assinatura. Tente novamente.'
            });
        }
    });
})();

// ============================================================================
// SCRIPT DE RENOVAÇÃO DE ASSINATURA (para período de carência/expirado)
// ============================================================================
(function () {
    'use strict';

    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '/';

    // Botão de renovar (aparece quando em carência ou expirado)
    const renewBtn = document.getElementById('btn-renew-subscription');
    const reactivateBtn = document.getElementById('btn-reactivate-subscription');

    // Handler comum para renovar/reativar - abre modal de pagamento
    const handleRenewClick = async (btn, action) => {
        if (!btn) return;

        btn.addEventListener('click', async () => {
            const planId = btn.dataset.planId;
            const planCode = btn.dataset.planCode;

            if (typeof Swal === 'undefined') {
                alert('Erro: SweetAlert não carregado');
                return;
            }

            const actionText = action === 'reactivate' ? 'reativar' : 'renovar';
            const titleText = action === 'reactivate' ? 'Reativar' : 'Renovar';

            // Confirmação
            const result = await Swal.fire({
                title: `🔄 ${titleText} assinatura Pro?`,
                html: `
                    <div style="text-align: left; padding: 1rem 0;">
                        <p style="margin-bottom: 1rem;">Ao ${actionText} sua assinatura você terá acesso imediato a:</p>
                        <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text-muted);">
                            <li style="margin-bottom: 0.5rem;">✅ Lançamentos ilimitados</li>
                            <li style="margin-bottom: 0.5rem;">✅ Importação automática de extratos</li>
                            <li style="margin-bottom: 0.5rem;">✅ Relatórios avançados</li>
                            <li>✅ Categorização inteligente com IA</li>
                        </ul>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: `${titleText} agora`,
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            // Buscar dados do plano PRO para abrir o modal de pagamento
            const assinarBtn = document.getElementById('btnAssinar');

            if (assinarBtn) {
                assinarBtn.click();
            } else if (typeof window.openBillingModal === 'function') {
                const proPriceEl = document.getElementById('planProPrice');
                const monthlyBase = proPriceEl ? Number(proPriceEl.dataset.basePrice || 0) : 14.90;

                window.openBillingModal({
                    planId: planId,
                    planCode: planCode,
                    planName: 'Lukrato PRO',
                    monthlyBase: monthlyBase,
                    cycle: 'monthly',
                    months: 1,
                    discount: 0
                });
            } else {
                window.location.href = `${baseUrl}billing?action=renew&plan=${planCode}`;
            }
        });
    };

    // Configura handlers
    handleRenewClick(renewBtn, 'renew');
    handleRenewClick(reactivateBtn, 'reactivate');
})();
