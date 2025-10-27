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
        from {
            opacity: 0;
            transform: scale(.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<!-- Modal Billing -->
<div id="billing-modal" class="lk-modal" style="display:none">
    <div class="lk-modal-content">
        <button class="lk-modal-close" onclick="closeBillingModal()">&times;</button>

        <h2>Escolha seu plano</h2>
        <div class="plans">
            <div class="plan free">
                <h3>Gratuito</h3>
                <p>Controle básico de transações</p>
                <button disabled>Plano Atual</button>
            </div>

            <div class="plan pro">
                <h3>Pro</h3>
                <p>Controle total e relatórios avançados</p>
                <div class="price">R$ 12/mês</div>
                <button id="btnAssinarProModal">Assinar Pro</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openBillingModal() {
        document.getElementById('billing-modal').style.display = 'flex';
    }

    function closeBillingModal() {
        document.getElementById('billing-modal').style.display = 'none';
    }

    document.getElementById('btnAssinarProModal')?.addEventListener('click', async () => {
        try {
            const resp = await fetch(`${BASE_URL}api/mercadopago/checkout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF || ''
                },
                credentials: 'include'
            });

            const json = await resp.json();
            const initPoint = json?.body?.init_point || json?.init_point;

            if (!initPoint) return Swal.fire('Erro', 'Link de pagamento não encontrado.', 'error');
            window.location.href = initPoint;

        } catch (e) {
            Swal.fire('Erro', e.message || 'Falha ao iniciar pagamento.', 'error');
        }
    });
</script>