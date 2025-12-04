<style>
  /* ==========================================================================
       MODAL DE PAGAMENTO
       ========================================================================== */
  .payment-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: var(--spacing-4);
    animation: fadeIn 0.3s ease;
  }

  .payment-modal--open {
    display: flex;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  .payment-modal__content {
    background: var(--color-surface);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(30px) scale(0.95);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  .payment-modal__header {
    padding: clamp(32px, 5vw, 48px) clamp(24px, 4vw, 48px) clamp(24px, 4vw, 32px);
    border-bottom: 1px solid var(--glass-border);
    text-align: center;
    position: relative;
  }

  .payment-modal__close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: var(--color-surface-muted);
    color: var(--color-text-muted);
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.25rem;
  }

  .payment-modal__close:hover {
    background: var(--color-danger);
    color: white;
    transform: rotate(90deg);
  }

  .payment-modal__title {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    margin: 0 0 var(--spacing-2);
    color: var(--color-text);
  }

  .payment-modal__subtitle {
    font-size: 1rem;
    color: var(--color-text-muted);
    margin: 0;
  }

  .payment-modal__price {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-2);
    background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
    color: white;
    font-size: 1.125rem;
    font-weight: 700;
    padding: var(--spacing-3) var(--spacing-5);
    border-radius: var(--radius-lg);
    margin-top: var(--spacing-4);
    box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 30%, transparent);
  }

  .payment-modal__body {
    padding: clamp(24px, 4vw, 40px) clamp(24px, 4vw, 48px);
  }

  /* Customização do container do Brick do Mercado Pago */
  #cardPaymentBrick_container {
    margin-top: var(--spacing-4);
  }

  @media (max-width: 768px) {
    .payment-modal {
      padding: var(--spacing-2);
    }

    .payment-modal__content {
      max-height: 95vh;
    }

    .payment-modal__header {
      padding: 24px 20px 20px;
    }

    .payment-modal__body {
      padding: 20px;
    }
  }
</style>

<div id="billing-modal" class="payment-modal" role="dialog" aria-labelledby="billing-modal-title" aria-modal="true">
  <div class="payment-modal__content">
    <div class="payment-modal__header">
      <button class="payment-modal__close"
        aria-label="Fechar modal"
        onclick="window.closeBillingModal?.()">
        <i class="fa-solid fa-times" aria-hidden="true"></i>
      </button>

      <h2 id="billing-modal-title" class="payment-modal__title">
        Pagamento Seguro
      </h2>

      <p id="billing-modal-text" class="payment-modal__subtitle">
        Complete os dados do cartão para ativar o Lukrato PRO
      </p>

      <div id="billing-modal-price"
        class="payment-modal__price"
        role="status"
        aria-live="polite">
        Selecione um plano para continuar
      </div>
    </div>

    <div class="payment-modal__body">
      <div id="cardPaymentBrick_container"></div>
    </div>
  </div>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
  (function() {
    'use strict';

    const BASE_URL = '<?= BASE_URL ?>';
    const PUBLIC_KEY = '<?= \Application\Services\MercadoPagoService::resolvePublicKey(); ?>';
    const PAYER_EMAIL = <?= json_encode(
                          (strtolower($_ENV['MP_ENV'] ?? 'production') === 'sandbox')
                            ? ($_ENV['MP_TEST_BUYER_EMAIL'] ?? $user->email)
                            : $user->email
                        ) ?>;

    console.log('[MP] PUBLIC_KEY:', PUBLIC_KEY);

    <?php if (strtolower($_ENV['MP_ENV'] ?? 'production') === 'sandbox'): ?>
      console.info('[MP] Sandbox ativo: usando credenciais de teste.');
    <?php else: ?>
      console.info('[MP] Produção ativa: usando credenciais reais.');
    <?php endif; ?>

    // Elementos do DOM
    const modal = document.getElementById('billing-modal');
    const modalTitle = document.getElementById('billing-modal-title');
    const modalText = document.getElementById('billing-modal-text');
    const modalPrice = document.getElementById('billing-modal-price');
    const planButtons = document.querySelectorAll('[data-plan-button]');

    const currencyFormatter = new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });

    let currentBrick = null;

    // ==========================================================================
    // FUNÇÕES DO MODAL
    // ==========================================================================

    function openBillingModal(planConfig) {
      if (!modal) return;
      modal.classList.add('payment-modal--open');
      document.body.style.overflow = 'hidden';
      updateModalInfo(planConfig);
    }

    function closeBillingModal() {
      if (!modal) return;
      modal.classList.remove('payment-modal--open');
      document.body.style.overflow = '';
      if (currentBrick?.unmount) {
        currentBrick.unmount();
      }
      currentBrick = null;
    }

    // Expor globalmente para o botão de fechar
    window.closeBillingModal = closeBillingModal;

    function updateModalInfo(planConfig) {
      if (!planConfig) return;

      const planName = planConfig.planName || 'Plano';
      const intervalText = planConfig.intervalLabel || 'mês';
      const amountText = currencyFormatter.format(planConfig.amount || 0);

      if (modalTitle) {
        modalTitle.textContent = 'Pagamento Seguro';
      }

      if (modalText) {
        modalText.textContent = `Ativando o plano ${planName}. Complete os dados do cartão para continuar.`;
      }

      if (modalPrice) {
        modalPrice.textContent = `${planName} - ${amountText}/${intervalText}`;
      }
    }

    // Fechar ao clicar fora do modal
    modal?.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeBillingModal();
      }
    });

    // Fechar com ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal?.classList.contains('payment-modal--open')) {
        closeBillingModal();
      }
    });

    // ==========================================================================
    // EVENT LISTENERS DOS BOTÕES
    // ==========================================================================

    planButtons.forEach((btn) => {
      btn.addEventListener('click', async () => {
        const amount = Number(btn.dataset.planAmount ?? '0');

        if (!amount || Number.isNaN(amount)) {
          window.Swal?.fire('Aviso', 'Plano inválido para pagamento.', 'warning');
          return;
        }

        if (amount < 1) {
          window.Swal?.fire(
            'Valor inválido',
            'O Mercado Pago exige pagamentos a partir de R$ 1,00.',
            'info'
          );
          return;
        }

        const planConfig = {
          amount,
          title: `Assinatura ${btn.dataset.planName || 'Lukrato'}`,
          planName: btn.dataset.planName || 'Lukrato',
          planId: btn.dataset.planId || null,
          planCode: btn.dataset.planCode || null,
          intervalLabel: btn.dataset.planInterval || 'mês',
        };

        openBillingModal(planConfig);
        await initCardPaymentBrick(planConfig);
      });
    });

    // ==========================================================================
    // INICIALIZAÇÃO DO BRICK DO MERCADO PAGO
    // ==========================================================================

    async function initCardPaymentBrick(config) {
      if (currentBrick?.unmount) {
        currentBrick.unmount();
      }

      const mp = new MercadoPago(PUBLIC_KEY, {
        locale: 'pt-BR'
      });
      const bricksBuilder = mp.bricks();

      const settings = {
        initialization: {
          amount: config.amount,
          payer: {
            email: PAYER_EMAIL
          }
        },
        customization: {
          visual: {
            style: {
              theme: 'default'
            }
          },
          paymentMethods: {
            creditCard: 'all',
            debitCard: 'all',
            bankTransfer: 'all'
          }
        },
        callbacks: {
          onReady: () => {
            console.log('[MP] Brick carregado com sucesso');
          },
          onError: (error) => {
            console.error('[MP] Erro ao carregar:', error);
            window.Swal?.fire('Erro', 'Falha ao carregar o pagamento.', 'error');
          },
          onSubmit: async (cardFormData) => {
            try {
              const response = await fetch(`${BASE_URL}api/mercadopago/pay`, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-Token': window.CSRF || ''
                },
                credentials: 'include',
                body: JSON.stringify({
                  amount: config.amount,
                  title: config.title,
                  data: cardFormData,
                  plan_id: config.planId,
                  plan_code: config.planCode
                })
              });

              const json = await response.json().catch(() => ({}));

              if (!response.ok || (json?.status && json.status !== 'success')) {
                throw new Error(json?.message || 'Pagamento não autorizado');
              }

              // Mostrar feedback de processamento
              await (window.Swal?.fire({
                title: 'Processando...',
                text: 'Confirmando pagamento com o banco',
                icon: 'info',
                timer: 1500,
                showConfirmButton: false
              }) ?? Promise.resolve());

              closeBillingModal();

              window.Swal?.fire(
                'Sucesso!',
                'Pagamento realizado com sucesso! 🎉',
                'success'
              ).then(() => {
                // Recarregar página para atualizar status do plano
                window.location.reload();
              });

            } catch (error) {
              console.error('[MP] Erro no pagamento:', error);
              window.Swal?.fire(
                'Erro',
                error.message || 'Pagamento recusado',
                'error'
              );
            }
          }
        }
      };

      try {
        currentBrick = await bricksBuilder.create(
          'cardPayment',
          'cardPaymentBrick_container',
          settings
        );
        window.__lkBrickDebug = currentBrick;
      } catch (error) {
        console.error('[MP] Erro ao criar brick:', error);
        window.Swal?.fire('Erro', 'Não foi possível inicializar o pagamento.', 'error');
      }
    }
  })();
</script>

<!-- ============================================================================
     FEEDBACK DE STATUS
     ============================================================================ -->
<?php if (isset($_GET['status'])): ?>
  <script>
    (function() {
      'use strict';

      const status = '<?= htmlspecialchars($_GET['status'], ENT_QUOTES, 'UTF-8') ?>';

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
  </script>
<?php endif; ?>