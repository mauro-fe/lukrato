<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<?php
$showOnboardingCongrats = !empty($_SESSION['onboarding_just_completed']);
if ($showOnboardingCongrats) {
    unset($_SESSION['onboarding_just_completed']);
}
?>

<?php if ($showOnboardingCongrats): ?>
<script>window.__lkFirstVisit = true;</script>
<?php endif; ?>

<style>
/* ── Onboarding Checklist (Modern) ── */
.lk-checklist {
    border-radius: var(--radius-xl);
    margin-bottom: var(--spacing-6);
    overflow: hidden;
    background: linear-gradient(135deg, rgba(var(--color-primary-rgb, 230,126,34), 0.04) 0%, var(--glass-bg) 40%, rgba(99,102,241,0.03) 100%);
    border: 1px solid var(--glass-border);
    animation: lk-checkIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    display: none;
    position: relative;
}
@keyframes lk-checkIn {
    from { opacity: 0; transform: translateY(-16px) scale(0.99); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Top accent shimmer */
.lk-checklist-accent {
    height: 3px;
    background: linear-gradient(90deg, var(--color-primary), #6366f1, #22c55e, var(--color-primary));
    background-size: 200% 100%;
    animation: lk-shimmer 3s ease infinite;
}
@keyframes lk-shimmer {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.lk-checklist-body {
    padding: var(--spacing-5) var(--spacing-6);
    position: relative;
}

/* Skip button */
.lk-checklist-dismiss {
    position: absolute;
    top: var(--spacing-4);
    right: var(--spacing-4);
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.06);
    color: var(--color-text-muted);
    border-radius: var(--radius-full);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.25s;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 5px 12px;
    backdrop-filter: blur(4px);
    z-index: 2;
}
.lk-checklist-dismiss:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.12);
    color: var(--color-text);
    transform: translateY(-1px);
}

/* Header */
.lk-checklist-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-5);
}
.lk-checklist-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 70%, #6366f1));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.4rem;
    box-shadow: 0 6px 20px color-mix(in srgb, var(--color-primary) 30%, transparent),
                0 2px 6px rgba(0,0,0,0.15);
}
.lk-checklist-title { flex: 1; }
.lk-checklist-title h2 {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--color-text);
    margin: 0 0 3px 0;
    letter-spacing: -0.01em;
}
.lk-checklist-title p {
    font-size: 0.78rem;
    color: var(--color-text-muted);
    margin: 0;
}

/* Badge */
.lk-checklist-badge {
    background: var(--color-surface);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-full);
    padding: 5px 14px;
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--color-text-muted);
    white-space: nowrap;
    letter-spacing: 0.5px;
}
.lk-checklist-badge.complete {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(34,197,94,0.3);
}

/* Progress bar */
.lk-checklist-progress {
    height: 5px;
    background: var(--color-surface-muted);
    border-radius: 4px;
    margin-bottom: var(--spacing-5);
    overflow: hidden;
    position: relative;
}
.lk-checklist-progress-fill {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(90deg, var(--color-primary), #6366f1, var(--color-success));
    background-size: 200% 100%;
    animation: lk-shimmer 3s ease infinite;
    transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    width: 0%;
    box-shadow: 0 0 10px color-mix(in srgb, var(--color-primary) 30%, transparent);
}

/* Item grid */
.lk-checklist-items {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

/* Individual card items */
.lk-checklist-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 12px;
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid var(--glass-border);
    background: rgba(255,255,255,0.02);
    position: relative;
    overflow: hidden;
}
.lk-checklist-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, transparent 60%, rgba(255,255,255,0.02));
    pointer-events: none;
    transition: opacity 0.3s;
    opacity: 0;
}
.lk-checklist-item:not(.done):hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.lk-checklist-item:not(.done):hover::before {
    opacity: 1;
}
.lk-checklist-item.done {
    opacity: 0.45;
    background: rgba(34,197,94,0.03);
    border-color: rgba(34,197,94,0.08);
}

/* Check circle */
.lk-checklist-check {
    width: 22px;
    height: 22px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.6rem;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.lk-checklist-item:not(.done) .lk-checklist-check {
    border: 2px solid rgba(255,255,255,0.12);
    background: transparent;
    color: transparent;
}
.lk-checklist-item.done .lk-checklist-check {
    border: none;
    background: #22c55e;
    color: #fff;
    box-shadow: 0 2px 8px rgba(34,197,94,0.35);
    transform: scale(1.05);
}

/* Icon */
.lk-checklist-item-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
    transition: transform 0.3s;
}
.lk-checklist-item:not(.done):hover .lk-checklist-item-icon {
    transform: scale(1.1);
}

/* Text */
.lk-checklist-item-text { flex: 1; min-width: 0; }
.lk-checklist-item-label {
    font-size: 0.8rem;
    font-weight: 700;
    display: block;
    line-height: 1.3;
    letter-spacing: -0.01em;
}
.lk-checklist-item-desc {
    font-size: 0.68rem;
    color: var(--color-text-muted);
    display: block;
    line-height: 1.3;
    margin-top: 1px;
}
.lk-checklist-item.done .lk-checklist-item-label {
    text-decoration: line-through;
    opacity: 0.7;
}
.lk-checklist-item-arrow {
    color: var(--color-text-muted);
    font-size: 0.65rem;
    opacity: 0;
    transition: all 0.25s;
}
.lk-checklist-item:not(.done):hover .lk-checklist-item-arrow {
    opacity: 1;
    transform: translateX(2px);
}
.lk-checklist-item.done .lk-checklist-item-arrow {
    display: none;
}

/* All-complete celebration */
.lk-checklist-complete {
    text-align: center;
    padding: var(--spacing-5) var(--spacing-4);
    grid-column: 1 / -1;
}
.lk-checklist-complete-icon {
    font-size: 3rem;
    margin-bottom: var(--spacing-3);
    animation: lk-bounce 1s ease infinite;
}
@keyframes lk-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
.lk-checklist-complete h3 {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--color-text);
    margin: 0 0 6px 0;
}
.lk-checklist-complete p {
    font-size: 0.8rem;
    color: var(--color-text-muted);
    margin: 0;
}

@media (max-width: 900px) {
    .lk-checklist-items { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .lk-checklist-body { padding: var(--spacing-4); }
    .lk-checklist-items { grid-template-columns: 1fr; }
    .lk-checklist-item-desc { display: none; }
    .lk-checklist-item { padding: 12px; }
}
</style>

<!-- Onboarding Checklist (persistent) -->
<div class="lk-checklist" id="onboardingChecklist">
    <div class="lk-checklist-accent"></div>
    <div class="lk-checklist-body">
        <button class="lk-checklist-dismiss" id="checklistDismiss" title="Pular etapas">
            <span>Pular</span>
            <i data-lucide="x" style="font-size:0.7rem;"></i>
        </button>

        <div class="lk-checklist-header">
            <div class="lk-checklist-icon-box">🚀</div>
            <div class="lk-checklist-title">
                <h2>Primeiros passos</h2>
                <p>Complete as etapas para aproveitar o melhor do Lukrato</p>
            </div>
            <div class="lk-checklist-badge" id="checklistBadge">0/6</div>
        </div>

        <div class="lk-checklist-progress">
            <div class="lk-checklist-progress-fill" id="checklistProgressFill"></div>
        </div>

        <div class="lk-checklist-items" id="checklistItems">
            <!-- JS populates -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var firstVisit = !!window.__lkFirstVisit;
    var SKIP_KEY = 'lk_checklist_skipped';
    var el = document.getElementById('onboardingChecklist');

    // User explicitly skipped? Don't show anymore
    if (localStorage.getItem(SKIP_KEY) === '1') return;

    fetch(BASE_URL + 'api/onboarding/checklist', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) return;
            var data = res.data;

            // All complete and not first visit → auto-hide after this visit
            if (data.all_complete && !firstVisit) return;

            renderChecklist(data);
            el.style.display = 'block';

            if (firstVisit) {
                fireConfetti();
                setTimeout(function() {
                    if (typeof window.checkPendingAchievements === 'function') {
                        window.gamificationPaused = false;
                        window.checkPendingAchievements();
                    }
                }, 1500);
            }
        })
        .catch(function() {});

    // Dismiss → confirm skip
    document.getElementById('checklistDismiss').addEventListener('click', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Pular primeiros passos?',
                text: 'Você pode sempre acessar essas funcionalidades pelo menu lateral.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'var(--color-primary, #e67e22)',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, pular',
                cancelButtonText: 'Continuar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    localStorage.setItem(SKIP_KEY, '1');
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-16px)';
                    el.style.transition = 'all 0.3s ease';
                    setTimeout(function() { el.style.display = 'none'; }, 300);
                }
            });
        } else {
            if (confirm('Pular primeiros passos? Você pode acessar tudo pelo menu lateral.')) {
                localStorage.setItem(SKIP_KEY, '1');
                el.style.display = 'none';
            }
        }
    });

    function renderChecklist(data) {
        var badge = document.getElementById('checklistBadge');
        var fill  = document.getElementById('checklistProgressFill');
        var box   = document.getElementById('checklistItems');

        badge.textContent = data.done_count + '/' + data.total;
        if (data.all_complete) badge.classList.add('complete');

        var pct = (data.done_count / data.total) * 100;
        setTimeout(function() { fill.style.width = pct + '%'; }, 100);

        // All complete → celebration
        if (data.all_complete) {
            box.innerHTML =
                '<div class="lk-checklist-complete">' +
                    '<div class="lk-checklist-complete-icon">🎉</div>' +
                    '<h3>Parabéns! Você completou tudo</h3>' +
                    '<p>Agora é só manter o controle das suas finanças</p>' +
                '</div>';

            if (firstVisit) {
                setTimeout(function() {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-16px)';
                    el.style.transition = 'all 0.5s ease';
                    setTimeout(function() { el.style.display = 'none'; }, 500);
                }, 8000);
            }
            return;
        }

        // Sort: pending first, done last
        var sorted = data.items.slice().sort(function(a, b) { return a.done - b.done; });

        box.innerHTML = sorted.map(function(item) {
            return '<a href="' + BASE_URL + item.href + '" class="lk-checklist-item ' + (item.done ? 'done' : '') + '">' +
                '<div class="lk-checklist-check"><i data-lucide="check"></i></div>' +
                '<div class="lk-checklist-item-icon" style="background:color-mix(in srgb, ' + item.color + ' 15%, var(--color-surface));color:' + item.color + ';">' +
                    '<i data-lucide="' + item.icon + '"></i>' +
                '</div>' +
                '<div class="lk-checklist-item-text">' +
                    '<span class="lk-checklist-item-label">' + item.label + '</span>' +
                    '<span class="lk-checklist-item-desc">' + item.description + '</span>' +
                '</div>' +
                '<i data-lucide="chevron-right" class="lk-checklist-item-arrow"></i>' +
            '</a>';
        }).join('');
    }

    function fireConfetti() {
        if (typeof confetti !== 'function') return;
        var duration = 3500;
        var end = Date.now() + duration;
        var defaults = { startVelocity: 35, spread: 360, ticks: 70, zIndex: 99999 };

        var interval = setInterval(function() {
            var timeLeft = end - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);
            var count = 60 * (timeLeft / duration);
            try {
                confetti(Object.assign({}, defaults, {
                    particleCount: count,
                    origin: { x: Math.random() * 0.3 + 0.1, y: Math.random() - 0.2 }
                }));
                confetti(Object.assign({}, defaults, {
                    particleCount: count,
                    origin: { x: Math.random() * 0.3 + 0.6, y: Math.random() - 0.2 }
                }));
            } catch(e) { clearInterval(interval); }
        }, 200);
    }
});
</script>

<section class="modern-dashboard">
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

    <!-- Gamificação -->
    <section class="gamification-section" data-aos="fade-up" data-aos-duration="500">
        <div class="gamification-header">
            <div class="gamification-title">
                <i data-lucide="trophy"></i>
                <span>Seu Progresso</span>
                <span class="pro-badge" id="proBadge" style="display: none;">
                    <i data-lucide="gem"></i> PRO
                </span>
            </div>
            <div class="level-badge" id="userLevel">
                <i data-lucide="star"></i>
                <span>Nível 1</span>
            </div>
        </div>

        <div class="gamification-grid">
            <!-- Streak -->
            <div class="streak-card">
                <div class="streak-icon">🔥</div>
                <div class="streak-number" id="streakDays">0</div>
                <div class="streak-label">Dias Ativos</div>
                <div class="streak-protection" id="streakProtection" style="display: none;">
                    <i data-lucide="shield"></i>
                    <span>Proteção disponível</span>
                </div>
            </div>

            <!-- Progresso -->
            <div class="level-progress-card">
                <div class="level-progress-header">
                    <span class="level-progress-label">Progresso para próximo nível</span>
                    <span class="level-progress-points" id="levelProgressPoints">0 / 300 pontos</span>
                </div>
                <div class="level-progress-bar-container">
                    <div class="level-progress-bar" id="levelProgressBar" style="width: 0%"></div>
                </div>
                <div class="level-progress-text" id="levelProgressText">Ganhe mais pontos para avançar!</div>
            </div>

            <!-- Badges -->
            <div class="badges-card">
                <div class="badges-title">
                    <i data-lucide="medal"></i>
                    <span>Conquistas</span>
                    <a href="<?= BASE_URL ?>gamification" class="btn-view-all">Ver todas</a>
                </div>
                <div class="badges-grid" id="badgesGrid">
                    <!-- Preenchido via JS -->
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                    <div class="badge-skeleton"></div>
                </div>
            </div>
        </div>

        <!-- Mini Stats -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-value" id="totalLancamentos">0</div>
                <div class="stat-mini-label">Lançamentos</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="totalCategorias">0</div>
                <div class="stat-mini-label">Categorias</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="mesesAtivos">0</div>
                <div class="stat-mini-label">Meses Ativos</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="pontosTotal">0</div>
                <div class="stat-mini-label">Pontos</div>
            </div>
        </div>

        <!-- Call to Action Pro (apenas para usuários Free) -->
        <div class="pro-cta-card" id="proCTA" style="display: none;">
            <div class="pro-cta-content">
                <div class="pro-cta-icon">
                    <i data-lucide="rocket"></i>
                </div>
                <div class="pro-cta-text">
                    <h3>Acelere seu progresso com o Plano Pro</h3>
                    <p>Ganhe 1.5x mais pontos, proteção de streak e conquistas exclusivas!</p>
                </div>
                <button class="btn-pro-upgrade">
                    <i data-lucide="gem"></i>
                    Conhecer o Pro
                </button>
            </div>
        </div>
    </section>

    <!-- KPI Cards -->
    <section class="kpi-grid" role="region" aria-label="Indicadores principais">
        <div data-aos="fade-up" data-aos-duration="500">
            <div class="modern-kpi" id="saldoCard">
                <div class="kpi-header">
                    <div class="kpi-icon balance">
                        <i data-lucide="wallet"></i>
                    </div>
                    <span class="kpi-label">Saldo Atual</span>
                </div>
                <div class="kpi-value loading" id="saldoValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
            <div class="modern-kpi" id="receitasCard">
                <div class="kpi-header">
                    <div class="kpi-icon income">
                        <i data-lucide="arrow-up"></i>
                    </div>
                    <span class="kpi-label">Receitas do Mês</span>
                </div>
                <div class="kpi-value income loading" id="receitasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
            <div class="modern-kpi" id="despesasCard">
                <div class="kpi-header">
                    <div class="kpi-icon expense">
                        <i data-lucide="arrow-down"></i>
                    </div>
                    <span class="kpi-label">Despesas do Mês</span>
                </div>
                <div class="kpi-value expense loading" id="despesasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
            <div class="modern-kpi" id="saldoMesCard">
                <div class="kpi-header">
                    <div class="kpi-icon balance">
                        <i data-lucide="scale"></i>
                    </div>
                    <span class="kpi-label">Saldo do Mês</span>
                </div>
                <div class="kpi-value loading" id="saldoMesValue">R$ 0,00</div>
            </div>
        </div>
    </section>

    <!-- Previsão Financeira (Agendamentos) -->
    <section class="provisao-section" id="provisaoSection" data-aos="fade-up" data-aos-duration="500">
        <h2 class="provisao-title">
            <i data-lucide="calendar-check"></i>
            Previsão Financeira
        </h2>

        <!-- Alertas de vencidos -->
        <div class="provisao-alerts-container" id="provisaoAlertsContainer">
            <!-- Alerta de despesas vencidas -->
            <div class="provisao-alert despesas" id="provisaoAlertDespesas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="triangle-alert"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertDespesasCount">0</strong> despesa(s) vencida(s) totalizando
                    <strong id="provisaoAlertDespesasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>agendamentos?tipo=despesa&status=vencido" class="provisao-alert-link">Ver <i data-lucide="arrow-right"></i></a>
            </div>
            <!-- Alerta de receitas vencidas (não recebidas) -->
            <div class="provisao-alert receitas" id="provisaoAlertReceitas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="info"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertReceitasCount">0</strong> recebimento(s) atrasado(s) totalizando
                    <strong id="provisaoAlertReceitasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>agendamentos?tipo=receita&status=vencido" class="provisao-alert-link">Ver <i data-lucide="arrow-right"></i></a>
            </div>
            <!-- Alerta de faturas vencidas -->
            <div class="provisao-alert faturas" id="provisaoAlertFaturas" style="display:none;">
                <div class="provisao-alert-icon"><i data-lucide="credit-card"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertFaturasCount">0</strong> fatura(s) vencida(s) totalizando
                    <strong id="provisaoAlertFaturasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>faturas" class="provisao-alert-link">Ver <i data-lucide="arrow-right"></i></a>
            </div>
        </div>

        <!-- Cards de Provisão -->
        <div class="provisao-grid">
            <div class="provisao-card pagar">
                <div class="provisao-card-icon"><i data-lucide="arrow-up"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">A Pagar</span>
                    <span class="provisao-card-value" id="provisaoPagar">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoPagarCount">0 agendamentos</span>
                </div>
            </div>
            <div class="provisao-card receber">
                <div class="provisao-card-icon"><i data-lucide="arrow-down"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">A Receber</span>
                    <span class="provisao-card-value" id="provisaoReceber">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoReceberCount">0 agendamentos</span>
                </div>
            </div>
            <div class="provisao-card projetado">
                <div class="provisao-card-icon"><i data-lucide="line-chart"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">Saldo Projetado</span>
                    <span class="provisao-card-value" id="provisaoProjetado">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoProjetadoLabel">saldo atual + previsão</span>
                </div>
            </div>
        </div>

        <!-- Próximos Vencimentos -->
        <div class="provisao-proximos">
            <div class="provisao-proximos-header">
                <span class="provisao-proximos-title" id="provisaoProximosTitle"><i data-lucide="clock"></i> Próximos Vencimentos</span>
                <a href="<?= BASE_URL ?>agendamentos" class="provisao-ver-todos" id="provisaoVerTodos">Ver todos <i data-lucide="arrow-right"></i></a>
            </div>
            <div class="provisao-proximos-list" id="provisaoProximosList">
                <div class="provisao-empty" id="provisaoEmpty">
                    <i data-lucide="circle-check"></i>
                    <span>Nenhum vencimento pendente</span>
                </div>
            </div>
        </div>

        <!-- Parcelas Ativas -->
        <div class="provisao-parcelas" id="provisaoParcelas" style="display:none;">
            <div class="provisao-parcelas-icon"><i data-lucide="layers"></i></div>
            <span class="provisao-parcelas-text" id="provisaoParcelasText">0 parcelamentos ativos</span>
            <span class="provisao-parcelas-valor" id="provisaoParcelasValor">R$ 0,00/mês</span>
        </div>

        <!-- Overlay PRO (para free users) -->
        <div class="provisao-pro-overlay" id="provisaoProOverlay" style="display:none;">
            <div class="provisao-pro-content">
                <div class="provisao-pro-icon">
                    <i data-lucide="gem"></i>
                </div>
                <h3>Previsão Financeira</h3>
                <p>Veja quanto vai pagar, receber e como ficará seu saldo. Disponível no plano <strong>Pro</strong>.</p>
                <button class="provisao-pro-btn" onclick="window.location.href='<?= BASE_URL ?>planos'">
                    <i data-lucide="rocket"></i> Conhecer o Pro
                </button>
            </div>
        </div>
    </section>

    <!-- Chart -->
    <section class="chart-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="chart-title">Evolução Financeira</h2>
        <div class="chart-wrapper">
            <div class="chart-loading" id="chartLoading"></div>
            <canvas id="evolutionChart" role="img" aria-label="Gráfico de evolução do saldo"></canvas>
        </div>
    </section>

    <!-- Table -->
    <section class="table-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="table-title">Últimos Lançamentos</h2>

        <div class="empty-state" id="emptyState" style="display:none;">
            <div class="empty-icon">
                <i data-lucide="receipt"></i>
            </div>
            <h3>Nenhum lançamento encontrado</h3>
            <p>Comece adicionando sua primeira transação para acompanhar suas finanças</p>
        </div>

        <!-- Cards Mobile -->
        <div class="transactions-cards" id="transactionsCards"></div>

        <!-- Tabela Desktop -->
        <div class="table-wrapper">
            <table class="modern-table" id="transactionsTable">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Conta</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <tr class="lk-loading-row">
                        <td colspan="7" style="text-align:center;padding:2rem 1rem;">
                            <div class="lk-loading-state">
                                <div class="spinner-border" role="status" style="width:2rem;height:2rem;color:var(--color-primary);">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <p style="margin:0.75rem 0 0;color:var(--color-text-muted);font-size:0.85rem;">Carregando transações...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</section>

<!-- Gamification JS -->
<script>
    // Define BASE_URL global para gamification script
    window.BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/gamification-dashboard.js?v=<?= time() ?>"></script>