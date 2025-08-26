<style>
/* ===== Offset para não ficar sob o header sticky ===== */
:root {
    /* margem superior que o conteúdo precisa para não ficar embaixo do header */
    --content-top-offset: calc(var(--header-height) + var(--spacing-4));
}

/* se sua página usa <main class="main-content"> ou .lukrato-main, ambas cobertas */
.main-content,
.lukrato-main {
    padding-top: var(--content-top-offset);
    padding-left: var(--container-padding);
    padding-right: var(--container-padding);
}

/* quando o aside some no mobile, mantemos um pouco menos de espaço */
@media (max-width: 768px) {
    :root {
        --content-top-offset: calc(var(--header-height) + var(--spacing-2));
    }
}

.content-offset {
    padding-top: var(--content-top-offset);
}

/* alias para a tabela de lançamentos */
.lukrato-table {
    width: 100%;
    border-collapse: collapse;
}

.lukrato-table th,
.lukrato-table td {
    text-align: left;
    padding: var(--spacing-4);
    border-bottom: 1px solid var(--glass-border);
    color: var(--branco);
}

.lukrato-table th {
    font-weight: 600;
    color: var(--cinza);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.lukrato-table tr:hover {
    background-color: var(--glass-bg);
}
</style>

<section class="main-content">
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