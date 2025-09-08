<section class="container">
    <h3>Lançamentos</h3>
    <section class="filters">
        <form id="formFiltros" class="filter-form">
            <div class="form-group">
                <label for="filtroMes"></label>
                <input type="month" id="filtroMes" class="form-input" value="<?= date('Y-m') ?>">
            </div>
        </form>
    </section>
    <div class="form-group">
        <label for="filtroTipo"></label>
        <select id="filtroTipo" class="form-select">
            <option value="">Todos</option>
            <option value="receita">Receitas</option>
            <option value="despesa">Despesas</option>
        </select>
        <button type="submit" class="btn btn-ghost"><i class="fas fa-search"></i> Filtrar</button>
    </div>
    <section class="table-container">
        <table class="lukrato-table" id="tabelaLancamentos">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th>Descrição</th>
                    <th>Observação</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody id="tbodyLancamentos">
                <tr>
                    <td colspan="6" class="text-center">Carregando...</td>
                </tr>
            </tbody>
        </table>
    </section>
</section>