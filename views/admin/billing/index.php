<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">

<style>
    /* =========================================================
 * BILLING / PLANOS - LUKRATO (Versão Premium)
 * =======================================================*/

    .billing-container {
        max-width: 900px;
        margin: 60px auto;
        background: var(--glass-bg);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        padding: 50px 60px;
        color: var(--color-text);
        text-align: center;
        position: relative;
        overflow: hidden;
        backdrop-filter: var(--glass-backdrop);
    }

    /* Efeito decorativo de fundo */
    .billing-container::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, var(--color-primary) 0%, transparent 70%);
        opacity: 0.08;
        border-radius: 50%;
        pointer-events: none;
        animation: float 8s ease-in-out infinite;
    }

    .billing-container::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -15%;
        width: 350px;
        height: 350px;
        background: radial-gradient(circle, var(--color-secondary) 0%, transparent 70%);
        opacity: 0.06;
        border-radius: 50%;
        pointer-events: none;
        animation: float 10s ease-in-out infinite reverse;
    }

    @keyframes float {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        50% {
            transform: translate(20px, -20px) scale(1.05);
        }
    }

    .billing-container h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
        color: var(--color-primary);
        letter-spacing: -0.02em;
        position: relative;
        z-index: 1;
    }

    .billing-container>p {
        font-size: var(--font-size-base);
        color: var(--color-text-muted);
        margin-bottom: 2.5rem;
        position: relative;
        z-index: 1;
    }

    .billing-plan {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 32px;
        margin-top: 40px;
        position: relative;
        z-index: 1;
    }

    /* Cards dos planos */
    .plan-card {
        background: var(--glass-bg);
        backdrop-filter: var(--glass-backdrop);
        border-radius: var(--radius-lg);
        padding: 32px 28px;
        border: 2px solid var(--glass-border);
        box-shadow: var(--shadow-md);
        transition: all var(--transition-normal);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .plan-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
        opacity: 0;
        transition: opacity var(--transition-fast);
    }

    .plan-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: var(--shadow-xl);
        border-color: var(--color-primary);
    }

    .plan-card:hover::before {
        opacity: 1;
    }

    .plan-card h2 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--color-text);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
    }

    .plan-card h2 i {
        font-size: 1.5rem;
        color: var(--color-primary);
    }

    .plan-card>p {
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
        margin: 0 0 1.5rem 0;
        min-height: 40px;
    }

    .plan-price {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 1.25rem 0;
        color: var(--color-primary);
        letter-spacing: -0.03em;
    }

    .plan-price small {
        font-size: var(--font-size-base);
        font-weight: 500;
        color: var(--color-text-muted);
    }

    /* Lista de features */
    .plan-features {
        list-style: none;
        padding: 0;
        margin: 1.5rem 0;
        text-align: left;
        flex-grow: 1;
    }

    .plan-features li {
        margin: 0.75rem 0;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: var(--font-size-sm);
        padding: var(--spacing-2) 0;
        transition: all var(--transition-fast);
    }

    .plan-features li:hover {
        transform: translateX(4px);
    }

    .plan-features li i {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        flex-shrink: 0;
        font-size: 0.75rem;
    }

    .plan-features li i.fa-check {
        background: var(--color-success);
        color: white;
    }

    .plan-features li i.fa-times {
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
        opacity: 0.5;
    }

    /* Botão de Assinatura Premium */
    .plan-subscribe-btn {
        margin-top: 1.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-2);
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: white;
        font-weight: 700;
        font-size: var(--font-size-base);
        border: none;
        border-radius: var(--radius-lg);
        padding: 16px 32px;
        cursor: pointer;
        transition: all var(--transition-normal);
        box-shadow: 0 8px 20px color-mix(in srgb, var(--color-primary) 40%, transparent);
        width: 100%;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Efeito de brilho animado */
    .plan-subscribe-btn::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.3) 50%,
                transparent 70%);
        transform: rotate(45deg);
        animation: shine 3s ease-in-out infinite;
    }

    @keyframes shine {

        0%,
        100% {
            transform: translateX(-100%) translateY(-100%) rotate(45deg);
        }

        50% {
            transform: translateX(100%) translateY(100%) rotate(45deg);
        }
    }

    /* Efeito de partículas */
    .plan-subscribe-btn::after {
        content: '✨';
        position: absolute;
        right: 20px;
        font-size: 1.2rem;
        animation: sparkle 2s ease-in-out infinite;
    }

    @keyframes sparkle {

        0%,
        100% {
            opacity: 0.5;
            transform: scale(1);
        }

        50% {
            opacity: 1;
            transform: scale(1.3);
        }
    }

    .plan-subscribe-btn:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 30px color-mix(in srgb, var(--color-primary) 50%, transparent);
    }

    .plan-subscribe-btn:active {
        transform: translateY(-2px) scale(1);
    }

    .plan-subscribe-btn span {
        position: relative;
        z-index: 1;
    }

    .plan-subscribe-btn i {
        position: relative;
        z-index: 1;
        font-size: 1.2rem;
        animation: bounce 2s ease-in-out infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-3px);
        }
    }

    /* Loading state */
    .plan-subscribe-btn.loading {
        pointer-events: none;
        background: linear-gradient(135deg,
                var(--color-primary),
                color-mix(in srgb, var(--color-primary) 70%, black));
    }

    .plan-subscribe-btn.loading::before {
        animation: none;
    }

    .plan-subscribe-btn.loading::after {
        display: none;
    }

    .plan-subscribe-btn.loading i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Disabled state */
    .plan-subscribe-btn:disabled:not(.loading) {
        background: var(--color-surface-muted);
        color: var(--color-text-muted);
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
        opacity: 0.7;
    }

    .plan-subscribe-btn:disabled::before,
    .plan-subscribe-btn:disabled::after {
        display: none;
    }

    .plan-subscribe-btn:disabled:hover {
        transform: none;
        box-shadow: none;
    }

    /* Status do plano ativo */
    .plan-active {
        border: 2px solid var(--color-success);
        background: linear-gradient(135deg,
                color-mix(in srgb, var(--color-success) 8%, transparent),
                color-mix(in srgb, var(--color-success) 4%, transparent));
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-success) 15%, transparent), var(--shadow-lg);
    }

    .plan-active::before {
        background: linear-gradient(90deg, var(--color-success), var(--color-success));
        opacity: 1;
        height: 5px;
    }

    .plan-active h2 {
        color: var(--color-success);
    }

    .plan-active h2::after {
        content: '✓ Ativo';
        display: inline-block;
        margin-left: auto;
        font-size: var(--font-size-xs);
        font-weight: 600;
        background: var(--color-success);
        color: white;
        padding: 4px 12px;
        border-radius: var(--radius-sm);
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    .plan-active .plan-subscribe-btn {
        background: var(--color-success);
    }

    .plan-active .plan-subscribe-btn::before {
        background: color-mix(in srgb, var(--color-success) 80%, black);
    }

    .plan-active .plan-subscribe-btn:hover {
        transform: translateY(-2px);
    }

    /* Badge de recomendado */
    .plan-recommended {
        position: relative;
    }

    .plan-recommended::after {
        content: 'Recomendado';
        position: absolute;
        top: -12px;
        right: 24px;
        background: linear-gradient(135deg, var(--color-warning), #f59e0b);
        color: white;
        font-size: var(--font-size-xs);
        font-weight: 700;
        padding: 6px 16px;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Mensagens de feedback */
    #msg {
        margin-top: 1rem;
        min-height: 1.5rem;
        font-size: var(--font-size-sm);
        font-weight: 500;
        padding: var(--spacing-2) var(--spacing-3);
        border-radius: var(--radius-md);
        transition: all var(--transition-fast);
    }

    #msg:not(:empty) {
        animation: slideInMessage 0.3s ease;
    }

    @keyframes slideInMessage {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Ripple effect */
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    /* Tema escuro - ajustes específicos */
    :root[data-theme="dark"] .billing-container {
        box-shadow: var(--shadow-xl), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    /* Tema claro - ajustes específicos */
    :root[data-theme="light"] .billing-container {
        background: rgba(255, 255, 255, 0.8);
    }

    :root[data-theme="light"] .plan-card {
        background: rgba(255, 255, 255, 0.6);
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .billing-container {
            margin: 40px 20px;
            padding: 32px 24px;
        }

        .billing-container h1 {
            font-size: 2rem;
        }

        .billing-plan {
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .plan-card {
            padding: 24px 20px;
        }

        .plan-price {
            font-size: 2rem;
        }

        .plan-recommended::after {
            top: -10px;
            right: 16px;
            font-size: 0.65rem;
            padding: 4px 12px;
        }

        .plan-subscribe-btn {
            padding: 14px 24px;
            font-size: var(--font-size-sm);
        }

        .plan-subscribe-btn::after {
            right: 12px;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .billing-container {
            padding: 24px 16px;
        }

        .billing-container h1 {
            font-size: 1.75rem;
        }

        .plan-card h2 {
            font-size: 1.5rem;
        }

        .plan-price {
            font-size: 1.75rem;
        }

        .plan-features li {
            font-size: var(--font-size-xs);
        }

        .plan-subscribe-btn {
            padding: 12px 20px;
            font-size: var(--font-size-xs);
            letter-spacing: 0.02em;
        }

        .plan-subscribe-btn i {
            font-size: 1rem;
        }
    }

    /* Animações de entrada */
    .billing-container[data-aos] .plan-card {
        opacity: 0;
        transform: translateY(30px);
        animation: slideUpPlan 0.6s ease forwards;
    }

    .billing-container[data-aos] .plan-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .billing-container[data-aos] .plan-card:nth-child(2) {
        animation-delay: 0.2s;
    }

    @keyframes slideUpPlan {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="billing-container" data-aos="fade-up">
    <h1>🚀 Escolha seu plano</h1>
    <p>Escolha o plano ideal para suas necessidades financeiras</p>

    <div class="billing-plan">
        <!-- Plano Gratuito -->
        <div class="plan-card <?= $user->isGratuito() ? 'plan-active' : '' ?>">
            <h2><i class="fas fa-gift"></i> Gratuito</h2>
            <p>Perfeito para começar a organizar suas finanças</p>
            <div class="plan-price">R$ 0<small>/mês</small></div>

            <ul class="plan-features">
                <li><i class="fas fa-check"></i> Controle básico de transações</li>
                <li><i class="fas fa-check"></i> Categorização de gastos</li>
                <li><i class="fas fa-times"></i> Relatórios avançados</li>
                <li><i class="fas fa-times"></i> Agendamentos</li>
                <li><i class="fas fa-times"></i> Exportação de dados</li>
            </ul>

            <?php if ($user->isGratuito()): ?>
                <button class="plan-subscribe-btn" disabled>
                    <i class="fas fa-check-circle"></i>
                    <span>Plano Atual</span>
                </button>
            <?php else: ?>
                <button class="plan-subscribe-btn" disabled>
                    <span>Plano Básico</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Plano Pro -->
        <div class="plan-card plan-recommended <?= $user->isPro() ? 'plan-active' : '' ?>">
            <h2><i class="fa-solid fa-crown"></i> Pro</h2>
            <p>Controle total e insights poderosos para suas finanças</p>
            <div class="plan-price">R$ 12<small>/mês</small></div>

            <ul class="plan-features">
                <li><i class="fa-solid fa-check"></i> Tudo do plano Gratuito</li>
                <li><i class="fa-solid fa-check"></i> Relatórios completos e detalhados</li>
                <li><i class="fa-solid fa-check"></i> Agendamentos automáticos</li>
                <li><i class="fa-solid fa-check"></i> Exportação ilimitada (CSV, Excel)</li>
                <li><i class="fa-solid fa-check"></i> Suporte prioritário</li>
            </ul>

            <?php if ($user->isPro()): ?>
                <button class="plan-subscribe-btn" disabled>
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Ativo até <?= htmlspecialchars(($user->plano_renova_em ?? $user->plan_renews_at) ?: '—') ?></span>
                </button>
            <?php else: ?>
                <button id="btnAssinar" class="plan-subscribe-btn">
                    <i class="fa-solid fa-rocket"></i>
                    <span>Assinar Pro Agora</span>
                </button>
                <div id="msg" aria-live="polite"></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if (isset($_GET['status'])): ?>
    <script>
        (function() {
            const s = '<?= htmlspecialchars($_GET['status'], ENT_QUOTES) ?>';
            if (typeof Swal !== 'undefined') {
                if (s === 'success') Swal.fire('Tudo certo!', 'Pagamento aprovado ✅', 'success');
                else if (s === 'pending') Swal.fire('Pagamento pendente', 'Aguardando confirmação…', 'info');
                else Swal.fire('Falhou', 'Pagamento não aprovado.', 'error');
            }
        })();
    </script>
<?php endif; ?>

<script>
    (function() {
        const base = '<?= BASE_URL ?>';
        const $ = (s) => document.querySelector(s);
        const btn = $('#btnAssinar');
        const msg = $('#msg');

        function setMsg(text, type = 'info') {
            if (!msg) return;
            msg.textContent = text || '';
            msg.style.color = type === 'error' ?
                'var(--color-danger,#ef4444)' :
                (type === 'success' ? 'var(--color-success,#22c55e)' : 'var(--color-text-muted)');
        }

        // a versão nova do seu handleFetch403 (já com “Assinar” levando a /billing) pode ficar separada
        async function handleFetch403(response, base) {
            if (response.status === 401) {
                const here = encodeURIComponent(location.pathname + location.search);
                location.href = `${base}login?return=${here}`;
                return true;
            }
            if (response.status === 403) {
                let m = 'Acesso não permitido.';
                try {
                    m = (await response.clone().json())?.message || m;
                } catch {}
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    const ret = await Swal.fire({
                        title: 'Acesso restrito',
                        html: m,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Assinar',
                        cancelButtonText: 'OK',
                        reverseButtons: true
                    });
                    if (ret.isConfirmed) location.href = `${base}billing`;
                } else {
                    if (confirm(`${m}\n\nIr para a página de assinatura agora?`)) {
                        location.href = `${base}billing`;
                    }
                }
                return true;
            }
            return false;
        }

        btn?.addEventListener('click', async () => {
            setMsg('');
            btn.disabled = true;
            btn.textContent = 'Redirecionando...';

            try {
                // ✅ define a variável resp aqui
                const resp = await fetch(`${base}api/mercadopago/checkout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': (window.CSRF || '')
                    },
                    credentials: 'include'
                });

                if (await handleFetch403(resp, base)) return;

                const json = await resp.json().catch(() => ({}));

                if (!resp.ok || json?.status !== 'success') {
                    throw new Error(json?.message || `Erro ${resp.status}`);
                }

                // procura o link nas duas formas possíveis
                const initPoint = json?.body?.init_point || json?.init_point;
                if (!initPoint) {
                    setMsg('Link de checkout não retornado.', 'error');
                    console.warn('Resposta do checkout:', json);
                    return;
                }

                // opcional: feedback antes de sair
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    await Swal.fire({
                        title: 'Indo para o pagamento...',
                        timer: 600,
                        showConfirmButton: false
                    });
                }

                // 🚀 redireciona para o checkout do MP
                window.location.href = initPoint;

            } catch (e) {
                setMsg(e.message || 'Falha ao criar checkout.', 'error');
                console.error(e);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Assinar Pro';
            }
        });
    })();
</script>