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
    <button class="lk-modal-close" onclick="closeBillingModal()">&times;</button>

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
  const PUBLIC_KEY = '<?= $_ENV['MP_PUBLIC_KEY'] ?>';
  const btnAssinar = document.querySelector('#btnAssinar');

  // abre o modal e monta o brick
  btnAssinar?.addEventListener('click', async () => {
    openBillingModal();
    await initCardPaymentBrick(12.00, 'Assinatura Pro Lukrato');
  });

  function openBillingModal() {
    document.getElementById('billing-modal').style.display = 'flex';
  }
  function closeBillingModal() {
    document.getElementById('billing-modal').style.display = 'none';
  }

  let currentBrick = null;

  async function initCardPaymentBrick(amount, title) {
    // desmonta se já existir
    if (currentBrick?.unmount) currentBrick.unmount();

    const mp = new MercadoPago(PUBLIC_KEY, { locale: 'pt-BR' });
    const bricksBuilder = mp.bricks();

    const settings = {
      initialization: {
        amount: amount,
        payer: { email: <?= json_encode($user->email) ?> }
      },
      customization: {
        visual: { style: { theme: 'default' } },
        paymentMethods: { creditCard: 'all', debitCard: 'all', bankTransfer: 'all' }
      },
      callbacks: {
        onReady: () => {},
        onError: (error) => {
          console.error(error);
          Swal?.fire('Erro', 'Falha ao carregar o pagamento.', 'error');
        },
        onSubmit: async (cardFormData) => {
          try {
            // cria o pagamento no backend
            const resp = await fetch(`${base}api/mercadopago/pay`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': (window.CSRF || '')
              },
              credentials: 'include',
              body: JSON.stringify({
                amount: amount,
                title: title,
                data: cardFormData
              })
            });

            const json = await resp.json().catch(() => ({}));
            if (!resp.ok || (json?.status && json?.status !== 'success')) {
              throw new Error(json?.message || 'Pagamento não autorizado');
            }

            // exibe feedback visual
            await Swal.fire({
              title: 'Processando...',
              text: 'Confirmando pagamento com o banco',
              icon: 'info',
              timer: 1500,
              showConfirmButton: false
            });

            // fecha o modal e atualiza a UI
            closeBillingModal();
            Swal.fire('Sucesso!', 'Pagamento realizado com sucesso! ✅', 'success');
            // aqui você pode atualizar o estado do plano na tela sem reload
            // ex: document.location.reload();

          } catch (e) {
            console.error(e);
            Swal.fire('Erro', e.message || 'Pagamento recusado', 'error');
          }
        }
      }
    };

    currentBrick = await bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', settings);
  }
})();
</script>
