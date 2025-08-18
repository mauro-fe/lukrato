<!-- app/Views/dashboard/index.php -->
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 m-0">Dashboard</h1>
        <a href="<?= BASE_URL ?>/lancamentos/novo" class="btn btn-primary">+ Adicionar lançamento</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Saldo total</div>
                    <div class="fs-4"><?= number_format($saldoTotal ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Receitas (mês)</div>
                    <div class="fs-4 text-success"><?= number_format($receitasMes ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Despesas (mês)</div>
                    <div class="fs-4 text-danger"><?= number_format($despesasMes ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm my-3">
        <div class="card-body">
            <div class="small text-muted mb-2">Fluxo de caixa do mês (R$ por dia)</div>
            <canvas id="fluxoChart" style="height:260px"></canvas>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small text-muted">Últimos lançamentos</div>
                <a href="<?= BASE_URL ?>/lancamentos" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th class="text-end">Valor (R$)</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($ultimos ?? []) as $l): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($l->data)) ?></td>
                            <td><span
                                    class="badge <?= $l->tipo === 'receita' ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars($l->tipo) ?></span>
                            </td>
                            <td><?= htmlspecialchars($l->categoria->nome ?? 'Sem categoria') ?></td>
                            <td class="text-end"><?= number_format($l->valor, 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($l->descricao ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ultimos) || count($ultimos) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-muted">Sem lançamentos ainda</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('fluxoChart');
const labels = <?= json_encode($labels ?? []) ?>;
const data = <?= json_encode($data ?? []) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'R$ por dia',
            data,
            tension: 0.3,
            fill: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: v => v.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    })
                }
            }
        }
    }
});
</script>