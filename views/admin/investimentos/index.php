<?php
// Garante que $data exista e normaliza nomes
$data = (isset($data) && is_array($data)) ? $data : [];

$investments      = $data['investments']      ?? [];
// aceita o nome correto e, por segurança, o que estava com L duplicado
$totalInvested    = (float)($data['totalInvested']    ?? ($data['totallnvested'] ?? 0));
$currentValue     = (float)($data['currentValue']     ?? 0);
$profit           = (float)($data['profit']           ?? ($currentValue - $totalInvested));
$profitPercentage = (float)($data['profitPercentage'] ?? ($totalInvested > 0 ? (($profit / $totalInvested) * 100) : 0));

// Se seu helper global ainda não estiver carregado neste template,
// garanta o include/require global no header (onde você já carrega helpers).
?>



<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5.5.2/dist/css/tabulator.min.css">


<div class="main-content">
    <div class="page-header">
        <h1>Investimentos</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-investimentos">
            <i class="icon-plus"></i> Novo Investimento
        </button>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?>">
            <?= $_SESSION['message'] ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>


    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="icon-wallet"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total Investido</span>
                <span class="stat-value">R$ <?= number_format($totalInvested, 2, ',', '.') ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="icon-trending-up"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Valor Atual</span>
                <span class="stat-value">R$ <?= number_format((float)$currentValue, 2, ',', '.') ?></span>

            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?= ($profit >= 0 ? 'green' : 'red') ?>">
                <i class="icon-<?= ($profit >= 0 ? 'arrow-up' : 'arrow-down') ?>"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Lucro/Prejuízo</span>
                <span class="stat-value" style="color: <?= ($profit >= 0 ? '#28a745' : '#dc3545') ?>">
                    R$ <?= number_format((float)$profit, 2, ',', '.') ?>
                    (<?= number_format((float)$profitPercentage, 2, ',', '.') ?>%)
                    <!-- ou: <?= ($profit) ?> (<?= number_format((float)$profitPercentage, 2, ',', '.') ?>%) -->
                </span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="icon-briefcase"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total de Ativos</span>
                <span class="stat-value"><?= (is_array($investments) ? count($investments) : 0) ?></span>
            </div>
        </div>

    </div>

    <div class="content-grid">

        <div class="card">
            <div class="card-header">
                <h3>Distribuição por Categoria</h3>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" height="300"></canvas>
            </div>
        </div>

        <!-- Lista de Investimentos -->
        <div class="card full-width">
            <div class="card-header">
                <h3>Meus Investimentos</h3>
            </div>
            <div class="card-body">
                <?php if (empty($investments)): ?>
                    <div class="empty-state">
                        <i class="icon-inbox"></i>
                        <p>Nenhum investimento cadastrado ainda.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-investimentos">
                            Adicionar Primeiro Investimento
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
                                    $totalInvestedRow = $inv['quantity'] * $inv['avg_price'];
                                    $currentValue     = $inv['quantity'] * ($inv['current_price'] ?? $inv['avg_price']);
                                    $profitLoss       = $currentValue - $totalInvestedRow;
                                    $profitPerc       = $totalInvestedRow > 0 ? (($profitLoss / $totalInvestedRow) * 100) : 0;
                                ?>

                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($inv['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: <?= $inv['color'] ?>">
                                                <?= htmlspecialchars($inv['category_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($inv['ticker'] ?? '-') ?></td>
                                        <td><?= number_format((float)$inv['quantity'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float)$inv['avg_price'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format((float)($inv['current_price'] ?? $inv['avg_price']), 2, ',', '.') ?></td>
                                        <td><strong>R$ <?= number_format((float)$currentValue, 2, ',', '.') ?></strong></td>
                                        <td>
                                            <span class="profit-badge <?= $profitLoss >= 0 ? 'positive' : 'negative' ?>">
                                                <?= $profitLoss >= 0 ? '+' : '' ?>R$ <?= number_format((float)$profitLoss, 2, ',', '.') ?>
                                                (<?= number_format((float)$profitPerc, 2, ',', '.') ?>%)
                                            </span>
                                        </td>

                                        <td>
                                            <div class="action-buttons">
                                                <a href="/investimentos/edit/<?= $inv['id'] ?>" class="btn-icon btn-edit" title="Editar">
                                                    <i class="icon-edit"></i>
                                                </a>
                                                <a href="/investimentos/delete/<?= $inv['id'] ?>"
                                                    class="btn-icon btn-delete"
                                                    title="Excluir"
                                                    onclick="return confirm('Tem certeza que deseja excluir este investimento?')">
                                                    <i class="icon-trash"></i>
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
    </div>
</div>

<?php
$categories = $categories ?? ($data['categories'] ?? []);
include BASE_PATH . '/views/admin/partials/modals/modal_investimentos.php';
?>


<script>
    // Gráfico de pizza para distribuição por categoria
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?= json_encode($statsByCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;


    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(c => c.category),
            datasets: [{
                data: categoryData.map(c => c.value),
                backgroundColor: categoryData.map(c => c.color),
                borderWidth: 2,
                borderColor: '#1a2332'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#fff',
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(2);
                            return context.label + ': R$ ' + value.toFixed(2).replace('.', ',') + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
</script>