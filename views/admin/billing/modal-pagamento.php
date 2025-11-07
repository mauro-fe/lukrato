<style>
  .lk-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    backdrop-filter: blur(4px);
  }
  .lk-modal-content {
    background: var(--color-surface);
    padding: 40px 50px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    max-width: 700px;
    color: var(--color-text);
    position: relative;
    text-align: center;
    animation: fadeIn .3s ease;
  }
  .lk-modal-close {
    position: absolute;
    top: 16px;
    right: 20px;
    border: none;
    background: transparent;
    font-size: 1.5rem;
    color: var(--color-text-muted);
    cursor: pointer;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: scale(.95); }
    to   { opacity: 1; transform: scale(1); }
  }
</style>

<!-- Modal Billing -->
<div id="billing-modal" class="lk-modal" style="display:none">
  <div class="lk-modal-content">

    <h2>Pagamento Seguro</h2>
    <p>Complete os dados do cartão para ativar o Lukrato PRO</p>

    <div class="price">R$ 12/mês</div>

    <!-- Container do Brick -->
    <div id="cardPaymentBrick_container" style="margin-top:16px;"></div>
  </div>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
(function () {
  const base = '<?= BASE_URL ?>';

  // 1) PUBLIC KEY correta (usa a service pra evitar mismatch)
  const PUBLIC_KEY = '<?= \Application\Services\MercadoPagoService::resolvePublicKey(); ?>';
  console.log('[MP] PUBLIC_KEY =', PUBLIC_KEY);
  // alerta útil em dev
  <?php if (strtolower($_ENV['MP_ENV'] ?? 'production') === 'sandbox'): ?>
  if (!PUBLIC_KEY.startsWith('TEST-')) {
    console.warn('[MP] Atenção: PUBLIC_KEY não é TEST- (sandbox). Verifique o .env.');
  }
  <?php endif; ?>

  const btnAssinar = document.querySelector('#btnAssinar');

  // 3) Singleton de montagem (evita duplicar)
  let currentBrick = null;
  let brickMounted = false;

  function openBillingModal() {
    document.getElementById('billing-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
  function closeBillingModal() {
    document.getElementById('billing-modal').style.display = 'none';
    document.body.style.overflow = '';
    // 4) desmonta ao fechar
    if (currentBrick?.unmount) currentBrick.unmount();
    currentBrick = null;
    brickMounted = false;
  }
  // fecha ao clicar fora
  document.getElementById('billing-modal').addEventListener('click', (e) => {
    if (e.target.id === 'billing-modal') closeBillingModal();
  });

  btnAssinar?.addEventListener('click', async () => {
    openBillingModal();
    if (!brickMounted) {
      await initCardPaymentBrick(12.00, 'Assinatura Pro Lukrato');
      brickMounted = true;
    }
  });

  async function initCardPaymentBrick(amount, title) {
    // desmonta se já existir por segurança
    if (currentBrick?.unmount) currentBrick.unmount();

    const mp = new MercadoPago(PUBLIC_KEY, { locale: 'pt-BR' });
    const bricksBuilder = mp.bricks();

    // 2) e-mail de teste no sandbox (se configurado)
    const payerEmail = <?= json_encode(
      (strtolower($_ENV['MP_ENV'] ?? 'production') === 'sandbox')
        ? ($_ENV['MP_TEST_BUYER_EMAIL'] ?? $user->email)
        : $user->email
    ) ?>;

    const settings = {
      initialization: {
        amount: amount,
        payer: { email: payerEmail }
      },
      customization: {
        visual: { style: { theme: 'default' } },
        paymentMethods: { creditCard: 'all', debitCard: 'all', bankTransfer: 'all' }
      },
      callbacks: {
        onReady: () => {},
        onError: (error) => {
          console.error(error);
          window.Swal?.fire('Erro', 'Falha ao carregar o pagamento.', 'error');
        },
        onSubmit: async (cardFormData) => {
          try {
            const resp = await fetch(`${base}api/mercadopago/pay`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.CSRF || '') },
              credentials: 'include',
              body: JSON.stringify({ amount, title, data: cardFormData })
            });

            const json = await resp.json().catch(() => ({}));
            if (!resp.ok || (json?.status && json?.status !== 'success')) {
              throw new Error(json?.message || 'Pagamento não autorizado');
            }

            await (window.Swal?.fire({
              title: 'Processando...',
              text: 'Confirmando pagamento com o banco',
              icon: 'info',
              timer: 1500,
              showConfirmButton: false
            }) ?? Promise.resolve());

            closeBillingModal();
            window.Swal?.fire('Sucesso!', 'Pagamento realizado com sucesso! ✅', 'success');

            // TODO: aqui você pode marcar o plano como ativo sem recarregar
            // ou dar location.reload() para simplificar.
          } catch (e) {
            console.error(e);
            window.Swal?.fire('Erro', e.message || 'Pagamento recusado', 'error');
          }
        }
      }
    };

    currentBrick = await bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', settings);
    window.__lkBrickDebug = currentBrick; // útil para debugar no console
  }
})();
</script>
