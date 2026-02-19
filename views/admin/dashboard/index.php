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
/* ── Onboarding Checklist ── */
.lk-checklist {
    border-radius: var(--radius-xl);
    margin-bottom: var(--spacing-6);
    overflow: hidden;
    border: 1px solid var(--glass-border);
    background: var(--glass-bg);
    animation: lk-checkIn 0.5s ease-out;
    display: none; /* shown by JS */
}
@keyframes lk-checkIn {
    from { opacity: 0; transform: translateY(-16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.lk-checklist-accent {
    height: 4px;
    background: linear-gradient(90deg, var(--color-primary), #6366f1, var(--color-success));
}
.lk-checklist-body {
    padding: var(--spacing-5) var(--spacing-6);
    position: relative;
}
.lk-checklist-dismiss {
    position: absolute;
    top: var(--spacing-3);
    right: var(--spacing-3);
    background: transparent;
    border: none;
    color: var(--color-text-muted);
    width: 28px;
    height: 28px;
    border-radius: var(--radius-full);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 0.85rem;
}
.lk-checklist-dismiss:hover {
    background: var(--color-surface-muted);
    color: var(--color-text);
}

/* Header */
.lk-checklist-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-4);
}
.lk-checklist-icon-box {
    width: 44px;
    height: 44px;
    border-radius: var(--radius-lg);
    background: var(--color-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.3rem;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 25%, transparent);
}
.lk-checklist-title { flex: 1; }
.lk-checklist-title h2 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 2px 0;
}
.lk-checklist-title p {
    font-size: 0.78rem;
    color: var(--color-text-muted);
    margin: 0;
}
.lk-checklist-badge {
    background: var(--color-surface);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-full);
    padding: 4px 12px;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--color-text-muted);
    white-space: nowrap;
}
.lk-checklist-badge.complete {
    background: var(--color-success);
    color: #fff;
    border-color: var(--color-success);
}

/* Progress bar */
.lk-checklist-progress {
    height: 6px;
    background: var(--color-surface-muted);
    border-radius: 3px;
    margin-bottom: var(--spacing-4);
    overflow: hidden;
}
.lk-checklist-progress-fill {
    height: 100%;
    border-radius: 3px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-success));
    transition: width 0.6s ease;
    width: 0%;
}

/* Item rows */
.lk-checklist-items {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}
.lk-checklist-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-3);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: var(--color-text);
    transition: all 0.2s;
    border: 1px solid transparent;
}
.lk-checklist-item:not(.done):hover {
    background: var(--color-surface);
    border-color: var(--glass-border);
}
.lk-checklist-item.done {
    opacity: 0.55;
}

/* Check circle */
.lk-checklist-check {
    width: 24px;
    height: 24px;
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.7rem;
    transition: all 0.3s;
}
.lk-checklist-item:not(.done) .lk-checklist-check {
    border: 2px solid var(--color-border);
    background: transparent;
    color: transparent;
}
.lk-checklist-item.done .lk-checklist-check {
    border: none;
    background: var(--color-success);
    color: #fff;
}

/* Icon + text */
.lk-checklist-item-icon {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}
.lk-checklist-item-text { flex: 1; min-width: 0; }
.lk-checklist-item-label {
    font-size: 0.82rem;
    font-weight: 600;
    display: block;
    line-height: 1.3;
}
.lk-checklist-item-desc {
    font-size: 0.72rem;
    color: var(--color-text-muted);
    display: block;
    line-height: 1.3;
}
.lk-checklist-item.done .lk-checklist-item-label {
    text-decoration: line-through;
}
.lk-checklist-item-arrow {
    color: var(--color-text-muted);
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.lk-checklist-item:not(.done):hover .lk-checklist-item-arrow {
    opacity: 1;
}
.lk-checklist-item.done .lk-checklist-item-arrow {
    display: none;
}

/* All-complete celebration */
.lk-checklist-complete {
    text-align: center;
    padding: var(--spacing-4) 0;
}
.lk-checklist-complete-icon { font-size: 2.5rem; margin-bottom: var(--spacing-2); }
.lk-checklist-complete h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 4px 0;
}
.lk-checklist-complete p {
    font-size: 0.8rem;
    color: var(--color-text-muted);
    margin: 0;
}

@media (max-width: 600px) {
    .lk-checklist-body { padding: var(--spacing-4); }
    .lk-checklist-item-desc { display: none; }
}
</style>

<!-- Onboarding Checklist (persistent) -->
<div class="lk-checklist" id="onboardingChecklist">
    <div class="lk-checklist-accent"></div>
    <div class="lk-checklist-body">
        <button class="lk-checklist-dismiss" id="checklistDismiss" title="Fechar">
            <i class="fas fa-times"></i>
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
    var DISMISS_KEY = 'lk_checklist_dismissed';
    var el = document.getElementById('onboardingChecklist');

    // Already dismissed?
    if (localStorage.getItem(DISMISS_KEY) === '1' && !firstVisit) return;

    fetch(BASE_URL + 'api/onboarding/checklist', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) return;
            var data = res.data;

            // If all complete and not the very first dashboard visit, don't show
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

    // Dismiss handler
    document.getElementById('checklistDismiss').addEventListener('click', function() {
        localStorage.setItem(DISMISS_KEY, '1');
        el.style.opacity = '0';
        el.style.transform = 'translateY(-16px)';
        el.style.transition = 'all 0.3s ease';
        setTimeout(function() { el.style.display = 'none'; }, 300);
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
                '<div class="lk-checklist-check"><i class="fas fa-check"></i></div>' +
                '<div class="lk-checklist-item-icon" style="background:color-mix(in srgb, ' + item.color + ' 15%, var(--color-surface));color:' + item.color + ';">' +
                    '<i class="fas ' + item.icon + '"></i>' +
                '</div>' +
                '<div class="lk-checklist-item-text">' +
                    '<span class="lk-checklist-item-label">' + item.label + '</span>' +
                    '<span class="lk-checklist-item-desc">' + item.description + '</span>' +
                '</div>' +
                '<i class="fas fa-chevron-right lk-checklist-item-arrow"></i>' +
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
                <i class="fas fa-trophy"></i>
                <span>Seu Progresso</span>
                <span class="pro-badge" id="proBadge" style="display: none;">
                    <i class="fas fa-gem"></i> PRO
                </span>
            </div>
            <div class="level-badge" id="userLevel">
                <i class="fas fa-star"></i>
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
                    <i class="fas fa-shield-alt"></i>
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
                    <i class="fas fa-medal"></i>
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
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="pro-cta-text">
                    <h3>Acelere seu progresso com o Plano Pro</h3>
                    <p>Ganhe 1.5x mais pontos, proteção de streak e conquistas exclusivas!</p>
                </div>
                <button class="btn-pro-upgrade">
                    <i class="fas fa-gem"></i>
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
                        <i class="fas fa-wallet"></i>
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
                        <i class="fas fa-arrow-up"></i>
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
                        <i class="fas fa-arrow-down"></i>
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
                        <i class="fas fa-balance-scale"></i>
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
            <i class="fas fa-calendar-check"></i>
            Previsão Financeira
        </h2>

        <!-- Alertas de vencidos -->
        <div class="provisao-alerts-container" id="provisaoAlertsContainer">
            <!-- Alerta de despesas vencidas -->
            <div class="provisao-alert despesas" id="provisaoAlertDespesas" style="display:none;">
                <div class="provisao-alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertDespesasCount">0</strong> despesa(s) vencida(s) totalizando
                    <strong id="provisaoAlertDespesasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>agendamentos?tipo=despesa&status=vencido" class="provisao-alert-link">Ver <i class="fas fa-arrow-right"></i></a>
            </div>
            <!-- Alerta de receitas vencidas (não recebidas) -->
            <div class="provisao-alert receitas" id="provisaoAlertReceitas" style="display:none;">
                <div class="provisao-alert-icon"><i class="fas fa-info-circle"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertReceitasCount">0</strong> recebimento(s) atrasado(s) totalizando
                    <strong id="provisaoAlertReceitasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>agendamentos?tipo=receita&status=vencido" class="provisao-alert-link">Ver <i class="fas fa-arrow-right"></i></a>
            </div>
            <!-- Alerta de faturas vencidas -->
            <div class="provisao-alert faturas" id="provisaoAlertFaturas" style="display:none;">
                <div class="provisao-alert-icon"><i class="fas fa-credit-card"></i></div>
                <div class="provisao-alert-text">
                    <strong id="provisaoAlertFaturasCount">0</strong> fatura(s) vencida(s) totalizando
                    <strong id="provisaoAlertFaturasTotal">R$ 0,00</strong>
                </div>
                <a href="<?= BASE_URL ?>faturas" class="provisao-alert-link">Ver <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <!-- Cards de Provisão -->
        <div class="provisao-grid">
            <div class="provisao-card pagar">
                <div class="provisao-card-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">A Pagar</span>
                    <span class="provisao-card-value" id="provisaoPagar">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoPagarCount">0 agendamentos</span>
                </div>
            </div>
            <div class="provisao-card receber">
                <div class="provisao-card-icon"><i class="fas fa-arrow-down"></i></div>
                <div class="provisao-card-body">
                    <span class="provisao-card-label">A Receber</span>
                    <span class="provisao-card-value" id="provisaoReceber">R$ 0,00</span>
                    <span class="provisao-card-count" id="provisaoReceberCount">0 agendamentos</span>
                </div>
            </div>
            <div class="provisao-card projetado">
                <div class="provisao-card-icon"><i class="fas fa-chart-line"></i></div>
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
                <span class="provisao-proximos-title" id="provisaoProximosTitle"><i class="fas fa-clock"></i> Próximos Vencimentos</span>
                <a href="<?= BASE_URL ?>agendamentos" class="provisao-ver-todos" id="provisaoVerTodos">Ver todos <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="provisao-proximos-list" id="provisaoProximosList">
                <div class="provisao-empty" id="provisaoEmpty">
                    <i class="fas fa-check-circle"></i>
                    <span>Nenhum vencimento pendente</span>
                </div>
            </div>
        </div>

        <!-- Parcelas Ativas -->
        <div class="provisao-parcelas" id="provisaoParcelas" style="display:none;">
            <div class="provisao-parcelas-icon"><i class="fas fa-layer-group"></i></div>
            <span class="provisao-parcelas-text" id="provisaoParcelasText">0 parcelamentos ativos</span>
            <span class="provisao-parcelas-valor" id="provisaoParcelasValor">R$ 0,00/mês</span>
        </div>

        <!-- Overlay PRO (para free users) -->
        <div class="provisao-pro-overlay" id="provisaoProOverlay" style="display:none;">
            <div class="provisao-pro-content">
                <div class="provisao-pro-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h3>Previsão Financeira</h3>
                <p>Veja quanto vai pagar, receber e como ficará seu saldo. Disponível no plano <strong>Pro</strong>.</p>
                <button class="provisao-pro-btn" onclick="window.location.href='<?= BASE_URL ?>planos'">
                    <i class="fas fa-rocket"></i> Conhecer o Pro
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
                <i class="fas fa-receipt"></i>
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
                <tbody id="transactionsTableBody"></tbody>
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