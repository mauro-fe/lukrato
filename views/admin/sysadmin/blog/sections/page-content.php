<div class="main-content">
    <div class="blog-container">
        <!-- Botão Voltar -->
        <a href="<?= BASE_URL ?>sysadmin" class="btn-voltar">
            <i data-lucide="arrow-left"></i>
            <span>Voltar ao Painel</span>
        </a>

        <!-- Header -->
        <div class="blog-header">
            <div class="blog-header-title">
                <div class="blog-header-icon">
                    <i data-lucide="book-open"></i>
                </div>
                <div>
                    <h1>Blog — Aprenda</h1>
                    <p>Crie e gerencie artigos educativos sobre finanças pessoais</p>
                </div>
            </div>
            <button class="btn-criar-post" data-action="abrirModalCriarPost">
                <i data-lucide="circle-plus"></i>
                Novo Artigo
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="blog-stats" id="blogStats" style="display: none;">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i data-lucide="file-text"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotal">0</h3>
                    <p>Total de Artigos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i data-lucide="globe"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statPublished">0</h3>
                    <p>Publicados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i data-lucide="file-edit"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statDraft">0</h3>
                    <p>Rascunhos</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="blog-filters">
            <div class="filter-group">
                <div class="search-box">
                    <i data-lucide="search"></i>
                    <input type="text" id="filterSearch" placeholder="Buscar artigos..." data-action="filterPosts">
                </div>
                <select id="filterCategoria" data-action="filterPosts">
                    <option value="">Todas as categorias</option>
                </select>
                <select id="filterStatus" data-action="filterPosts">
                    <option value="">Todos os status</option>
                    <option value="published">Publicados</option>
                    <option value="draft">Rascunhos</option>
                </select>
            </div>
        </div>

        <!-- Tabela de Posts -->
        <div class="blog-table-container">
            <div id="loading" class="loading">
                <i data-lucide="loader-2" class="icon-spin"></i>
                Carregando artigos...
            </div>
            <table class="blog-table" id="blogTable" style="display: none;">
                <thead>
                    <tr>
                        <th class="col-img">Capa</th>
                        <th class="col-titulo">Título</th>
                        <th class="col-cat">Categoria</th>
                        <th class="col-status">Status</th>
                        <th class="col-data">Data</th>
                        <th class="col-acoes">Ações</th>
                    </tr>
                </thead>
                <tbody id="blogTableBody">
                    <!-- Preenchido via JavaScript -->
                </tbody>
            </table>
            <div id="emptyState" class="empty-state" style="display: none;">
                <i data-lucide="book-open"></i>
                <h3>Nenhum artigo cadastrado</h3>
                <p>Crie seu primeiro artigo para começar o blog</p>
            </div>
            <div id="paginationContainer" class="pagination-wrapper"></div>
        </div>
    </div>
</div>
