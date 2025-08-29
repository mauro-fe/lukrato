<section class="container">
    <header class="page-header">
        <h1><i class="fas fa-receipt"></i> Lançamentos</h1>
    </header>

    <section class="filters">
        <form id="formFiltros" class="filter-form">
            <div class="form-group">
                <label for="filtroMes">Mês</label>
                <input type="month" id="filtroMes" class="form-input" value="<?= date('Y-m') ?>">
            </div>
            <div class="form-group">
                <label for="filtroTipo">Tipo</label>
                <select id="filtroTipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="receita">Receitas</option>
                    <option value="despesa">Despesas</option>
                </select>
            </div>
            <button type="submit" class="btn btn-ghost"><i class="fas fa-search"></i> Filtrar</button>
        </form>
    </section>

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