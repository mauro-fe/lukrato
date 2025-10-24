<?php
// Normaliza dados vindos do controller
$data = (isset($data) && is_array($data)) ? $data : [];

$investments       = $data['investments']       ?? [];
$totalInvested     = (float)($data['totalInvested']     ?? ($data['totallnvested'] ?? 0));
$currentValue      = (float)($data['currentValue']      ?? 0);
$profit            = (float)($data['profit']            ?? ($currentValue - $totalInvested));
$profitPercentage  = (float)($data['profitPercentage']  ?? ($totalInvested > 0 ? (($profit / $totalInvested) * 100) : 0));

$statsByCategory   = $data['statsByCategory']   ?? ($statsByCategory ?? []);
$categories        = $categories ?? ($data['categories'] ?? []);
?>

<!-- CSS da página -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-investimentos-index.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-partials-modals-modal_investimentos.css">


<div class="main-content">
    <div class="page-header">
        <!-- Botão laranja -->
        <button
            type="button"
            class="btn-invest"
            data-bs-toggle="modal"
            data-bs-target="#modal-investimentos"
            title="Adicionar investimento">
            <i class="fa-solid fa-plus"></i> Novo investimento
        </button>
    </div>

    <!-- KPIs -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-ico blue"><i class="fa-solid fa-wallet"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Investido</span>
                <span class="stat-value">R$ <?= number_format($totalInvested, 2, ',', '.') ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-ico green"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="stat-info">
                <span class="stat-label">Valor Atual</span>
                <span class="stat-value">R$ <?= number_format((float)$currentValue, 2, ',', '.') ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-ico <?= ($profit >= 0 ? 'green' : 'red') ?>">
                <i class="fa-solid fa-<?= ($profit >= 0 ? 'arrow-up' : 'arrow-down') ?>"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Lucro/Prejuízo</span>
                <span class="stat-value <?= ($profit >= 0 ? 'positive' : 'negative') ?>">
                    <?= $profit >= 0 ? '+' : '−' ?>R$ <?= number_format(abs((float)$profit), 2, ',', '.') ?>
                    (<?= number_format((float)$profitPercentage, 2, ',', '.') ?>%)
                </span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-ico orange"><i class="fa-solid fa-briefcase"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total de Ativos</span>
                <span class="stat-value"><?= (is_array($investments) ? count($investments) : 0) ?></span>
            </div>
        </div>
    </section>

    <!-- Gráfico + Resumo -->
    <section class="content-grid">
        <!-- Doughnut -->
        <div class="card">
            <div class="card-header">
                <h3>Distribuição por Categoria</h3>
            </div>
            <div class="card-body">
                <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>

        <!-- Resumo por Categoria -->
        <div class="card">
            <div class="card-header">
                <h3>Resumo por Categoria</h3>
            </div>
            <div class="card-body">
                <div class="cat-list">
                    <?php foreach ($statsByCategory as $c): ?>
                        <div class="cat-row">
                            <div class="cat-left">
                                <span class="cat-dot" style="background:<?= htmlspecialchars($c['color'] ?? '#64748b') ?>"></span>
                                <?= htmlspecialchars($c['category'] ?? '-') ?>
                            </div>
                            <div class="cat-val">
                                R$ <?= number_format((float)($c['value'] ?? 0), 2, ',', '.') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($statsByCategory)): ?>
                        <div class="empty-state">Nenhuma categoria para exibir.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card full-width">
            <div class="card-header">
                <h3>Meus Investimentos</h3>
            </div>
            <div class="card-body">
                <?php if (empty($investments)): ?>
                    <div class="empty-state">
                        <div>Nenhum investimento cadastrado ainda.</div>
                        <button class="btn-invest" data-bs-toggle="modal" data-bs-target="#modal-investimentos">
                            <i class="fa-solid fa-plus"></i> Adicionar primeiro investimento
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Ticker</th>
                                    <th>Quantidade</th>
                                    <th>Preço Médio</th>
                                    <th>Preço Atual</th>
                                    <th>Valor Total</th>
                                    <th>Rentabilidade</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($investments as $inv):
                                    $rowInvested   = (float)$inv['quantity'] * (float)$inv['avg_price'];
                                    $rowCurrent    = (float)$inv['quantity'] * (float)($inv['current_price'] ?? $inv['avg_price']);
                                    $rowProfit     = $rowCurrent - $rowInvested;
                                    $rowProfitPerc = $rowInvested > 0 ? ($rowProfit / $rowInvested) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($inv['name'] ?? '-') ?></strong></td>
                                        <td>
                                            <span class="badge" style="background:<?= htmlspecialchars($inv['color'] ?? '#475569') ?>">
                                                <?= htmlspecialchars($inv['category_name'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($inv['ticker'] ?? '-') ?></td>
                                        <td><?= number_format((float)$inv['quantity'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float)$inv['avg_price'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float)($inv['current_price'] ?? $inv['avg_price']), 2, ',', '.') ?></td>
                                        <td><strong>R$ <?= number_format((float)$rowCurrent, 2, ',', '.') ?></strong></td>
                                        <td>
                                            <span class="profit-badge <?= $rowProfit >= 0 ? 'positive' : 'negative' ?>">
                                                <?= $rowProfit >= 0 ? '+' : '−' ?>R$
                                                <?= number_format(abs((float)$rowProfit), 2, ',', '.') ?>
                                                (<?= number_format((float)$rowProfitPerc, 2, ',', '.') ?>%)
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a class="btn-icon" href="/investimentos/edit/<?= (int)$inv['id'] ?>" title="Editar">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                                <a class="btn-icon" href="/investimentos/delete/<?= (int)$inv['id'] ?>" data-delete title="Excluir">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php if (defined('BASE_PATH')): ?>
    <?php include BASE_PATH . '/views/admin/partials/modals/modal_investimentos.php'; ?>
<?php endif; ?>

<!-- Gráfico (pizza) -->
<script>
    (function() {
        const el = document.getElementById('categoryChart');
        if (!el) return;

        const cat = <?= json_encode($statsByCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
        const labels = cat.map(c => c.category || '-');
        const values = cat.map(c => Number(c.value || 0));
        const colors = cat.map(c => c.color || '#64748b');
        const total = values.reduce((a, b) => a + b, 0);

        new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#0b1220',
                    hoverOffset: 6,
                    cutout: '64%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: getComputedStyle(document.documentElement).getPropertyValue('--color-text') || '#e5e7eb',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const v = ctx.parsed;
                                const p = total > 0 ? (v / total) * 100 : 0;
                                return `${ctx.label}: ${v.toLocaleString('pt-BR',{style:'currency',currency:'BRL'})} (${p.toFixed(2)}%)`;
                            }
                        }
                    }
                }
            }
        });
    })();
</script>

<!-- SweetAlert2: toast para mensagens de sessão -->
<?php if (isset($_SESSION['message'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const iconMap = {
                success: 'success',
                error: 'error',
                danger: 'error',
                warning: 'warning',
                info: 'info'
            };

            const msg = <?= json_encode($_SESSION['message'], JSON_UNESCAPED_UNICODE) ?>;
            const type = <?= json_encode($_SESSION['message_type'] ?? 'info', JSON_UNESCAPED_UNICODE) ?>;

            const css = getComputedStyle(document.documentElement);
            const bg = (css.getPropertyValue('--color-surface') || '#1c2c3c').trim();
            const fg = (css.getPropertyValue('--color-text') || '#ffffff').trim();
            const ring = (css.getPropertyValue('--ring') || 'rgba(230,126,34,.22)').trim();

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: iconMap[type] ?? 'info',
                title: msg,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                background: bg,
                color: fg,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                    toast.style.boxShadow = `0 0 0 2px ${ring}`;
                    toast.style.borderRadius = '12px';
                }
            });
        });
    </script>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
<?php endif; ?>

<!-- SweetAlert2: confirmação de exclusão -->
<script>
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[data-delete]');
        if (!a) return;

        e.preventDefault();

        const css = getComputedStyle(document.documentElement);
        const bg = (css.getPropertyValue('--color-surface') || '#1c2c3c').trim();
        const fg = (css.getPropertyValue('--color-text') || '#ffffff').trim();
        const danger = (css.getPropertyValue('--color-danger') || '#e74c3c').trim();

        Swal.fire({
            icon: 'warning',
            title: 'Excluir investimento?',
            text: 'Esta ação não poderá ser desfeita.',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: danger,
            background: bg,
            color: fg
        }).then((r) => {
            if (r.isConfirmed) window.location.href = a.href;
        });
    });
</script>