<?php
// Header incluído automaticamente pelo framework render() — não duplicar
?>

<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

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

<!-- Modal Criar/Editar Post -->
<div id="modalPost" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>
                <i data-lucide="file-edit"></i>
                <span id="modalTitle">Novo Artigo</span>
            </h2>
            <button class="btn-close" data-action="fecharModalPost">&times;</button>
        </div>
        <form id="formPost">
            <input type="hidden" id="postId" name="id">
            <div class="modal-body">
                <!-- Row: Título + Slug -->
                <div class="form-row">
                    <div class="form-group flex-2">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" required placeholder="Ex: Como organizar seu dinheiro"
                            data-action="generateSlug">
                    </div>
                    <div class="form-group flex-1">
                        <label for="slug">Slug (URL)</label>
                        <input type="text" id="slug" name="slug" placeholder="auto-gerado-do-titulo">
                        <small>Deixe vazio para gerar automaticamente</small>
                    </div>
                </div>

                <!-- Row: Categoria + Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="blog_categoria_id">Categoria</label>
                        <select id="blog_categoria_id" name="blog_categoria_id">
                            <option value="">Sem categoria</option>
                            <!-- Preenchido via JS -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status">
                            <option value="draft">Rascunho</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>
                </div>

                <!-- Resumo -->
                <div class="form-group">
                    <label for="resumo">Resumo</label>
                    <textarea id="resumo" name="resumo" rows="3"
                        placeholder="Breve descrição do artigo (exibida na listagem)" maxlength="500"></textarea>
                    <small><span id="resumoCount">0</span>/500 caracteres</small>
                </div>

                <!-- Imagem de Capa -->
                <div class="form-group">
                    <label>Imagem de Capa</label>
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <i data-lucide="image-plus"></i>
                            <p>Clique ou arraste uma imagem</p>
                            <small>JPEG, PNG ou WebP — máx. 2MB</small>
                        </div>
                        <div class="upload-preview" id="uploadPreview" style="display: none;">
                            <img id="previewImg" src="" alt="Preview">
                            <button type="button" class="btn-remove-img" data-action="removerImagem">&times;</button>
                        </div>
                        <input type="file" id="imagemCapa" name="imagem_capa" accept="image/jpeg,image/png,image/webp"
                            style="display: none;">
                        <input type="hidden" id="imagemCapaPath" name="imagem_capa_path">
                    </div>
                </div>

                <!-- Conteúdo (TinyMCE) -->
                <div class="form-group">
                    <label for="conteudo">Conteúdo *</label>
                    <textarea id="conteudo" name="conteudo"></textarea>
                </div>

                <!-- SEO -->
                <details class="seo-section">
                    <summary>
                        <i data-lucide="search"></i>
                        Configurações de SEO
                    </summary>
                    <div class="seo-fields">
                        <div class="form-group">
                            <label for="meta_title">Meta Title</label>
                            <input type="text" id="meta_title" name="meta_title" maxlength="255"
                                placeholder="Título para mecanismos de busca">
                            <small>Deixe vazio para usar o título do artigo</small>
                        </div>
                        <div class="form-group">
                            <label for="meta_description">Meta Description</label>
                            <textarea id="meta_description" name="meta_description" rows="2" maxlength="500"
                                placeholder="Descrição para mecanismos de busca (máx. 160 caracteres recomendados)">
                            </textarea>
                            <small>Deixe vazio para usar o resumo do artigo</small>
                        </div>
                    </div>
                </details>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-action="fecharModalPost">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvar">
                    <i data-lucide="save"></i>
                    Salvar Artigo
                </button>
            </div>
        </form>
    </div>
</div>
