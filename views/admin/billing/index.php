<style>
    /* =========================================================
 * BILLING / PLANOS - LUKRATO
 * =======================================================*/

    .billing-container {
        max-width: 800px;
        margin: 60px auto;
        background: var(--color-surface);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        padding: 40px 50px;
        color: var(--color-text);
        text-align: center;
    }

    .billing-container h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: var(--color-primary);
    }

    .billing-plan {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin-top: 32px;
    }

    .plan-card {
        background: var(--color-surface-muted);
        border-radius: var(--radius-md);
        padding: 24px;
        border: 1px solid var(--color-border, rgba(255, 255, 255, .08));
        box-shadow: var(--shadow-sm);
        transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    }

    .plan-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .plan-card h2 {
        font-size: 1.4rem;
        font-weight: 600;
        color: var(--color-primary);
        margin-bottom: .5rem;
    }

    .plan-card p {
        color: var(--color-text-muted);
        font-size: .95rem;
        margin: 0;
    }

    .plan-price {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 1rem 0;
        color: var(--color-text);
    }

    .plan-features {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
        text-align: left;
    }

    .plan-features li {
        margin: .35rem 0;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .95rem;
    }

    .plan-features li i {
        color: var(--color-success);
    }

    .plan-btn {
        margin-top: 1rem;
        display: inline-block;
        background: var(--color-primary);
        color: #fff;
        font-weight: 600;
        border: none;
        border-radius: var(--radius-md);
        padding: 12px 24px;
        cursor: pointer;
        transition: background var(--transition-fast), transform var(--transition-fast);
    }

    .plan-btn:hover {
        background: var(--color-primary-hover, #d8741e);
        transform: translateY(-2px);
    }

    .plan-btn:disabled {
        background: var(--color-neutral);
        cursor: not-allowed;
    }

    /* Status do plano ativo */
    .plan-active {
        border: 2px solid var(--color-success);
        background: rgba(34, 197, 94, .05);
    }

    .plan-active h2 {
        color: var(--color-success);
    }

    .plan-active .plan-btn {
        background: var(--color-success);
    }

    .plan-active .plan-btn:hover {
        background: #22c55e;
    }

    /* Responsividade */
    @media (max-width: 600px) {
        .billing-container {
            padding: 24px;
        }

        .plan-card {
            padding: 20px;
        }
    }
</style>
<div class="billing-container" data-aos="fade-up">
    <h1>Seu plano</h1>

    <div class="billing-plan">
        <!-- Plano Gratuito -->
        <div class="plan-card <?= $user->isGratuito() ? 'plan-active' : '' ?>">
            <h2>Gratuito</h2>
            <p>Sem relatórios nem agendamentos.</p>
            <ul class="plan-features">
                <li><i class="fas fa-times"></i> Relatórios avançados</li>
                <li><i class="fas fa-times"></i> Agendamentos</li>
                <li><i class="fas fa-times"></i> Exportação</li>
            </ul>
            <?php if ($user->isGratuito()): ?>
                <button class="plan-btn" disabled>Plano Atual</button>
            <?php endif; ?>
        </div>

        <!-- Plano Pro -->
        <div class="plan-card <?= $user->isPro() ? 'plan-active' : '' ?>">
            <h2>Pro</h2>
            <div class="plan-price">R$ 12 / mês</div>
            <p>Relatórios completos, agendamentos e exportação ilimitada.</p>
            <ul class="plan-features">
                <li><i class="fas fa-check"></i> Relatórios completos</li>
                <li><i class="fas fa-check"></i> Agendamentos</li>
                <li><i class="fas fa-check"></i> Exportação de dados</li>
            </ul>

            <?php if ($user->isPro()): ?>
                <button class="plan-btn" disabled>
                    Ativo até <?= htmlspecialchars($user->plan_renews_at ?? '—') ?>
                </button>
            <?php else: ?>
                <div style="margin-top:.75rem; display:flex; gap:.5rem; justify-content:center; align-items:center;">
                    <input type="text" id="cardToken" placeholder="Insira o card_token (tok_xxx)" inputmode="latin"
                        autocomplete="off"
                        style="min-width:260px; padding:.6rem .75rem; border-radius:8px; border:1px solid rgba(255,255,255,.12); background:rgba(0,0,0,.15); color:var(--color-text);" />
                    <button id="btnAssinar" class="plan-btn">Assinar Pro</button>
                </div>
                <div id="msg" aria-live="polite"
                    style="margin-top:.5rem; min-height:1.25rem; color:var(--color-text-muted)"></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    (function() {
        const base = '<?= BASE_URL ?>';
        const $ = sel => document.querySelector(sel);
        const btn = $('#btnAssinar');
        const input = $('#cardToken');
        const msg = $('#msg');

        function setMsg(text, type = 'info') {
            if (!msg) return;
            msg.textContent = text || '';
            msg.style.color = type === 'error' ?
                'var(--color-danger, #ef4444)' :
                (type === 'success' ? 'var(--color-success, #22c55e)' : 'var(--color-text-muted)');
        }

        async function handleFetch403(response) {
            if (response.status !== 403) return false;
            try {
                const data = await response.clone().json();
                setMsg(data?.message || 'Recurso disponível apenas no plano Pro.', 'error');
            } catch (_) {
                setMsg('Recurso disponível apenas no plano Pro.', 'error');
            }
            return true;
        }

        btn?.addEventListener('click', async () => {
            const token = (input?.value || '').trim();
            setMsg('');

            if (!token) {
                setMsg('Informe o token do cartão.', 'error');
                input?.focus();
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Processando...';

            try {
                const resp = await fetch(`${base}api/billing/pagarme/checkout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': (window.CSRF || '')
                    },
                    body: JSON.stringify({
                        card_token: token
                    }),
                    credentials: 'include'
                });

                if (await handleFetch403(resp)) return;

                let json = {};
                try {
                    json = await resp.json();
                } catch {}

                if (!resp.ok || json?.status !== 'success') {
                    throw new Error(json?.message || `Erro ${resp.status}`);
                }

                setMsg('Assinatura criada. Após confirmação (webhook), seu plano será PRO.', 'success');
                input.value = '';

            } catch (e) {
                setMsg(e.message || 'Falha ao criar assinatura.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Assinar Pro';
            }
        });

        // Qualquer melhoria de UX:
        input?.focus();
    })();
</script>