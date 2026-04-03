<!-- Tab Panel: Users -->
<div class="sysadmin-tab-panel" id="panel-usuarios" role="tabpanel" aria-labelledby="tab-usuarios">
    <!-- Filtros de Usuários -->
    <div class="user-filters-card">
        <form id="userFilters" class="user-filters-form">
            <input type="text" name="query" class="filter-input" placeholder="Buscar por nome, email ou ID..." />
            <select name="status" class="filter-select">
                <option value="">Todos</option>
                <option value="admin">Admin</option>
                <option value="user">Usuário</option>
            </select>
            <select name="plan" class="filter-select">
                <option value="">Todos os Planos</option>
                <option value="pro">Pro</option>
                <option value="free">Free</option>
            </select>
            <select name="perPage" class="filter-select">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>
            <button type="submit" class="btn-control primary"><i data-lucide="filter"></i> Filtrar</button>
        </form>
    </div>

    <!-- Tabela dinâmica de usuários -->
    <div class="table-section" id="userTableSection">
        <!-- Conteúdo da tabela será renderizado via JS -->
    </div>
</div>