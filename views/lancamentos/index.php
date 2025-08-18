<!-- app/Views/lancamentos/index.php -->
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 m-0">Lançamentos</h1>
        <a href="<?= BASE_URL ?>/lancamentos/novo" class="btn btn-primary">+ Adicionar</a>
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
                <?php foreach (($lancamentos ?? []) as $l): ?>
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
                <?php if (empty($lancamentos) || count($lancamentos) === 0): ?>
                <tr>
                    <td colspan="5" class="text-muted">Sem lançamentos</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>