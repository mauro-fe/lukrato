<?php
// Garantia de variáveis vindas do controller
if (!isset($investments))      $investments = [];
if (!isset($totalInvested))    $totalInvested = 0.0;
if (!isset($currentValue))     $currentValue = 0.0;
if (!isset($profit))           $profit = ($currentValue - $totalInvested);
if (!isset($profitPercentage)) $profitPercentage = ($totalInvested > 0 ? (($profit / $totalInvested) * 100) : 0.0);
if (!isset($statsByCategory))  $statsByCategory = [];
if (!isset($categories))       $categories = [];
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-investimentos-index.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin-partials-modals-modal_investimentos.css">

<div class="main-content">
    <div class="page-header" data-aos="fade-up">
        <button type=" button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-investimentos"
            title="Adicionar investimento">
            <i class="fa-solid fa-plus"></i> Novo investimento
        </button>
    </div>

    <section class="stats-grid">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-ico blue"><i class="fa-solid fa-wallet"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Investido</span>
                <span class="stat-value">R$ <?= number_format((float)$totalInvested, 2, ',', '.') ?></span>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-left">
            <div class="stat-ico green"><i class="fa-solid fa-arrow-trend-up"></i></div>
            <div class="stat-info">
                <span class="stat-label">Valor Atual</span>
                <span class="stat-value">R$ <?= number_format((float)$currentValue, 2, ',', '.') ?></span>
            </div>
        </div>

        <!-- Card de Lucro/Prejuízo comentado a pedido
        <div class="stat-card">
            <div class="stat-ico <?= ($profit >= 0 ? 'green' : 'red') ?>">
                <i class="fa-solid fa-<?= ($profit >= 0 ? 'arrow-up' : 'arrow-down') ?>"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Lucro/Prejuízo</span>
                <span class="stat-value <?= ($profit >= 0 ? 'positive' : 'negative') ?>">
                    <?= $profit >= 0 ? '+' : '-' ?>R$ <?= number_format(abs((float)$profit), 2, ',', '.') ?>
                    (<?= number_format((float)$profitPercentage, 2, ',', '.') ?>%)
                </span>
            </div>
        </div>
        -->

        <div class="stat-card" data-aos="flip-left">
            <div class="stat-ico orange"><i class="fa-solid fa-briefcase"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total de Ativos</span>
                <span class="stat-value"><?= (is_array($investments) ? count($investments) : 0) ?></span>
            </div>
        </div>
    </section>

    <section class="content-grid">
        <div class="card" data-aos="flip-left">
            <div class="card-header">
                <h3>Distribuição por Categoria</h3>
            </div>
            <div class="card-body">
                <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>

        <div class="card" data-aos="flip-right">
            <div class="card-header">
                <h3>Resumo por Categoria</h3>
            </div>
            <div class="card-body">
                <div class="cat-list">
                    <?php foreach ($statsByCategory as $c): ?>
                    <div class="cat-row">
                        <div class="cat-left">
                            <span class="cat-dot"
                                style="background:<?= htmlspecialchars($c['color'] ?? '#64748b') ?>"></span>
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

        <div class="card full-width" data-aos="zoom-in">
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
                                <!-- <th>Rentabilidade</th> -->
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($investments as $inv):
                                    $quantity      = (float)($inv['quantidade'] ?? $inv['quantity'] ?? 0);
                                    $avgPrice      = (float)($inv['preco_medio'] ?? $inv['avg_price'] ?? 0);
                                    $currentPrice  = (float)($inv['preco_atual'] ?? $inv['current_price'] ?? $avgPrice);
                                    $rowInvested   = $quantity * $avgPrice;
                                    $rowCurrent    = isset($inv['valor_atual']) ? (float)$inv['valor_atual'] : $quantity * $currentPrice;
                                    /* Rentabilidade comentada
                                    $rowProfit     = $rowCurrent - $rowInvested;
                                    $rowProfitPerc = $rowInvested > 0 ? ($rowProfit / $rowInvested) * 100 : 0;
                                    */
                                ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($inv['nome'] ?? $inv['name'] ?? '-') ?></strong></td>
                                <td>
                                    <span class="badge"
                                        style="background:<?= htmlspecialchars($inv['cor'] ?? $inv['color'] ?? '#475569') ?>">
                                        <?= htmlspecialchars($inv['categoria_nome'] ?? $inv['category_name'] ?? '-') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($inv['ticker'] ?? '-') ?></td>
                                <td><?= number_format($quantity, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($avgPrice, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($currentPrice, 2, ',', '.') ?></td>
                                <td><strong>R$ <?= number_format((float)$rowCurrent, 2, ',', '.') ?></strong></td>
                                <!-- <td>
                                            <span class="profit-badge <?= isset($rowProfit) && $rowProfit >= 0 ? 'positive' : 'negative' ?>">
                                                <?= isset($rowProfit) && $rowProfit >= 0 ? '+' : '-' ?>R$
                                                <?= isset($rowProfit) ? number_format(abs((float)$rowProfit), 2, ',', '.') : '0,00' ?>
                                                (<?= isset($rowProfitPerc) ? number_format((float)$rowProfitPerc, 2, ',', '.') : '0,00' ?>%)
                                            </span>
                                        </td> -->
                                <td>
                                    <div class="action-buttons">
                                        <a class="btn-icon"
                                            href="<?= BASE_URL ?>investimentos/edit/<?= (int)$inv['id'] ?>" data-edit
                                            data-id="<?= (int)$inv['id'] ?>" title="Editar">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                        <a class="btn-icon"
                                            href="<?= BASE_URL ?>investimentos/delete/<?= (int)$inv['id'] ?>"
                                            data-delete data-id="<?= (int)$inv['id'] ?>" title="Excluir">
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
                        color: getComputedStyle(document.documentElement).getPropertyValue(
                            '--color-text') || '#e5e7eb',
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

<script>
// Handlers em fase de captura para sobrescrever listeners antigos
const BASE_URL = '<?= BASE_URL ?>';
const cssVars = getComputedStyle(document.documentElement);
const ui = {
    bg: (cssVars.getPropertyValue('--color-surface') || '#1c2c3c').trim(),
    fg: (cssVars.getPropertyValue('--color-text') || '#ffffff').trim(),
    ring: (cssVars.getPropertyValue('--ring') || 'rgba(230,126,34,.22)').trim(),
    danger: (cssVars.getPropertyValue('--color-danger') || '#e74c3c').trim()
};

function toast(title, type = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title,
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true,
        background: ui.bg,
        color: ui.fg,
        didOpen: (t) => {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
            t.style.boxShadow = `0 0 0 2px ${ui.ring}`;
            t.style.borderRadius = '12px';
        }
    });
}

let editingId = null;

// Editar: captura antes e impede handlers antigos
document.addEventListener('click', async (e) => {
    const a = e.target.closest('a[data-edit]');
    if (!a) return;
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const id = a.dataset.id;
    if (!id) return;
    editingId = id;

    try {
        const res = await fetch(`${BASE_URL}api/investimentos/${id}`);
        const json = await res.json();
        if (!res.ok || json.error) throw new Error(json.message || 'Falha ao carregar investimento');
        const d = json.data ?? json;

        const form = document.getElementById('form-investimento');
        form.action = `${BASE_URL}api/investimentos/${id}/update`;
        form.method = 'POST';
        document.getElementById('modalInvestimentosLabel').textContent = 'Editar Investimento';

        document.getElementById('category_id').value = String(d.categoria_id || '');
        document.getElementById('name').value = d.nome || '';
        document.getElementById('ticker').value = d.ticker || '';
        document.getElementById('quantity').value = (d.quantidade ?? '').toString();
        document.getElementById('avg_price').value = (d.preco_medio ?? '').toString();
        document.getElementById('current_price').value = (d.preco_atual ?? '').toString();
        const notesEl = document.getElementById('notes');
        if (notesEl) notesEl.value = d.observacoes || '';

        const modalEl = document.getElementById('modal-investimentos');
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();

        document.getElementById('avg_price').dispatchEvent(new Event('input'));
    } catch (err) {
        console.error(err);
        toast('Falha ao carregar dados do investimento', 'error');
    }
}, true);

// Reset ao fechar
document.getElementById('modal-investimentos')?.addEventListener('hidden.bs.modal', () => {
    const form = document.getElementById('form-investimento');
    form.reset();
    form.action = `${BASE_URL}api/investimentos`;
    form.method = 'POST';
    document.getElementById('modalInvestimentosLabel').textContent = 'Novo Investimento';
    editingId = null;
});

// Submit via fetch
document.getElementById('form-investimento')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const fd = new FormData(form);
    const body = new URLSearchParams();
    for (const [k, v] of fd.entries()) body.append(k, v);
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.error) throw new Error(json.message || 'Erro ao salvar');
        toast(editingId ? 'Investimento atualizado!' : 'Investimento criado!');
        setTimeout(() => window.location.reload(), 900);
    } catch (err) {
        console.error(err);
        toast(err.message || 'Falha ao salvar', 'error');
    }
});

// Excluir via fetch com captura
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[data-delete]');
    if (!a) return;
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const id = a.dataset.id;
    Swal.fire({
        icon: 'warning',
        title: 'Excluir investimento?',
        text: 'Esta ação não poderá ser desfeita.',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: ui.danger,
        background: ui.bg,
        color: ui.fg
    }).then(async (r) => {
        if (!r.isConfirmed) return;
        try {
            const res = await fetch(`${BASE_URL}api/investimentos/${id}/delete`, {
                method: 'POST'
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || json.error) throw new Error(json.message || 'Erro ao excluir');
            toast('Excluído com sucesso');
            setTimeout(() => window.location.reload(), 900);
        } catch (err) {
            console.error(err);
            toast(err.message || 'Falha ao excluir', 'error');
        }
    });
}, true);
</script>
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