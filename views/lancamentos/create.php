<!-- app/Views/lancamentos/create.php -->
<div class="container py-4" style="max-width:720px">
    <h1 class="h4 mb-3">Adicionar lançamento</h1>

    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_SESSION['flash_error']);
                                            unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/lancamentos" method="post">
        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" class="form-select" required>
                <option value="">Selecione</option>
                <option value="receita">Receita</option>
                <option value="despesa">Despesa</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Valor (R$)</label>
            <input type="text" name="valor" class="form-control" placeholder="0,00" required>
            <div class="form-text">Digite em reais. Ex.: 123,45</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Categoria</label>
            <select name="categoria_id" class="form-select">
                <option value="">Sem categoria</option>
                <?php foreach (($categorias ?? []) as $c): ?>
                <option value="<?= (int)$c->id ?>"><?= htmlspecialchars($c->nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Data</label>
            <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" class="form-control" placeholder="Opcional">
        </div>

        <button class="btn btn-primary">Salvar</button>
        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-link">Cancelar</a>
    </form>
</div>